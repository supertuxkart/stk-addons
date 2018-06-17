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
 * Friend class
 */
class Friend implements IAsXML
{
    /**
     * @var int
     */
    private $friend_id;

    /**
     * @var string
     */
    private $date;

    /**
     * @var bool
     */
    private $is_pending = false;

    /**
     * @var bool
     */
    private $is_online = false;

    /**
     * @var bool
     */
    private $is_asker = false;

    /**
     * We are the logged in user
     * @var bool
     */
    private $is_self = false;

    /**
     * The user instance associated with the friend
     * @var User
     */
    private $user;

    /**
     * The Friend constructor
     *
     * @param array $info_array an associative array based on the database
     * @param bool  $online     is the user online
     * @param bool  $is_self    initialize extra info
     */
    private function __construct($info_array, $online = false, $is_self = false)
    {
        $this->user = new User(["id" => $info_array['friend_id'], "username" => $info_array['friend_name']], true);
        $this->is_self = $is_self;
        $this->is_online = $online;
        $this->date = $info_array['date'];

        if ($is_self) // we are logged in
        {
            $this->is_pending = ((int)$info_array['is_request'] === 1);
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
     * @param int $user_id
     *
     * @return int[] array of friend id's
     * @throws FriendException
     */
    public static function getOnlineFriendsOf($user_id)
    {
        $sql_query = <<<SQL
    SELECT `{DB_VERSION}_friends`.asker_id AS friend_id
    FROM `{DB_VERSION}_friends`, `{DB_VERSION}_client_sessions`
    WHERE   `{DB_VERSION}_friends`.receiver_id = :user_id
        AND `{DB_VERSION}_friends`.is_request = 0
        AND `{DB_VERSION}_client_sessions`.uid = `{DB_VERSION}_friends`.asker_id
        AND `{DB_VERSION}_client_sessions`.is_online = 1
UNION
    SELECT `{DB_VERSION}_friends`.receiver_id AS friend_id
    FROM `{DB_VERSION}_friends`, `{DB_VERSION}_client_sessions`
    WHERE   `{DB_VERSION}_friends`.asker_id = :user_id
        AND `{DB_VERSION}_friends`.is_request = 0
        AND `{DB_VERSION}_client_sessions`.uid = `{DB_VERSION}_friends`.receiver_id
        AND `{DB_VERSION}_client_sessions`.is_online = 1
SQL;

        try
        {
            $friends = DBConnection::get()->query(
                $sql_query,
                DBConnection::FETCH_ALL,
                [":user_id" => $user_id],
                [":user_id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new FriendException(exception_message_db(_('fetch online friends')));
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
     * @param int  $user_id
     * @param bool $is_self
     * @param bool $return_instance
     *
     * @throws FriendException
     * @return Friend[]|array an array of friends
     */
    public static function getFriendsOf($user_id, $is_self = false, $return_instance = true)
    {
        try
        {
            // TODO clean selects, use JOIN instead of listing selects in the from clause
            if ($is_self) // get all users if we are the logged in user
            {
                $sql_query = <<<SQL
    SELECT `{DB_VERSION}_friends`.date AS date, 
           `{DB_VERSION}_friends`.is_request AS is_request, 
           `{DB_VERSION}_friends`.asker_id AS friend_id,
           `{DB_VERSION}_users`.username AS friend_name, 1 AS is_asker
    FROM `{DB_VERSION}_friends`, `{DB_VERSION}_users`
    WHERE   `{DB_VERSION}_friends`.receiver_id = :user_id
        AND `{DB_VERSION}_users`.id = `{DB_VERSION}_friends`.asker_id
UNION
    SELECT `{DB_VERSION}_friends`.date AS date, 
           `{DB_VERSION}_friends`.is_request AS is_request,
           `{DB_VERSION}_friends`.receiver_id AS friend_id,
           `{DB_VERSION}_users`.username AS friend_name, 0 AS is_asker
    FROM `{DB_VERSION}_friends`, `{DB_VERSION}_users`
    WHERE   `{DB_VERSION}_friends`.asker_id = :user_id
        AND `{DB_VERSION}_users`.id = `{DB_VERSION}_friends`.receiver_id
    ORDER BY date ASC
SQL;
                $friends = DBConnection::get()->query(
                    $sql_query,
                    DBConnection::FETCH_ALL,
                    [":user_id" => $user_id],
                    [":user_id" => DBConnection::PARAM_INT]
                );
            }
            else // get only the accepted friends, viewing other user profile
            {
                $sql_query = <<<SQL
    SELECT `{DB_VERSION}_friends`.date AS date, 
           `{DB_VERSION}_friends`.asker_id AS friend_id,
           `{DB_VERSION}_users`.username AS friend_name
    FROM `{DB_VERSION}_friends`, `{DB_VERSION}_users`
    WHERE   `{DB_VERSION}_friends`.receiver_id = :user_id
        AND `{DB_VERSION}_users`.id = `{DB_VERSION}_friends`.asker_id
        AND `{DB_VERSION}_friends`.is_request = 0
UNION
    SELECT `{DB_VERSION}_friends`.date AS date,
           `{DB_VERSION}_friends`.receiver_id AS friend_id,
           `{DB_VERSION}_users`.username AS friend_name
    FROM `{DB_VERSION}_friends`, `{DB_VERSION}_users`
    WHERE   `{DB_VERSION}_friends`.asker_id = :user_id
        AND `{DB_VERSION}_users`.id = `{DB_VERSION}_friends`.receiver_id
        AND `{DB_VERSION}_friends`.is_request = 0
    ORDER BY date ASC
SQL;
                $friends = DBConnection::get()->query(
                    $sql_query,
                    DBConnection::FETCH_ALL,
                    [":user_id" => $user_id],
                    [":user_id" => DBConnection::PARAM_INT]
                );
            }
        }
        catch (DBException $e)
        {
            throw new FriendException(exception_message_db(_('fetch friends')));
        }

        // build friends array
        $return_friends = [];
        if ($return_instance)
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
     * @param int  $user_id
     * @param bool $is_self
     *
     * @throws FriendException
     * @return string
     */
    public static function getFriendsAsXML($user_id, $is_self = false)
    {
        $friends = static::getFriendsOf($user_id, $is_self);

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
                "SELECT asker_id, receiver_id FROM `{DB_VERSION}_friends`
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
                    "INSERT INTO `{DB_VERSION}_friends` (asker_id, receiver_id)
                    VALUES (:asker, :receiver)
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
                    "INSERT INTO `{DB_VERSION}_notifications` (`to`, `from`, `type`)
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
        catch (DBException $e)
        {
            DBConnection::get()->rollback();
            throw new FriendException(exception_message_db(_('add a friend request')));
        }
    }

    /**
     * Accept a friend request
     *
     * @param int $friend_id   the user who sent the friend request, usually the friend
     * @param int $receiver_id the user who accepts the friend request, usually the logged in user
     *
     * @throws FriendException
     */
    public static function acceptFriendRequest($friend_id, $receiver_id)
    {
        try
        {
            DBConnection::get()->query(
                "UPDATE `{DB_VERSION}_friends`
                SET is_request = 0
                WHERE asker_id = :asker AND receiver_id = :receiver",
                DBConnection::ROW_COUNT,
                [
                    ':asker'    => $friend_id,
                    ':receiver' => $receiver_id
                ],
                [':asker' => DBConnection::PARAM_INT, ':receiver' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new FriendException(exception_message_db(_('accept a friend request')));
        }
    }

    /**
     * Remove a friend by deleting the record from the database.
     * This is used bu the decline and cancel methods.
     *
     * @param int    $asker_id    the asker
     * @param int    $receiver_id the receiver
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
                "DELETE FROM `{DB_VERSION}_friends`
                WHERE asker_id = :asker AND receiver_id = :receiver",
                DBConnection::ROW_COUNT,
                [
                    ':asker'    => $asker_id,
                    ':receiver' => $receiver_id
                ],
                [':asker' => DBConnection::PARAM_INT, ':receiver' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new FriendException(exception_message_db($message_error));
        }

        return $count;
    }

    /**
     * Decline a friend request by deleting the record. We are the receiver
     *
     * @param int $friend_id   the user who sent the friend request
     * @param int $receiver_id the user who declines the friend request
     *
     * @throws FriendException
     */
    public static function declineFriendRequest($friend_id, $receiver_id)
    {
        static::removeFriendRecord($friend_id, $receiver_id, _('decline a friend request'));
    }

    /**
     * Cancel a friend request by deleting the record. The logged in user is the asker
     *
     * @param int $asker_id  the user who sent the friend request, usually the logged user
     * @param int $friend_id the user who was asked by the logged in user
     *
     * @throws FriendException
     */
    public static function cancelFriendRequest($asker_id, $friend_id)
    {
        static::removeFriendRecord($asker_id, $friend_id, _('cancel a friend request'));
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
