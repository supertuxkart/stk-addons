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
     * @var
     */
    protected $date;

    /**
     * @var bool
     */
    protected $is_pending;

    /**
     * @var bool
     */
    protected $is_online;

    /**
     * @var bool
     */
    protected $is_asker;

    /**
     * @var User
     */
    protected $user;

    /**
     * The Friend constructor
     *
     * @param array $info_array an associative array based on the database
     * @param bool  $online     is the user online
     * @param bool  $extra_info initialize extra info
     */
    protected function __construct($info_array, $online = false, $extra_info = false)
    {
        $this->user = new User($info_array['friend_id'], ["user" => $info_array['friend_name']]);
        $this->extra_info = $extra_info;
        if ($extra_info)
        {
            $this->is_online = $online;
            $this->is_pending = $info_array['request'] == 1;
            $this->is_asker = $info_array['is_asker'] == 1;
            $this->date = $info_array['date'];
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
     * @return bool
     */
    public function isOnline()
    {
        return $this->is_online;
    }

    /**
     * @return string
     */
    public function asXML()
    {
        $friend_xml = new XMLOutput();
        $friend_xml->startElement('friend');
        if ($this->extra_info)
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
     *
     * @throws FriendException
     * @return Friend[] an array of friends
     */
    public static function getFriendsOf($userid, $is_self = false)
    {
        // TODO clean up the look of the SQL queries
        try
        {
            if ($is_self)
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
                        ORDER BY friend_name ASC",
                    DBConnection::FETCH_ALL,
                    [":userid" => $userid],
                    [":userid" => DBConnection::PARAM_INT]
                );
            }
            else
            {
                $friends = DBConnection::get()->query(
                    "   SELECT " . DB_PREFIX . "friends.asker_id AS friend_id, "
                    . DB_PREFIX . "users.user AS friend_name
                        FROM " . DB_PREFIX . "friends, " . DB_PREFIX . "users
                        WHERE   " . DB_PREFIX . "friends.receiver_id = :userid
                            AND " . DB_PREFIX . "users.id = " . DB_PREFIX . "friends.asker_id
                            AND " . DB_PREFIX . "friends.request = 0
                    UNION
                        SELECT " . DB_PREFIX . "friends.receiver_id AS friend_id, "
                    . DB_PREFIX . "users.user AS friend_name
                        FROM " . DB_PREFIX . "friends, " . DB_PREFIX . "users
                        WHERE   " . DB_PREFIX . "friends.asker_id = :userid
                            AND " . DB_PREFIX . "users.id = " . DB_PREFIX . "friends.receiver_id
                            AND " . DB_PREFIX . "friends.request = 0
                        ORDER BY friend_name ASC",
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
        foreach ($friends as $friend)
        {
            /// TODO also get online status
            $return_friends[] = new Friend($friend, false, $is_self);
        }

        return $return_friends;
    }

    /**
     * Returns XML string
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
}
