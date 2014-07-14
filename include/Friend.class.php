<?php
/**
 * copyright 2013 Glenn De Jonghe
 *
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
 * Server class
 */
class Friend
{
    /**
     * @var int
     */
    protected $friend_id;

    /**
     * @var string
     */
    protected $date;

    /**
     * @var bool
     */
    protected $is_pending = false;

    /**
     * @var bool
     */
    protected $is_online = false;

    /**
     * @var bool
     */
    protected $is_asker = false;

    /**
     * We are the logged in user
     * @var bool
     */
    protected $is_self = false;

    /**
     * @var User
     */
    protected $user;

    /**
     * The Friend constructor
     *
     * @param array $info_array an associative array based on the database
     * @param bool  $online     is the user online
     * @param bool  $is_self initialize extra info
     */
    protected function __construct($info_array, $online = false, $is_self = false)
    {
        $this->user = new User($info_array['friend_id'], ["user" => $info_array['friend_name']]);
        $this->is_self = $is_self;
        $this->is_online = $online;
        $this->date = $info_array['date'];

        if ($is_self) // we are logged in
        {
            $this->is_pending = ((int)$info_array['request'] === 1);
            $this->is_asker = ((int)$info_array['is_asker'] === 1);
        }
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return bool
     */
    public function isOnline()
    {
        return $this->is_online;
    }

    /**
     * @return bool
     */
    public function isAsker()
    {
        return $this->is_asker;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return $this->is_pending;
    }

    /**
     * Get the friend as an xml string
     *
     * @return string
     */
    public function asXML()
    {
        $friend_xml = new XMLOutput();
        $friend_xml->startElement('friend');

        if ($this->is_self)
        {
            $friend_xml->writeAttribute("is_pending", ($this->is_pending ? "yes" : "no"));
            if ($this->is_pending)
            {
                $friend_xml->writeAttribute("is_asker", ($this->is_asker ? "yes" : "no"));
            }
            else
            {
                $friend_xml->writeAttribute("online", ($this->is_online ? "yes" : "no"));
            }
            $friend_xml->writeAttribute("date", $this->date);
        }

        $friend_xml->insert($this->user->asXML());
        $friend_xml->endElement();

        return $friend_xml->asString();
    }

    /**
     * Get a space separated string of friend id's
     *
     * @param $userid
     *
     * @return int[] array of friend id's
     * @throws FriendException
     */
    public static function getOnlineFriendsOf($userid)
    {
        try
        {
            $friends = DBConnection::get()->query(
                "   SELECT " . DB_PREFIX . "friends.asker_id AS friend_id
                    FROM " . DB_PREFIX . "friends, " . DB_PREFIX . "client_sessions
                    WHERE   " . DB_PREFIX . "friends.receiver_id = :userid
                        AND " . DB_PREFIX . "friends.request = 0
                        AND " . DB_PREFIX . "client_sessions.uid = " . DB_PREFIX . "friends.asker_id
                        AND " . DB_PREFIX . "client_sessions.online = 1
                UNION
                    SELECT " . DB_PREFIX . "friends.receiver_id AS friend_id
                    FROM " . DB_PREFIX . "friends, " . DB_PREFIX . "client_sessions
                    WHERE   " . DB_PREFIX . "friends.asker_id = :userid
                        AND " . DB_PREFIX . "friends.request = 0
                        AND " . DB_PREFIX . "client_sessions.uid = " . DB_PREFIX . "friends.receiver_id
                        AND " . DB_PREFIX . "client_sessions.online = 1",
                DBConnection::FETCH_ALL,
                [":userid" => $userid],
                [":userid" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new FriendException(
                _('An unexpected error occured while fetching online friends.') . ' ' .
                _('Please contact a website administrator.')
            );
        }

        // build array of ids
        $return_friends = [];
        foreach ($friends as $friend)
        {
            $return_friends[] = $friend['friend_id'];
        }

        return $return_friends;
    }

    /**
     * Return all the friends of a user
     *
     * @param int  $userid
     * @param bool $is_self
     * @param bool $return_instance
     *
     * @throws FriendException
     * @return Friend[]|array an array of friends
     */
    public static function getFriendsOf($userid, $is_self = false, $return_instance = true)
    {
        // TODO clean up the look of the SQL queries
        try
        {
            if ($is_self) // get all users if we are the logged in user
            {
                $friends = DBConnection::get()->query(
                    "   SELECT " . DB_PREFIX . "friends.date AS date, "
                                 . DB_PREFIX . "friends.request AS request, "
                                 . DB_PREFIX . "friends.asker_id AS friend_id, "
                                 . DB_PREFIX . "users.user AS friend_name, 1 AS is_asker
                        FROM " . DB_PREFIX . "friends, " . DB_PREFIX . "users
                        WHERE   " . DB_PREFIX . "friends.receiver_id = :userid
                            AND " . DB_PREFIX . "users.id = " . DB_PREFIX . "friends.asker_id
                    UNION
                        SELECT " . DB_PREFIX . "friends.date AS date, "
                                 . DB_PREFIX . "friends.request AS request, "
                                 . DB_PREFIX . "friends.receiver_id AS friend_id, "
                                 . DB_PREFIX . "users.user AS friend_name, 0 AS is_asker
                        FROM " . DB_PREFIX . "friends, " . DB_PREFIX . "users
                        WHERE " . DB_PREFIX . "friends.asker_id = :userid
                            AND " . DB_PREFIX . "users.id = " . DB_PREFIX . "friends.receiver_id
                        ORDER BY date ASC",
                    DBConnection::FETCH_ALL,
                    [":userid" => $userid],
                    [":userid" => DBConnection::PARAM_INT]
                );
            }
            else // get only the accepted friends, viewing other user profile
            {
                $friends = DBConnection::get()->query(
                    "   SELECT " . DB_PREFIX . "friends.date AS date, "
                                 . DB_PREFIX . "friends.asker_id AS friend_id, "
                                 . DB_PREFIX . "users.user AS friend_name
                        FROM " . DB_PREFIX . "friends, " . DB_PREFIX . "users
                        WHERE   " . DB_PREFIX . "friends.receiver_id = :userid
                            AND " . DB_PREFIX . "users.id = " . DB_PREFIX . "friends.asker_id
                            AND " . DB_PREFIX . "friends.request = 0
                    UNION
                        SELECT " . DB_PREFIX . "friends.date AS date, "
                                 . DB_PREFIX . "friends.receiver_id AS friend_id, "
                                 . DB_PREFIX . "users.user AS friend_name
                        FROM " . DB_PREFIX . "friends, " . DB_PREFIX . "users
                        WHERE   " . DB_PREFIX . "friends.asker_id = :userid
                            AND " . DB_PREFIX . "users.id = " . DB_PREFIX . "friends.receiver_id
                            AND " . DB_PREFIX . "friends.request = 0
                        ORDER BY date ASC",
                    DBConnection::FETCH_ALL,
                    [":userid" => $userid],
                    [":userid" => DBConnection::PARAM_INT]
                );
            }
        }
        catch(DBException $e)
        {
            throw new FriendException(h(
                _('An unexpected error occurred while fetching friends.') . ' .' .
                _('Please contact a website administrator.')
            ));
        }

        // build friends array
        $return_friends = [];
        if($return_instance)
        {
            foreach ($friends as $friend)
            {
                /// TODO also get online status
                $return_friends[] = new Friend($friend, false, $is_self);
            }
        }
        else
        {
            $return_friends = $friends;
        }


        return $return_friends;
    }

    /**
     * Returns XML string of all friends
     *
     * @param int  $userid
     * @param bool $is_self
     *
     * @throws FriendException
     * @return string
     */
    public static function getFriendsAsXML($userid, $is_self = false)
    {
        $friends = static::getFriendsOf($userid, $is_self);

        $partial_output = new XMLOutput();
        $partial_output->startElement('friends');
        foreach ($friends as $friend)
        {
            $partial_output->insert($friend->asXML());
        }
        $partial_output->endElement();

        return $partial_output->asString();
    }

    /**
     * Send a friend request to a user
     *
     * @param int $asker_id
     * @param int $friend_id the id of the user we want to be friends with
     *
     * @throws FriendException
     */
    public static function friendRequest($asker_id, $friend_id)
    {
        if ($friend_id == $asker_id)
        {
            throw new FriendException(_h('You cannot ask yourself to be your friend!'));
        }

        try
        {
            DBConnection::get()->beginTransaction();

            // see if request already exists
            $result = DBConnection::get()->query(
                "SELECT asker_id, receiver_id FROM `" . DB_PREFIX . "friends`
                WHERE (asker_id = :asker AND receiver_id = :receiver)
                OR (asker_id = :receiver AND receiver_id = :asker)",
                DBConnection::FETCH_FIRST,
                [
                    ':asker'    => $asker_id,
                    ':receiver' => $friend_id
                ],
                [':asker' => DBConnection::PARAM_INT, ':receiver' => DBConnection::PARAM_INT]
            );

            if ($result) // request already exists, maybe by the asker or by the friend
            {
                DBConnection::get()->commit();
                if ($result['asker_id'] == $asker_id)
                {
                    // The request was already in there by the asker, should not be possible normally
                    // ignore it but log this! FIXME
                }
                else
                {
                    // The friend already did a friend request! interpret as accepting the friend request!
                    static::acceptFriendRequest($friend_id, $asker_id);
                }
            }
            else
            {
                // add friend request
                DBConnection::get()->query(
                    "INSERT INTO `" . DB_PREFIX . "friends` (asker_id, receiver_id, date)
                    VALUES (:asker, :receiver, CURRENT_DATE())
                    ON DUPLICATE KEY UPDATE asker_id = :asker",
                    DBConnection::ROW_COUNT,
                    [
                        ':asker'    => $asker_id,
                        ':receiver' => $friend_id
                    ],
                    [':asker' => DBConnection::PARAM_INT, ':receiver' => DBConnection::PARAM_INT]
                );

                // add notification
                DBConnection::get()->query(
                    "INSERT INTO `" . DB_PREFIX . "notifications` (`to`, `from`, `type`)
                    VALUES (:to, :from, 'f_request')
                    ON DUPLICATE KEY UPDATE `to` = :to",
                    DBConnection::ROW_COUNT,
                    [
                        ':to'   => $friend_id,
                        ':from' => $asker_id
                    ],
                    [':to' => DBConnection::PARAM_INT, ':from' => DBConnection::PARAM_INT]
                );

                DBConnection::get()->commit();
            }
        }
        catch(DBException $e)
        {
            DBConnection::get()->rollback();
            throw new FriendException(
                _h('An unexpected error occured while adding your friend request.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }
    }

    /**
     * Accept a friend request
     *
     * @param int $friend_id the user who sent the friend request, usually the friend
     * @param int $receiver_id the user who accepts the friend request, usually the logged in user
     *
     * @throws FriendException
     */
    public static function acceptFriendRequest($friend_id, $receiver_id)
    {
        try
        {
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "friends`
                SET request = 0
                WHERE asker_id = :asker AND receiver_id = :receiver",
                DBConnection::ROW_COUNT,
                [
                    ':asker'    => $friend_id,
                    ':receiver' => $receiver_id
                ],
                [':asker' => DBConnection::PARAM_INT, ':receiver' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new FriendException(
                _h('An unexpected error occured while accepting a friend request.') . ' ' .
                _h('Please contact a website administrator.')
            );
        }
    }

    /**
     * Remove a friend by deleting the record from the database.
     * This is used bu the decline and cancel methods.
     *
     * @param int $asker_id the asker
     * @param int $receiver_id the receiver
     * @param string $message_error
     *
     * @return int the number of affected rows
     * @throws FriendException
     */
    protected static function removeFriendRecord($asker_id, $receiver_id, $message_error)
    {
        try
        {
            $count = DBConnection::get()->query(
                "DELETE FROM `" . DB_PREFIX . "friends`
                WHERE asker_id = :asker AND receiver_id = :receiver",
                DBConnection::ROW_COUNT,
                [
                    ':asker'    => $asker_id,
                    ':receiver' => $receiver_id
                ],
                [':asker' => DBConnection::PARAM_INT, ':receiver' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new FriendException(
                $message_error . ' ' .
                _h('Please contact a website administrator.')
            );
        }

        return $count;
    }

    /**
     * Decline a friend request by deleting the record. We are the receiver
     *
     * @param int $friend_id the user who sent the friend request
     * @param int $receiver_id the user who declines the friend request
     *
     * @throws FriendException
     */
    public static function declineFriendRequest($friend_id, $receiver_id)
    {
        static::removeFriendRecord($friend_id, $receiver_id, _h('An unexpected error occurred while declining a friend request.'));
    }

    /**
     * Cancel a friend request by deleting the record. The logged in user is the asker
     *
     * @param int $asker_id the user who sent the friend request, usually the logged user
     * @param int $friend_id the user who was asked by the logged in user
     *
     * @throws FriendException
     */
    public static function cancelFriendRequest($asker_id, $friend_id)
    {
        static::removeFriendRecord($asker_id, $friend_id, _h('An unexpected error occurred while cancelling your friend request.'));
    }

    /**
     * Remove a friend from the database. The order does not matter if the order is correct.
     * As long as the 2 ids exists. remove all connections between the 2
     *
     * @param int $user1_id
     * @param int $user2_id
     */
    public static function removeFriend($user1_id, $user2_id)
    {
        static::cancelFriendRequest($user1_id, $user2_id);
        static::declineFriendRequest($user2_id, $user1_id);
    }
}
