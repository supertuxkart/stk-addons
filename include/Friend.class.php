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
    protected $is_asker;
    protected $friend_id;
    protected $date;
    protected $is_pending;
    protected $user;
    protected $online;

    /**
     * 
     * @param array $info_array an associative array based on the database
     */
    protected function __construct($info_array, $online = False, $extra_info = False)
    {
        $this->user = new User($info_array['friend_id'], $info_array['friend_name']);
        $this->extra_info = $extra_info;
        if($extra_info){
            $this->online = $online;
    		$this->is_pending = $info_array['request'] == 1;
    		$this->is_asker = $info_array['is_asker'] == 1;		
    		$this->date = $info_array['date'];
        }
    }
    
    public function getUser()
    {
        return $this->user;
    }
    
    public function isOnline()
    {
        return $this->online;
    }
    
    public function asXML()
    {
    	$friend_xml = new XMLOutput();
	    $friend_xml->startElement('friend');
	    if($this->extra_info){
    	    $friend_xml->writeAttribute("is_pending", ($this->is_pending ? "yes" : "no"));
    	    if($this->is_pending){
    	       $friend_xml->writeAttribute("is_asker", ($this->is_asker ? "yes" : "no"));
    	    }else{
    	       $friend_xml->writeAttribute("online", ($this->online ? "yes" : "no"));
    	    }
    	    $friend_xml->writeAttribute("date", $this->date);   
	    } 
	    $friend_xml->insert($this->user->asXML());
	    $friend_xml->endElement();
	    return $friend_xml->asString();
    }
    
    /**
     * Create server
     * @param 
     * @param 
     * @return Server
     * @throws 
     *//*
    public static function create(  $ip,
                                    $port,
                                    $userid,
                                    $server_name,
                                    $max_players)
    {
        $max_players = (int) $max_players;
        try{
            $count = DBConnection::get()->query
            (
                "SELECT `id` FROM `" . DB_PREFIX . "servers`
                    WHERE `ip`= :ip AND `port`= :port ",
                DBConnection::ROW_COUNT,
                array
                (
                    ':ip'   => $ip,
                    ':port' => $port
                )
            );
            if ($count != 0)
                throw new ServerException(_('Specified server already exists.'));
            $result = DBConnection::get()->query
            (
                "INSERT INTO `" . DB_PREFIX ."servers` (hostid, ip, port, name, max_players)
                VALUES (:hostid, :ip, :port, :name, :max_players)",
                DBConnection::ROW_COUNT,
                array
                (
                    ':hostid'       => (int) $userid,
                    ':ip'           => (int) $ip,
                    ':port'         => (int) $port,
                    ':name'         => (string) $server_name,
                    ':max_players'  => (int)    $max_players
                )
            );
            if ($result != 1) {
                throw new ServerException(_('Could not create server'));
            }
            return Server::getServer(DBConnection::get()->lastInsertId());

        }catch(PDOExpcetion $e){
            throw new ServerException(
                _('An error occurred while creating server.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }*/
    
    public static function getOnlineFriendsOf($userid)
    {
        try{
            $result = DBConnection::get()->query
            (
                "
                    SELECT " . DB_PREFIX ."friends.asker_id AS friend_id 
                    FROM " . DB_PREFIX ."friends, " . DB_PREFIX ."client_sessions
                    WHERE " . DB_PREFIX ."friends.receiver_id = :userid 
                        AND " . DB_PREFIX ."friends.request = 0
                        AND " . DB_PREFIX ."client_sessions.uid = " . DB_PREFIX ."friends.asker_id 
                        AND " . DB_PREFIX ."client_sessions.online = 1
                UNION
                    SELECT " . DB_PREFIX ."friends.receiver_id AS friend_id 
                    FROM " . DB_PREFIX ."friends, " . DB_PREFIX ."client_sessions
                    WHERE " . DB_PREFIX ."friends.asker_id = :userid
                        AND " . DB_PREFIX ."friends.request = 0
                        AND " . DB_PREFIX ."client_sessions.uid = " . DB_PREFIX ."friends.receiver_id 
                        AND " . DB_PREFIX ."client_sessions.online = 1
                ",
                DBConnection::FETCH_ALL,
                array
                (
                        ':userid'       => (int) $userid
                )
            );
        }catch (DBException $e){
            throw new FriendException(
                    _('An unexpected error occured while fetching online friends.') . ' ' .
                    _('Please contact a website administrator.'));
        }
        $string_list = "";
        foreach ($result as $r){
            $string_list .= $r['friend_id'];
            $string_list .= ' ';
        }
        $string_list = trim($string_list);
        return $string_list;
    }
    
    /**
     * Returns XML string
     * @param int $userid
     * @return string
     */
    public static function getFriendsAsXML($userid, $is_self = False)
    {
        try{
            if($is_self){
                $result = DBConnection::get()->query
                ( 
                    "
                    SELECT " . DB_PREFIX ."friends.date AS date, " . DB_PREFIX ."friends.request AS request, " . DB_PREFIX ."friends.asker_id AS friend_id, " . DB_PREFIX ."users.user AS friend_name, 1 AS is_asker FROM " . DB_PREFIX ."friends, " . DB_PREFIX ."users
                    WHERE " . DB_PREFIX ."friends.receiver_id = :userid AND " . DB_PREFIX ."users.id = " . DB_PREFIX ."friends.asker_id
                    UNION
                    SELECT " . DB_PREFIX ."friends.date AS date, " . DB_PREFIX ."friends.request AS request, " . DB_PREFIX ."friends.receiver_id AS friend_id, " . DB_PREFIX ."users.user AS friend_name, 0 AS is_asker FROM " . DB_PREFIX ."friends, " . DB_PREFIX ."users 
                    WHERE " . DB_PREFIX ."friends.asker_id = :userid AND " . DB_PREFIX ."users.id = " . DB_PREFIX ."friends.receiver_id
                    ORDER BY friend_name ASC                 
                    ",       
                    DBConnection::FETCH_ALL,
                    array
                    (
                        ':userid'       => (int) $userid
                    )          
                );
            }else{
                $result = DBConnection::get()->query
                (
                    "
                    SELECT " . DB_PREFIX ."friends.asker_id AS friend_id, " . DB_PREFIX ."users.user AS friend_name FROM " . DB_PREFIX ."friends, " . DB_PREFIX ."users
                    WHERE " . DB_PREFIX ."friends.receiver_id = :userid 
                        AND " . DB_PREFIX ."users.id = " . DB_PREFIX ."friends.asker_id 
                        AND " . DB_PREFIX ."friends.request = 0
                    UNION
                    SELECT " . DB_PREFIX ."friends.receiver_id AS friend_id, " . DB_PREFIX ."users.user AS friend_name FROM " . DB_PREFIX ."friends, " . DB_PREFIX ."users
                    WHERE " . DB_PREFIX ."friends.asker_id = :userid 
                        AND " . DB_PREFIX ."users.id = " . DB_PREFIX ."friends.receiver_id
                        AND " . DB_PREFIX ."friends.request = 0
                    ORDER BY friend_name ASC
                    ",
                    DBConnection::FETCH_ALL,
                    array
                    (
                            ':userid'       => (int) $userid
                    )
                );
            }
        }catch (DBException $e){
            throw new FriendException(
                _('An unexpected error occured while fetching friends.') . ' ' .
                _('Please contact a website administrator.'));
        }
        $partial_output = new XMLOutput();
        $partial_output->startElement('friends');
        foreach ($result as $friend_result)
        {
            $friend = new Friend($friend_result, False, $is_self);
            $partial_output->insert($friend->asXML());
        }
        $partial_output->endElement();
        return $partial_output->asString();
    }
}
