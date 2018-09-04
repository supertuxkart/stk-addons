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
    public function getFriendsOf(int $visiting_id)
    {
        return Friend::getFriendsAsXML($visiting_id, $this->user->getId() === $visiting_id);
    }

    /**
     * Create a server instance
     *
     * @param int       $ip
     * @param int       $port
     * @param int       $private_port
     * @param string    $server_name
     * @param int       $max_players
     * @param int       $difficulty
     * @param int       $game_mode
     * @param int       $password
     * @param int       $version
     *
     * @throws ServerException
     * @return Server
     */
    public function createServer(
        $ip,
        int $port,
        int $private_port,
        $server_name,
        int $max_players,
        int $difficulty,
        int $game_mode,
        int $password,
        int $version
    ) {
        return Server::create(
            $ip,
            $port,
            $private_port,
            $this->user->getId(),
            $server_name,
            $max_players,
            $difficulty,
            $game_mode,
            $password,
            $version
        );
    }

    /**
     * Stop a server by deleting it from the database
     *
     * @param int $ip   the server ip
     * @param int $port the server port
     *
     * @throws ServerException
     */
    public function stopServer($ip, int $port)
    {
        return Server::stop($ip, $port, $this->user->getId());
    }

    /**
     * A space separated string of names
     *
     * @throws ClientSessionException
     * @return string
     */
    public function getOnlineFriends()
    {
        try
        {
            return implode(" ", Friend::getOnlineFriendsOf($this->user->getId()));
        }
        catch (FriendException $e)
        {
            throw new ClientSessionException($e);
        }
    }

    /**
     * Return server names if any friends joined
     * @param array $friends list of friend
     * @throws ClientSessionException
     * @return array
     */
    public function getServersFriendsJoined($friends)
    {
        try
        {
            $query_parts = [];
            foreach ($friends as $friend)
                $query_parts[] = "`user_id` = " . $friend;
            $result = DBConnection::get()->query(
                "SELECT user_id, name FROM `{DB_VERSION}_server_conn`
                INNER JOIN `{DB_VERSION}_servers` ON
                `{DB_VERSION}_server_conn`.server_id = `{DB_VERSION}_servers`.id
                WHERE " . implode(" OR ", $query_parts),
                DBConnection::FETCH_ALL
            );
            return $result;
        }
        catch (DBException $e)
        {
            throw new ClientSessionException($e);
        }
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
                "SELECT `from`, `type` FROM `{DB_VERSION}_notifications`
                WHERE `to` = :to",
                DBConnection::FETCH_ALL,
                [':to' => $this->user->getId()],
                [':to' => DBConnection::PARAM_INT]
            );
            DBConnection::get()->query(
                "DELETE FROM `{DB_VERSION}_notifications`
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
     * Set the key to join server with client ip and port
     *
     * @param int $server_id id of the server
     * @param int $address ip of client
     * @param int $port port of client
     * @param string $aes_key aes 128 bit key of client in base64
     * @param string $aes_iv initialization vector of the aes key in base64
     *
     * @throws ClientSessionException if setting join key fails
     */
    public function setJoinServerKey(int $server_id, $address, int $port, string $aes_key, string $aes_iv)
    {
        try
        {
            Server::cleanOldServers();
            DBConnection::get()->query(
                "INSERT INTO `{DB_VERSION}_server_conn`
                (`user_id`, `server_id`, `ip`, `port`, `aes_key`, `aes_iv`, `connected_since`) VALUES
                (:user_id, :server_id, :ip, :port, :aes_key, :aes_iv, :connected_since)
                ON DUPLICATE KEY UPDATE `server_id` = :server_id,
                `ip` = :ip, `port`= :port, `aes_key` = :aes_key, `connected_since` = :connected_since",
                DBConnection::NOTHING,
                [
                    ':user_id'         => $this->user->getId(),
                    ':server_id'       => $server_id,
                    ':ip'              => $address,
                    ':port'            => $port,
                    ':aes_key'         => $aes_key,
                    ':aes_iv'          => $aes_iv,
                    ':connected_since' => time(),
                ],
                [
                    ':user_id'         => DBConnection::PARAM_INT,
                    ':server_id'       => DBConnection::PARAM_INT,
                    ':ip'              => DBConnection::PARAM_INT,
                    ':port'            => DBConnection::PARAM_INT,
                    ':aes_key'         => DBConnection::PARAM_STR,
                    ':aes_iv'          => DBConnection::PARAM_STR,
                    ':connected_since' => DBConnection::PARAM_INT
                ]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException(exception_message_db(_('set join server key.')));
        }
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
     * @param int    $ip
     * @param int    $port
     * @param int    $current_players
     * @param int    $game_started
     *
     * @return array
     * @throws ClientSessionException
     */
    public function getServerConnectionRequests($ip, int $port, int $current_players, int $game_started)
    {
        try
        {
            $server_id = DBConnection::get()->query(
                "SELECT `id` FROM `{DB_VERSION}_servers`
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
                "UPDATE `{DB_VERSION}_servers`
                SET `last_poll_time` = :new_time, `current_players` = :current_players, `game_started` = :game_started
                WHERE `id` = :server_id",
                DBConnection::NOTHING,
                [
                    ':server_id'       => $server_id['id'],
                    ':new_time'        => time(),
                    ':current_players' => $current_players,
                    ':game_started'    => $game_started,
                ],
                [
                    ':server_id'       => DBConnection::PARAM_INT,
                    ':new_time'        => DBConnection::PARAM_INT,
                    ':current_players' => DBConnection::PARAM_INT,
                    ':game_started'    => DBConnection::PARAM_INT
                ]
            );

            // Get all connection requests 45 seconds before
            $timeout = time() - 45;
            $connection_requests = DBConnection::get()->query(
                "SELECT `user_id`, `server_id`, `ip`, `port`, `aes_key`, `aes_iv`, `username`
                FROM `{DB_VERSION}_server_conn`
                INNER JOIN `{DB_VERSION}_users`
                ON `{DB_VERSION}_server_conn`.user_id = `{DB_VERSION}_users`.id
                WHERE `server_id` = :server_id AND `connected_since` > :timeout",
                DBConnection::FETCH_ALL,
                [
                    ':server_id' => $server_id['id'],
                    ':timeout'   => $timeout
                ],
                [
                    ':server_id' => DBConnection::PARAM_INT,
                    ':timeout'   => DBConnection::PARAM_INT
                ]
            );

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
        $online_friends = Friend::getOnlineFriendsOf($this->user->getId());
        $notifications = $this->getNotifications();

        $partial_output = new XMLOutput();
        $partial_output->startElement('poll');
        $partial_output->writeAttribute('success', 'yes');
        $partial_output->writeAttribute('info', '');

        if ($online_friends)
        {
            $partial_output->writeAttribute('online', implode(" ", $online_friends));
        }

        if (!empty($notifications['f_request']))
        {
            foreach ($notifications['f_request'] as $requester_id)
            {
                $partial_output->insert(User::getFromID($requester_id)->asXML('new_friend_request'));
            }
        }

        // For 0.9.4 usage, return the server names if any friends joined
        if ($online_friends)
        {
            $server_list = $this->getServersFriendsJoined($online_friends);
            if ($server_list)
            {
                foreach ($server_list as $server)
                {
                    $partial_output->startElement('friend-in-server');
                        $partial_output->writeAttribute("id", $server["user_id"]);
                        $partial_output->writeAttribute("name", $server["name"]);
                    $partial_output->endElement();
                }
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
                "DELETE FROM `{DB_VERSION}_client_sessions`
    	        WHERE `cid` = :session_id AND uid = :user_id",
                DBConnection::ROW_COUNT,
                [
                    ':user_id'    => $this->user->getId(),
                    ':session_id' => $this->session_id
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
            $this->clearUserJoinedServer();
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
                "SELECT `is_save` FROM `{DB_VERSION}_client_sessions`
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
                    $this->clearUserJoinedServer();
                    $this->setOnline(false);
                }
                else
                {
                    // destroy will clear user joined server too
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
     * Vote on a server,
     *
     * @param int $host_id
     * @param int $vote
     *
     * @return int
     * @throws ClientSessionException
     */
    public function setHostVote(int $host_id, int $vote)
    {
        if ($vote !== 1 && $vote !== -1)
        {
            throw new ClientSessionException(_h("Invalid vote. Your rating has to be either -1 or 1."));
        }

        try
        {
            // TODO find out if host_id is a server or user
            DBConnection::get()->query(
                "INSERT INTO `{DB_VERSION}_host_votes` (`user_id`, `host_id`, `vote`)
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
    public function setOnline(bool $online = true)
    {
        try
        {
            // sometimes the MYSQL 'ON UPDATE' does not fire because the online value is the same
            DBConnection::get()->query(
                "UPDATE `{DB_VERSION}_client_sessions`
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
     * Get session object for already created session
     *
     * @param string $session_id session id
     * @param int    $user_id    user id
     *
     * @return ClientSession
     * @throws ClientSessionExpiredException|ClientSessionException when session does not exist
     */
    public static function get(string $session_id, int $user_id)
    {
        try
        {
            $session_info = DBConnection::get()->query(
                "SELECT * FROM `{DB_VERSION}_client_sessions`
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
                "INSERT INTO `{DB_VERSION}_client_sessions` (cid, uid, is_save, `last-online`)
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

            // Remove all server connection that is not currently online
            DBConnection::get()->query(
                "DELETE FROM `{DB_VERSION}_server_conn`
                WHERE `user_id` NOT IN
                (SELECT `uid` FROM `{DB_VERSION}_client_sessions`
                WHERE `is_online` = 1)"
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException($e->getMessage());
        }
    }

    /**
     * Clear the specific joined server of the current user id, called when a client leave wan server in STK
     *     *
     * @throws ClientSessionException
     */
    public function clearUserJoinedServer()
    {
        try
        {
            DBConnection::get()->delete(
                "server_conn",
                "`user_id` = :user_id",
                [":user_id" => $this->user->getId()],
                [":user_id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new ClientSessionException($e->getMessage());
        }
    }
}
