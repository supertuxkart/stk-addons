<?php
/**
 * copyright 2012
 *
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */

include_once('exceptions.php');
include_once('sql.php');
include_once('Validate.class.php');

class ClientSessionException extends Exception {}
class ClientSessionConnectException extends ClientSessionException {}
class ClientSessionExpiredException extends ClientSessionException {}

/**
 * Abstract base class for handling client sessions
 */
abstract class ClientSession
{
    private $session_id;

    protected function __construct($sessionid)
    {
        $this->session_id = $sessionid;
    }

    /**
     * Get current session id
     * @return string session id
     */
    public function getSessionId()
    {
        return $this->session_id;
    }

    /**
     * Get user name for this session
     * @return string user name
     */
    abstract public function getName();

    /**
     * Get user id for this session
     * @return int user id
     */
    abstract public function getUserId();

    /**
     * Regenerate session with new session id
     */
    public function regenerate()
    {
        $new_session_id = ClientSession::calcSessionId();
        $this->updateSessionId($new_session_id);
        // nothing awful happend while updating database, can change attribute safely
        $this->session_id = $new_session_id;
    }

    /**
     * Update session id in database table
     * @param string new session id
     */
    abstract protected function updateSessionId($session_id);

    /**
     * Create new session
     * @param string $username user name (registered user or temporary nickname)
     * @param string $password password of registered user (optional)
     * @return ClientSession object
     * @throws InvalidArgumentException when username is not provided
     */
    public static function create($username, $password = '')
    {
        if (empty($username)) {
            throw new InvalidArgumentException('Username required');
        }
        else if (empty($password)) {
            return ClientSessionAnonymous::create($username);
        }
        else {
            return ClientSessionUser::create($username, $password);
        }
    }

    /**
     * Get session object for already created session
     * @param string $session_id session id
     * @param mixed $user username (string) or numerical user id
     * @return ClientSessionAnonymous|ClientSessionUser
     * @throws ClientSessionExpiredException when session does not exist
     */
    public static function get($session_id, $user)
    {
        $sql = null;
        if (ctype_digit("$user") && $user > 0) {
            $sql = sprintf("SELECT * FROM `%s` WHERE cid = '%s' AND uid = %d",
                    DB_PREFIX.'client_sessions',
                    mysql_real_escape_string($session_id),
                    (int) $user);
        }
        else {
            $sql = sprintf("SELECT * FROM `%s` WHERE cid = '%s' AND name = '%s'",
                    DB_PREFIX.'client_sessions',
                    mysql_real_escape_string($session_id),
                    mysql_real_escape_string($user));
        }

        $result = sql_query($sql);
        if (!$result || mysql_num_rows($result) == 0) {
            throw new ClientSessionExpiredException('No session found');
        }
        else {
            $session_row = mysql_fetch_object($result);
            if ($session_row->uid == 0) {
                return new ClientSessionAnonymous($session_id, $session_row->name);
            }
            else {
                return new ClientSessionUser($session_id, $session_row->uid, $session_row->name);
            }
        }
    }

    /**
     * Destroy session, you could also call it logout
     * @param string $session_id session id
     * @param mixed $user username (string) or numerical user id
     * @throws ClientSessionExpiredException when session does not exist
     */
    public static function destroy($session_id, $user)
    {
        $sql = null;

        if (ctype_digit("$user") && $user > 0) {
            $sql = sprintf("DELETE FROM `%s` WHERE cid = '%s' AND uid = %d",
                    DB_PREFIX.'client_sessions',
                    mysql_real_escape_string($session_id), (int) $user);
        }
        else {
            $sql = sprintf("DELETE FROM `%s` WHERE cid = '%s' AND name = '%s'",
                    DB_PREFIX.'client_sessions',
                    mysql_real_escape_string($session_id),
                    mysql_real_escape_string($user));
        }

        if (!sql_query($sql) || mysql_affected_rows() == 0)
            throw new ClientSessionExpiredException('Could not destroy session');
    }

    /**
     * Generate a alphanumerical session id
     * @return string session id
     */
    protected static function calcSessionId()
    {
        // TODO: Not sure if this is strong enough, looks quite good for a first trial though
        return substr(md5(uniqid('', true)), 0, 24);
    }
}

/**
 * ClientSession implementation for registered users
 */
class ClientSessionUser extends ClientSession
{
    private $user_id;
    private $user_name;

    /**
     * New instance
     * @param string $session_id
     * @param int $user_id
     * @param string $user_name
     */
    protected function __construct($session_id, $user_id, $user_name)
    {
        parent::__construct($session_id);
        $this->user_id = $user_id;
        $this->user_name = $user_name;
    }

    /**
     * Create session for registered user
     * @param string $username username
     * @param type $password password (plain)
     * @return ClientSessionUser
     * @throws ClientSessionConnectException when credentials are wrong
     */
    public static function create($username, $password = '')
    {
        $username = Validate::username($username);

        // TODO: Share password checking with User class
        // Currently User class is tightly coupled with session handling, so can't use it here yet
        $sql = sprintf("SELECT id FROM `%s` WHERE user = '%s' AND pass = '%s'",
                DB_PREFIX.'users',
                $username,
                Validate::password($password, null, $username));
        $result = sql_query($sql);

        if (!$result) {
            throw new ClientSessionConnectException('Could not find user.');
        }
        elseif ($result && mysql_num_rows($result) == 1) {
            $session_id = ClientSession::calcSessionId();
            $user_row = mysql_fetch_row($result);
            $user_id = (int) $user_row[0];

            // if there is already a session, then we update it
            $sql = sprintf("INSERT INTO `%s` (cid, uid, name) VALUES ('%s', %d, '%s')
                    ON DUPLICATE KEY UPDATE cid = '%2\$s'",
                    DB_PREFIX.'client_sessions',
                    mysql_real_escape_string($session_id),
                    $user_id,
                    mysql_real_escape_string($username));

            if (sql_query($sql)) {
                return new ClientSessionUser($session_id, $user_id, $username);
            }
            else {
                throw new ClientSessionConnectException('Could not create new session');
            }
        }
        else {
            throw new ClientSessionConnectException('Invalid credentials');
        }
    }

    /**
     * Update session id in database
     * @param string $session_id new session id
     * @throws ClientSessionExpiredException when session does not exist
     */
    protected function updateSessionId($session_id)
    {
        $sql = sprintf("UPDATE `%s` SET cid = '%s' WHERE cid = '%s' AND uid = %d",
            DB_PREFIX.'client_sessions',
            mysql_real_escape_string($session_id),
            mysql_real_escape_string($this->getSessionId()),
            (int) $this->getUserId());

        if (!sql_query($sql) || mysql_affected_rows() == 0)
            throw new ClientSessionExpiredException('Refreshing user session failed');
    }

    public function getName()
    {
        return $this->user_name;
    }

    public function getUserId()
    {
        return $this->user_id;
    }
}

/**
 *ClientSession implementation for unregistered users
 */
class ClientSessionAnonymous extends ClientSession
{
    private $name;

    /**
     * New instance
     * @param string $session_id session id
     * @param string $name temporary nickname
     */
    protected function __construct($session_id, $name)
    {
        parent::__construct($session_id);
        $this->name = $name;
    }

    /**
     * Create session in database
     * @param string $name nickname
     * @param string $password unused, but needed to keep child compatibility
     * @return ClientSessionAnonymous
     * @throws ClientSessionConnectException when nickname is already used
     */
    public static function create($name, $password='')
    {
        $session_id = ClientSession::calcSessionId();

        $name = Validate::username($name);
        if (sql_exist('client_sessions', 'name', $name) || User::exists($name)) {
            throw new ClientSessionConnectException("Nickname $name already used");
        }
        else {
            $properties = array('name', 'cid');
            $values = array($name, $session_id);
            if (sql_insert('client_sessions', $properties, $values)) {
                return new ClientSessionAnonymous($session_id, $name);
            }
            else {
                throw new ClientSessionConnectException('Could not create new session');
            }
        }
    }

    /**
     * Update session id in database
     * @param string $session_id new session id
     * @throws ClientSessionExpiredException when session does not exist
     */
    protected function updateSessionId($session_id)
    {
        $sql = sprintf("UPDATE `%s` SET cid = '%s' WHERE cid = '%s' AND name = '%s'",
            DB_PREFIX.'client_sessions',
            mysql_real_escape_string($session_id),
            mysql_real_escape_string($this->getSessionId()),
            mysql_real_escape_string($this->getName()));

        if (!sql_query($sql) || mysql_affected_rows() == 0)
            throw new ClientSessionExpiredException('Refreshing temporary session failed');
    }

    public function getName()
    {
        return $this->name;
    }

    public function getUserId()
    {
        return 0;
    }
}
?>