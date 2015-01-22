<?php
/**
 * copyright 2013 Glenn De Jonghe
 *           2014 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
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

/**
 * Abstract base class for handling client sessions
 */
abstract class ClientSession
{
    /**
     * @var string
     */
    protected $session_id;

    /**
     * @var int
     */
    protected $user_id;

    /**
     * @var string
     */
    protected $user_name;

    /**
     * @param string $session_id
     * @param int    $user_id
     * @param string $user_name
     */
    protected function __construct($session_id, $user_id, $user_name)
    {
        $this->session_id = $session_id;
        $this->user_id = (int)$user_id;
        $this->user_name = $user_name;
    }

    /**
     * Get current session id
     * @return string session id
     */
    public function getSessionID()
    {
        return $this->session_id;
    }

    /**
     * Get user id for this session
     * @return int user id
     */
    public function getUserID()
    {
        return $this->user_id;
    }

    /**
     * Get user name for this session
     * @return string user name
     */
    public function getUsername()
    {
        return $this->user_name;
    }

    /**
     * Destroy session, you could also call it logout
     *
     * @throws ClientSessionExpiredException when session does not exist
     */
    public function destroy()
    {
        try
        {
            DBConnection::get()->query(
                "DELETE FROM `" . DB_PREFIX . "client_sessions`
    	        WHERE `cid` = :session_id AND uid = :user_id",
                DBConnection::ROW_COUNT,
                [
                    ':user_id'    => $this->user_id,
                    ':session_id' => $this->session_id
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionExpiredException(
                _h('An error occurred while signing out.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }
    }

    /**
     * @throws ClientSessionExpiredException
     */
    public function clientQuit()
    {
        try
        {
            DBConnection::get()->beginTransaction();

            $client = DBConnection::get()->query(
                "SELECT `save` FROM `" . DB_PREFIX . "client_sessions`
    	        WHERE `cid` = :session_id AND uid = :user_id",
                DBConnection::FETCH_FIRST,
                [
                    ':user_id'    => $this->user_id,
                    ':session_id' => $this->session_id
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );

            if ($client)
            {
                if ($client['save'] == 1)
                {
                    $this->setOnline(false);
                }
                else
                {
                    $this->destroy();
                }
            }


            DBConnection::get()->commit();
        }
        catch(DBException $e)
        {
            throw new ClientSessionExpiredException(
                _h('An error occurred while logging out.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }
    }

    /**
     * Get the public address of a player
     *
     * @param int $peer_id id of the peer
     *
     * @return array the ip and port of the player
     * @throws ClientSessionException if the request fails
     */
    public function getPeerAddress($peer_id)
    {
        try
        {
            //FIXME :   A check should be done that the requester is the host of a server
            //          the requestee has joined. (Else anybody with an account could call this with the correct POST parameters)
            // Query the database to set the ip and port
            $result = DBConnection::get()->query(
                "SELECT `ip`, `port`, `private_port`
                FROM `" . DB_PREFIX . "client_sessions`
                WHERE `uid` = :peerid",
                DBConnection::FETCH_ALL,
                [':peerid' => $peer_id],
                [':peerid' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionException(
                _h('An error occurred while getting a peer\'s ip:port.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        $size = count($result);
        if ($size === 0)
        {
            throw new ClientSessionException(_h('That user is not signed in.'));
        }
        elseif ($size > 1)
        {
            throw new ClientSessionException(_h('Too much users match the request'));
        }

        return $result[0];
    }

    /**
     * @param $server_id
     *
     * @return array|int|null
     * @throws ClientSessionConnectException
     */
    public function requestServerConnection($server_id)
    {
        try
        {
            $count = DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "server_conn` (serverid, userid, request)
                VALUES ( :serverid, :userid, 1) 
                ON DUPLICATE KEY 
                UPDATE request = '1', serverid = :serverid",
                DBConnection::ROW_COUNT,
                [
                    ':userid'   => $this->user_id,
                    ':serverid' => $server_id
                ],
                [':userid' => DBConnection::PARAM_INT, ':serverid' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionConnectException(
                _h('An error occurred while requesting a server connection.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        if ($count > 2 || $count < 0)
        {
            throw new ClientSessionConnectException(h("requestServerConnection: Unexpected error occurred"));
        }

        return $count;
    }

    /**
     * Join a server
     *
     * @return mixed
     * @throws ClientSessionConnectException
     */
    public function quickJoin()
    {
        try
        {
            // Query the database to add the request entry
            $server = DBConnection::get()->query(
                "SELECT `id`, `hostid`, `ip`, `port`, `private_port`
                FROM `" . DB_PREFIX . "servers`
                LIMIT 1",
                DBConnection::FETCH_FIRST
            );

            if (!$server)
            {
                throw new ClientSessionConnectException(_h('No server found'));
            }

            DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "server_conn` (serverid, userid, request)
                VALUES ( :serverid, :userid, 1)
                ON DUPLICATE KEY UPDATE request = '1'",
                DBConnection::NOTHING,
                [
                    ':userid'   => $this->user_id,
                    ':serverid' => $server['id']
                ],
                [':userid' => DBConnection::PARAM_INT, ':serverid' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionConnectException(
                _h('An error occurred while quick joining.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        return $server;
    }

    /**
     * @param string $ip
     * @param int    $port
     *
     * @return array|int|null
     * @throws ClientSessionException
     */
    public function getServerConnectionRequests($ip, $port)
    {
        try
        {
            $server_id = DBConnection::get()->query(
                "SELECT `id` FROM `" . DB_PREFIX . "servers`
                WHERE `hostid` = :hostid AND `ip` = :ip AND `port` = :port LIMIT 1",
                DBConnection::FETCH_ALL,
                [
                    ':hostid' => $this->user_id,
                    ':ip'     => $ip,
                    ':port'   => $port
                ],
                [
                    ':hostid' => DBConnection::PARAM_INT,
                    ':ip'     => DBConnection::PARAM_INT,
                    ':port'   => DBConnection::PARAM_INT
                ]
            );
            $connection_requests = DBConnection::get()->query(
                "SELECT `userid`
                FROM `" . DB_PREFIX . "server_conn`
                WHERE `serverid` = :server_id AND `request` = '1'",
                DBConnection::FETCH_ALL,
                [':server_id' => $server_id[0]['id']],
                [':serverid' => DBConnection::PARAM_INT]
            );

            // Set the request bit to zero for all users we fetch
            $index = 0;
            $parameters = [];
            $query_parts = [];
            foreach ($connection_requests as $user)
            {
                $parameter = ":userid" . $index;
                $index++;
                $query_parts[] = "`userid` = " . $parameter;
                $parameters[$parameter] = $user['userid'];
            }

            if ($index > 0)
            {
                DBConnection::get()->query(
                    "UPDATE `" . DB_PREFIX . "server_conn`
                    SET `request` = 0
                    WHERE " . implode(" OR ", $query_parts),
                    DBConnection::ROW_COUNT,
                    $parameters
                );
                // TODO Perhaps check if $count and $index are equal
            }
        }
        catch(DBException $e)
        {
            throw new ClientSessionException(
                _h('An error occurred while fetching server connection requests.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        return $connection_requests;
    }

    /**
     * @param bool $online
     *
     * @throws ClientSessionException
     */
    public function setOnline($online = true)
    {
        try
        {
            // sometimes the MYSQL 'ON UPDATE' does not fire because the online value is the same
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "client_sessions`
                SET `online` = :online, `last-online` = NOW()
                WHERE `uid` = :id",
                DBConnection::ROW_COUNT,
                [
                    ':id'     => $this->user_id,
                    ':online' => ($online ? 1 : 0),

                ],
                [
                    ':id'     => DBConnection::PARAM_INT,
                    ':online' => DBConnection::PARAM_INT
                ]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionException(
                _h('An unexpected error occured while updating your status.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }
    }

    /**
     * Create new session
     *
     * @param string $username user name (registered user or temporary nickname)
     * @param string $password password of registered user (optional)
     * @param bool   $save_session
     *
     * @return ClientSession|RegisteredClientSession object
     * @throws InvalidArgumentException when username is not provided
     */
    public static function create($username, $password, $save_session)
    {
        if (empty($username))
        {
            throw new InvalidArgumentException(_h('Username required'));
        }
        elseif (empty($password))
        {
            throw new InvalidArgumentException(_h('Password required'));
        }

        return RegisteredClientSession::create($username, $password, $save_session);
    }

    /**
     * Get session object for already created session
     *
     * @param string $session_id session id
     * @param int    $user_id    user id
     *
     * @return RegisteredClientSession
     * @throws ClientSessionExpiredException|ClientSessionException when session does not exist
     * @throws UserException on database error
     */
    public static function get($session_id, $user_id)
    {
        try
        {
            $session_info = DBConnection::get()->query(
                "SELECT * FROM `" . DB_PREFIX . "client_sessions`
                WHERE cid = :sessionid AND uid = :userid",
                DBConnection::FETCH_ALL,
                [
                    ':sessionid' => $session_id,
                    ':userid'    => $user_id
                ],
                [':userid' => DBConnection::PARAM_INT]
            );

            // session is not valid
            $size = count($session_info);
            if ($size !== 1)
            {
                throw new ClientSessionExpiredException(_h('Session not valid. Please sign in.'));
            }

            // Valid session found, get more user info
            $user_info = DBConnection::get()->query(
                "SELECT `user`, `role`
                FROM `" . DB_PREFIX . "users`
                WHERE `id` = :userid",
                DBConnection::FETCH_FIRST,
                [':userid' => $user_id],
                [':userid' => DBConnection::PARAM_INT]
            );

            // here an if statement will come for Guest and registered
            return new RegisteredClientSession(
                $session_info[0]["cid"],
                $session_info[0]["uid"],
                $user_info["user"]
            );

        }
        catch(DBException $e)
        {
            throw new ClientSessionException(
                _h('An error occurred while verifying session.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }
    }

    /**
     * Sets the public address of a player
     *
     * @param int    $id           user id
     * @param string $token        user token
     * @param int    $ip           user ip
     * @param int    $port         user port
     * @param int    $private_port the private port (for LANs and special NATs)
     *
     * @throws UserException if the request fails
     */
    public static function setPublicAddress($id, $token, $ip, $port, $private_port)
    {
        try
        {
            // Query the database to set the ip and port
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "client_sessions`
                SET `ip` = :ip , `port` = :port, `private_port` = :private_port
                WHERE `uid` = :userid AND `cid` = :token",
                DBConnection::ROW_COUNT,
                [
                    ':ip'           => $ip,
                    ':port'         => $port,
                    ':private_port' => $private_port,
                    ':userid'       => $id,
                    ':token'        => $token
                ],
                [
                    ':ip'           => DBConnection::PARAM_INT,
                    ':port'         => DBConnection::PARAM_INT,
                    ':private_port' => DBConnection::PARAM_INT,
                    ':userid'       => DBConnection::PARAM_INT,
                ]

            );
        }
        catch(DBException $e)
        {
            throw new UserException(_h('An error occurred while setting ip:port') . ' .' . _h('Please contact a website administrator.'));
        }

        // if count = 0 that may be a re-update of an existing key
        if ($count > 1)
        {
            throw new UserException(_h('Could not set the ip:port'));
        }
    }

    /**
     * Unset the public address of a user
     *
     * @param int    $id    user id
     * @param string $token user token
     *
     * @throws ClientSessionException if the request fails
     */
    public static function unsetPublicAddress($id, $token)
    {
        try
        {
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "client_sessions`
                SET `ip` = '0' , `port` = '0'
                WHERE `uid` = :userid AND `cid` = :token",
                DBConnection::ROW_COUNT,
                [
                    ':userid' => $id,
                    ':token'  => $token
                ],
                [':userid' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionException(_h('An error occurred while unsetting ip:port') . ' .' . _h(
                    'Please contact a website administrator.'
                ));
        }

        if ($count === 0)
        {
            throw new ClientSessionException(_h('ID:Token must be wrong.'));
        }
        elseif ($count > 1)
        {
            throw new ClientSessionException(_h('Weird count of updates'));
        }
    }

    /**
     * Hourly cron job for client sessions
     *
     * @param int $seconds how old should a normal session be, before deleting
     * @param int $old_seconds how old should a long session be, before deleting and updating
     *
     * @throws ClientSessionException
     */
    public static function cron($seconds, $old_seconds)
    {
        // delete old records
        // all records that are older than old_seconds
        // all records that are not 'remember me' and older than seconds
        try
        {
            DBConnection::get()->delete(
                "client_sessions",
                "((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`last-online`)) > :old_seconds)
                    OR
                 ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`last-online`)) > :seconds AND `save` = 0)
                ",
                [
                    ":seconds"     => $seconds,
                    ":old_seconds" => $old_seconds
                ],
                [
                    ":seconds"     => DBConnection::PARAM_INT,
                    ":old_seconds" => DBConnection::PARAM_INT
                ]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionException($e->getMessage());
        }

        // set offline all 'remember me' users older than seconds
        try
        {
            DBConnection::get()->update(
                "client_sessions",
                "((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`last-online`)) > :seconds AND `save` = 1)",
                [
                    ":seconds" => $seconds,
                    "online"   => 0
                ],
                [":seconds" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionException($e->getMessage());
        }
    }
}
