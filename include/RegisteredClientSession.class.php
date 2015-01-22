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
 * ClientSession implementation for registered users
 */
class RegisteredClientSession extends ClientSession
{
    /**
     * New instance
     *
     * @param string $session_id
     * @param int    $user_id
     * @param string $user_name
     */
    protected function __construct($session_id, $user_id, $user_name)
    {
        parent::__construct($session_id, $user_id, $user_name);
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
        return Friend::getFriendsAsXML($visiting_id, $this->user_id === $visiting_id);
    }

    /**
     * Create a server instance
     *
     * @param int    $ip
     * @param int    $port
     * @param int    $private_port
     * @param string $server_name
     * @param int    $max_players
     *
     * @return Server
     */
    public function createServer($ip, $port, $private_port, $server_name, $max_players)
    {
        parent::setPublicAddress($this->user_id, $this->session_id, $ip, $port, $private_port);

        return Server::create($ip, $port, $private_port, $this->user_id, $server_name, $max_players);
    }

    /**
     * Stop a server by deleting it from the database
     *
     * @param int $ip   the server ip
     * @param int $port the server port
     *
     * @throws UserException
     */
    public function stopServer($ip, $port)
    {
        try
        {
            // empty the public ip:port
            parent::setPublicAddress($this->user_id, $this->session_id, 0, 0, 0);

            // now setup the serv info
            $count = DBConnection::get()->query(
                "DELETE FROM `" . DB_PREFIX . "servers`
                WHERE `ip`= :ip AND `port`= :port AND `hostid`= :id",
                DBConnection::ROW_COUNT,
                [
                    ':ip'   => $ip,
                    ':port' => $port,
                    ':id'   => $this->user_id
                ],
                [
                    ':ip'   => DBConnection::PARAM_INT,
                    ':port' => DBConnection::PARAM_INT,
                    ':id'   => DBConnection::PARAM_INT
                ]
            );
        }
        catch(DBException $e)
        {
            throw new UserException(
                _h('An error occurred while ending a server.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        if ($count !== 1)
        {
            throw new UserException(_h('Not the good number of servers deleted.'));
        }
    }


    /**
     * A space separated string of names
     *
     * @return string
     */
    public function getOnlineFriends()
    {
        return implode(" ", Friend::getOnlineFriendsOf($this->user_id));
    }

    /**
     * Get all the usr notifications
     *
     * @return array
     * @throws FriendException
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
                [':to' => $this->user_id],
                [':to' => DBConnection::PARAM_INT]
            );
            DBConnection::get()->query(
                "DELETE FROM `" . DB_PREFIX . "notifications`
                WHERE `to` = :to",
                DBConnection::NOTHING,
                [':to' => $this->user_id],
                [':to' => DBConnection::PARAM_INT]
            );

            DBConnection::get()->commit();
        }
        catch(DBException $e)
        {
            DBConnection::get()->rollback();
            throw new FriendException(
                _h('An unexpected error occured while fetching new notifications.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        $result_array = [];
        $result_array['f_request'] = [];
        foreach ($result as $notification)
        {
            if ($notification['type'] == 'f_request')
            {
                $result_array['f_request'][] = $notification['from'];
            }
        }

        return $result_array;
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
     * Vote on a server
     *
     * @param int $host_id
     * @param int $vote
     *
     * @throws ClientSessionException
     */
    public function hostVote($host_id, $vote)
    {
        $vote = (int)$vote;
        if ($vote !== 1 || $vote !== -1)
        {
            throw new ClientSessionException(_h("Invalid vote. Your rating has to be either -1 or 1."));
        }
        try
        {
            DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "host_votes` (`userid`, `hostid`, `vote`)
                VALUES (:userid, :hostid, :vote)
                ON DUPLICATE KEY UPDATE `to` = :to",
                DBConnection::ROW_COUNT,
                [
                    ':hostid' => $host_id,
                    ':userid' => $this->user_id,
                    ':vote'   => $vote
                ],
                [
                    ':hostid' => DBConnection::PARAM_INT,
                    ':userid' => DBConnection::PARAM_INT,
                    ':vote'   => DBConnection::PARAM_INT
                ]
            );
        }
        catch(DBException $e)
        {
            throw new ClientSessionException(
                _h('An unexpected error occured while casting your host vote.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }
    }

    /**
     * @param int $achievement_id
     */
    public function onAchieving($achievement_id)
    {
        Achievement::achieve($this->user_id, $achievement_id);
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
            return implode(" ", Achievement::getAchievementsIdsOf($this->user_id));
        }

        return implode(" ", Achievement::getAchievementsIdsOf($id));
    }

    /**
     * Create session for registered user
     *
     * @param string $username username
     * @param string $password password (plain)
     * @param bool   $save_session
     *
     * @return RegisteredClientSession
     * @throws ClientSessionConnectException when credentials are wrong
     */
    public static function create($username, $password, $save_session)
    {
        // check if username/password is correct, throws exception
        try
        {
            $user = Validate::credentials($password, $username, Validate::CREDENTIAL_USERNAME);
        }
        catch(UserException $e)
        {
            throw new ClientSessionConnectException($e->getMessage());
        }

        try
        {
            $session_id = Util::getClientSessionId();
            $user_id = $user->getId();
            $username = $user->getUserName();
            $count = DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "client_sessions` (cid, uid, save, `last-online`)
                VALUES (:session_id, :user_id, :save, NOW())
                ON DUPLICATE KEY UPDATE cid = :session_id, online = 1",
                DBConnection::ROW_COUNT,
                [
                    ':session_id' => $session_id,
                    ':user_id'    => $user_id,
                    ':save'       => ($save_session ? 1 : 0),
                ],
                [':user_id' => DBConnection::PARAM_INT, ':save' => DBConnection::PARAM_INT]
            );
            if ($count > 2 || $count < 0)
            {
                throw new DBException();
            }
            User::updateLoginTime($user_id);

            return new RegisteredClientSession($session_id, $user_id, $username);
        }
        catch(DBException $e)
        {
            throw new ClientSessionConnectException(
                _h('An unexpected error occured while creating your session.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }
    }
}
