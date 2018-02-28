<?php
/**
 * copyright 2013      Glenn De Jonghe
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Handle all the STK client sessions
 */
class ClientSession
{
    /**
     * @var string
     */
    protected $session_id;

    /**
     * @var User
     */
    protected $user;

    /**
     * @param string $session_id
     * @param User   $user
     */
    protected function __construct($session_id, User $user)
    {
        // TODO store also the table data
        $this->session_id = $session_id;
        $this->user = $user;
    }

    /**
     * @return \User
     */
    public function getUser()
    {
        return $this->user;
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
     * Get the friends as an xml string
     *
     * @param int $visiting_id
     *
     * @return string and xml string
     */
    public function getFriendsOf($visiting_id)
    {
        return Friend::getFriendsAsXML($visiting_id, $this->user->getId() === $visiting_id);
    }

    /**
     * Create a server instance
     *
     * @param int    $ip
     * @param int    $port
     * @param int    $private_port
     * @param string $server_name
     * @param int    $max_players
     * @param int    $difficulty
     * @param int    $game_mode
     *
     * @return Server
     */
    public function createServer($ip, $port, $private_port, $server_name,
        $max_players, $difficulty, $game_mode)
    {
        $this->setPublicAddress($ip, $port, $private_port);

        return Server::create($ip, $port, $private_port, $this->user->getId(),
            $server_name, $max_players, $difficulty, $game_mode);
    }

    /**
     * Stop a server by deleting it from the database
     *
     * @param int $ip   the server ip
     * @param int $port the server port
     *
     * @throws ClientSessionException
     */
    public function stopServer($ip, $port)
    {
        try
        {
            // empty the public ip:port
            $this->setPublicAddress(0, 0, 0);

            // now setup the serv info
            $count = DBConnection::get()->query(
                "DELETE FROM `" . DB_PREFIX . "servers`
                WHERE `ip`= :ip AND `port`= :port AND `host_id`= :id",
                DBConnection::ROW_COUNT,
                [
                    ':ip'   => $ip,
                    ':port' => $port,
                    ':id'   => $this->user->getId()
                ],
                [
                    ':ip'   => DBConnection::PARAM_INT,
                    ':port' => DBConnection::PARAM_INT,
                    ':id'   => DBConnection::PARAM_INT
                ]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('stop a server')));
        }

        if ($count !== 1)
        {
            throw new ClientSessionException(_h('Not the good number of servers deleted.'));
        }
    }


    /**
     * A space separated string of names
     *
     * @return string
     */
    public function getOnlineFriends()
    {
        return implode(" ", Friend::getOnlineFriendsOf($this->user->getId()));
    }

    /**
     * Get all the user notifications
     *
     * @return array
     * @throws ClientSessionException
     */
    public function getNotifications()
    {
        try
        {
            DBConnection::get()->beginTransaction();

            $result = DBConnection::get()->query(
                "SELECT `from`, `type` FROM `" . DB_PREFIX . "notifications`
                WHERE `to` = :to",
                DBConnection::FETCH_ALL,
                [':to' => $this->user->getId()],
                [':to' => DBConnection::PARAM_INT]
            );
            DBConnection::get()->query(
                "DELETE FROM `" . DB_PREFIX . "notifications`
                WHERE `to` = :to",
                DBConnection::NOTHING,
                [':to' => $this->user->getId()],
                [':to' => DBConnection::PARAM_INT]
            );

            DBConnection::get()->commit();
        }
        catch (DBException $e)
        {
            DBConnection::get()->rollback();
            throw new ClientSessionException(exception_message_db(_('fetch notifications')));
        }

        $result_array = [];
        $result_array['f_request'] = [];
        foreach ($result as $notification)
        {
            if ($notification['type'] === 'f_request')
            {
                $result_array['f_request'][] = $notification['from'];
            }
        }

        return $result_array;
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
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('get a peer\'s public address.')));
        }

        $size = count($result);
        if ($size === 0)
        {
            throw new ClientSessionException(_h('That user is not signed in.'));
        }
        elseif ($size > 1)
        {
            throw new ClientSessionException(_h('Too many users match the request'));
        }

        return $result[0];
    }

    /**
     * @param int $id
     *
     * @return string
     */
    public function getAchievements($id = 0)
    {
        if ($id == 0)
        {
            return implode(" ", Achievement::getAchievementsIdsOf($this->user->getId()));
        }

        return implode(" ", Achievement::getAchievementsIdsOf($id));
    }

    /**
     * @param string $ip
     * @param int    $port
     *
     * @return array
     * @throws ClientSessionException
     */
    public function getServerConnectionRequests($ip, $port, $current_players)
    {
        try
        {
            $server_id = DBConnection::get()->query(
                "SELECT `id` FROM `" . DB_PREFIX . "servers`
                WHERE `host_id` = :host_id AND `ip` = :ip AND `port` = :port LIMIT 1",
                DBConnection::FETCH_FIRST,
                [
                    ':host_id' => $this->user->getId(),
                    ':ip'      => $ip,
                    ':port'    => $port
                ],
                [
                    ':host_id' => DBConnection::PARAM_INT,
                    ':ip'      => DBConnection::PARAM_INT,
                    ':port'    => DBConnection::PARAM_INT
                ]
            );
            if (!$server_id)
            {
                return [];
            }

            // Update this server info (atm last poll and and current players joined)
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "servers`
                SET `last_poll_time` = :new_time, `current_players` = :current_players
                WHERE `id` = :server_id",
                DBConnection::NOTHING,
                [
                    ':server_id'       => $server_id['id'],
                    ':new_time'        => time(),
                    ':current_players' => $current_players
                ],
                [
                    ':server_id'       => DBConnection::PARAM_INT,
                    ':new_time'        => DBConnection::PARAM_INT,
                    ':current_players' => DBConnection::PARAM_INT
                ]);

            $connection_requests = DBConnection::get()->query(
                "SELECT s.user_id, c.ip, c.private_port, c.port
                FROM `" . DB_PREFIX . "server_conn` s
                INNER JOIN `" . DB_PREFIX . "client_sessions` c ON c.uid = s.user_id
                WHERE s.`server_id` = :server_id AND s.`is_request` = '1'",
                DBConnection::FETCH_ALL,
                [':server_id' => $server_id['id']],
                [':server_id' => DBConnection::PARAM_INT]
            );

            // Set the request bit to zero for all users we fetch
            $index = 0;
            $parameters = [];
            $query_parts = [];
            foreach ($connection_requests as $user)
            {
                $parameter = ":user_id" . $index;
                $index++;
                $query_parts[] = "`user_id` = " . $parameter;
                $parameters[$parameter] = $user['user_id'];
            }

            if ($index > 0)
            {
                DBConnection::get()->query(
                    "UPDATE `" . DB_PREFIX . "server_conn`
                    SET `is_request` = 0
                    WHERE " . implode(" OR ", $query_parts),
                    DBConnection::ROW_COUNT,
                    $parameters
                );
                // TODO Perhaps check if $count and $index are equal
            }
        }
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('fetch server connection requests')));
        }

        return $connection_requests;
    }

    /**
     * Poll the server for friends and notifications
     *
     * @return string
     */
    public function poll()
    {
        $this->setOnline();
        $online_friends = $this->getOnlineFriends();
        $notifications = $this->getNotifications();

        $partial_output = new XMLOutput();
        $partial_output->startElement('poll');
        $partial_output->writeAttribute('success', 'yes');
        $partial_output->writeAttribute('info', '');

        if ($online_friends)
        {
            $partial_output->writeAttribute('online', $online_friends);
        }

        if (!empty($notifications['f_request']))
        {
            foreach ($notifications['f_request'] as $requester_id)
            {
                $partial_output->insert(User::getFromID($requester_id)->asXML('new_friend_request'));
            }
        }
        $partial_output->endElement();

        return $partial_output->asString();
    }


    /**
     * @param int $achievement_id
     */
    public function onAchieving($achievement_id)
    {
        Achievement::achieve($this->user->getId(), $achievement_id);
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
                    ':user_id'    => $this->user->getId(),
                    ':session_id' => $this->session_id
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionExpiredException(exception_message_db(_('to sign out')));
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
                "SELECT `is_save` FROM `" . DB_PREFIX . "client_sessions`
    	        WHERE `cid` = :session_id AND uid = :user_id",
                DBConnection::FETCH_FIRST,
                [
                    ':user_id'    => $this->user->getId(),
                    ':session_id' => $this->session_id
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );

            if ($client)
            {
                if ($client['is_save'] == 1)
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
        catch (DBException $e)
        {
            throw new ClientSessionExpiredException(exception_message_db(_('log out')));
        }
    }


    /**
     * Create a server connection
     *
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
                "INSERT INTO `" . DB_PREFIX . "server_conn` (server_id, user_id, is_request)
                VALUES (:server_id, :user_id, 1)
                ON DUPLICATE KEY 
                UPDATE is_request = '1', server_id = :server_id",
                DBConnection::ROW_COUNT,
                [
                    ':user_id'   => $this->user->getId(),
                    ':server_id' => $server_id
                ],
                [':user_id' => DBConnection::PARAM_INT, ':server_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionConnectException(exception_message_db(_('request a server connection')));
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
                "SELECT `id`, `host_id`, `ip`, `port`, `private_port`
                FROM `" . DB_PREFIX . "servers`
                LIMIT 1",
                DBConnection::FETCH_FIRST
            );

            if (!$server)
            {
                throw new ClientSessionConnectException(_h('No server found'));
            }

            DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "server_conn` (server_id, user_id, is_request)
                VALUES (:server_id, :user_id, 1)
                ON DUPLICATE KEY UPDATE is_request = '1'",
                DBConnection::NOTHING,
                [
                    ':user_id'   => $this->user->getId(),
                    ':server_id' => $server['id']
                ],
                [':user_id' => DBConnection::PARAM_INT, ':server_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionConnectException(exception_message_db(_('quick join a server')));
        }

        return $server;
    }


    /**
     * Vote on a server,
     *
     * @param int $host_id
     * @param int $vote
     *
     * @return int
     * @throws ClientSessionException
     */
    public function setHostVote($host_id, $vote)
    {
        $vote = (int)$vote;
        if ($vote !== 1 || $vote !== -1)
        {
            throw new ClientSessionException(_h("Invalid vote. Your rating has to be either -1 or 1."));
        }
        try
        {
            // TODO find out if host_id is a server or user
            DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "host_votes` (`user_id`, `host_id`, `vote`)
                VALUES (:user_id, :host_id, :vote)
                ON DUPLICATE KEY UPDATE `vote` = :vote",
                DBConnection::ROW_COUNT,
                [
                    ':host_id' => $host_id,
                    ':user_id' => $this->user->getId(),
                    ':vote'    => $vote
                ],
                [
                    ':host_id' => DBConnection::PARAM_INT,
                    ':user_id' => DBConnection::PARAM_INT,
                    ':vote'    => DBConnection::PARAM_INT
                ]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('cast your host vote')));
        }

        // TODO fix server host votes
        return 0;
    }

    /**
     * Set the current user online
     *
     * @param bool $online
     *
     * @throws ClientSessionException
     * @return ClientSession
     */
    public function setOnline($online = true)
    {
        try
        {
            // sometimes the MYSQL 'ON UPDATE' does not fire because the online value is the same
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "client_sessions`
                SET `is_online` = :is_online, `last-online` = NOW()
                WHERE `uid` = :id",
                DBConnection::ROW_COUNT,
                [
                    ':id'        => $this->user->getId(),
                    ':is_online' => ($online ? 1 : 0),

                ],
                [
                    ':id'        => DBConnection::PARAM_INT,
                    ':is_online' => DBConnection::PARAM_BOOL
                ]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('update your status')));
        }

        return $this;
    }

    /**
     * Sets the public address of a player
     *
     * @param int $ip           user ip
     * @param int $port         user port
     * @param int $private_port the private port (for LANs and special NATs)
     *
     * @throws ClientSessionException if the request fails
     * @return ClientSession
     */
    public function setPublicAddress($ip, $port, $private_port)
    {
        try
        {
            // Query the database to set the ip and port
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "client_sessions`
                SET `ip` = :ip , `port` = :port, `private_port` = :private_port
                WHERE `uid` = :user_id AND `cid` = :token",
                DBConnection::ROW_COUNT,
                [
                    ':ip'           => $ip,
                    ':port'         => $port,
                    ':private_port' => $private_port,
                    ':user_id'      => $this->user->getId(),
                    ':token'        => $this->session_id
                ],
                [
                    ':ip'           => DBConnection::PARAM_INT,
                    ':port'         => DBConnection::PARAM_INT,
                    ':private_port' => DBConnection::PARAM_INT,
                    ':user_id'      => DBConnection::PARAM_INT,
                ]

            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('set your public address')));
        }

        // if count = 0 that may be a re-update of an existing key
        if ($count > 1)
        {
            throw new ClientSessionException(_h('Could not set the ip:port'));
        }

        return $this;
    }

    /**
     * Unset the public address of a user
     *
     * @throws ClientSessionException if the request fails
     */
    public function unsetPublicAddress()
    {
        try
        {
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "client_sessions`
                SET `ip` = '0' , `port` = '0'
                WHERE `uid` = :user_id AND `cid` = :token",
                DBConnection::ROW_COUNT,
                [
                    ':user_id' => $this->user->getId(),
                    ':token'   => $this->session_id
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('unset your public address')));
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
     * Get session object for already created session
     *
     * @param string $session_id session id
     * @param int    $user_id    user id
     *
     * @return ClientSession
     * @throws ClientSessionExpiredException|ClientSessionException when session does not exist
     */
    public static function get($session_id, $user_id)
    {
        try
        {
            $session_info = DBConnection::get()->query(
                "SELECT * FROM `" . DB_PREFIX . "client_sessions`
                WHERE cid = :sessionid AND uid = :user_id",
                DBConnection::FETCH_ALL,
                [
                    ':sessionid' => $session_id,
                    ':user_id'   => $user_id
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('verify session')));
        }

        // session is not valid
        $size = count($session_info);
        if ($size !== 1)
        {
            throw new ClientSessionExpiredException(_h('Session not valid. Please sign in.'));
        }

        // here an if statement will come for Guest and registered
        return new static($session_info[0]["cid"], User::getFromID($user_id));
    }

    /**
     * Create session for registered user
     *
     * @param string $username username
     * @param string $password password (plain)
     * @param bool   $save_session
     *
     * @return ClientSession
     * @throws InvalidArgumentException when username is not provided
     * @throws ClientSessionConnectException when credentials are wrong
     */
    public static function create($username, $password, $save_session)
    {
        if (!$username)
        {
            throw new InvalidArgumentException(_h('Username required'));
        }
        if (!$password)
        {
            throw new InvalidArgumentException(_h('Password required'));
        }

        // check if username/password is correct, throws exception
        try
        {
            $user = User::validateCredentials($password, $username, User::CREDENTIAL_USERNAME, true);
        }
        catch (UserException $e)
        {
            throw new ClientSessionConnectException($e->getMessage());
        }

        try
        {
            $session_id = Util::getClientSessionId();
            $user_id = $user->getId();
            $count = DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "client_sessions` (cid, uid, is_save, `last-online`)
                VALUES (:session_id, :user_id, :is_save, NOW())
                ON DUPLICATE KEY UPDATE cid = :session_id, is_online = 1",
                DBConnection::ROW_COUNT,
                [
                    ':session_id' => $session_id,
                    ':user_id'    => $user_id,
                    ':is_save'    => ($save_session ? 1 : 0),
                ],
                [
                    ':user_id' => DBConnection::PARAM_INT,
                    ':is_save' => DBConnection::PARAM_BOOL
                ]
            );
            if ($count > 2 || $count < 0)
            {
                throw new DBException();
            }
            User::updateLoginTime($user_id);
        }
        catch (DBException $e)
        {
            throw new ClientSessionConnectException(exception_message_db(_('create your session')));
        }

        return new static($session_id, $user);
    }

    /**
     * Hourly cron job for client sessions
     *
     * @param int $seconds     how old should a normal session be, before deleting
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
                 ((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`last-online`)) > :seconds AND `is_save` = 0)
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
        catch (DBException $e)
        {
            throw new ClientSessionException($e->getMessage());
        }

        // set offline all 'remember me' users older than seconds
        try
        {
            DBConnection::get()->update(
                "client_sessions",
                "((UNIX_TIMESTAMP() - UNIX_TIMESTAMP(`last-online`)) > :seconds AND `is_save` = 1)",
                [
                    ":seconds"  => $seconds,
                    "is_online" => 0
                ],
                [":seconds" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException($e->getMessage());
        }
    }
}
