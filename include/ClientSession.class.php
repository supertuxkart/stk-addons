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


require_once(INCLUDE_DIR . 'Validate.class.php');
require_once(INCLUDE_DIR . 'Server.class.php');
require_once(INCLUDE_DIR . 'DBConnection.class.php');
require_once(INCLUDE_DIR . 'Exceptions.class.php');
require_once(INCLUDE_DIR . 'User.class.php');
require_once(INCLUDE_DIR . 'Friend.class.php');
require_once(INCLUDE_DIR . 'Achievement.class.php');

class ClientSessionException extends Exception {}
class ClientSessionConnectException extends ClientSessionException {}
class ClientSessionExpiredException extends ClientSessionException {}

/**
 * Abstract base class for handling client sessions
 * @property string $session_id
 * @property int	$user_id
 * @property string $user_name
 */
abstract class ClientSession
{
    protected $session_id;
    protected $user_id;
    protected $user_name;

    /**
     * @param string $session_id
     * @param int	 $user_id
     * @param string $user_name
     */
    protected function __construct($session_id, $user_id, $user_name)
    {
        $this->session_id = $session_id;
        $this->user_id = $user_id;
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
     * Create new session
     * @param string $username user name (registered user or temporary nickname)
     * @param string $password password of registered user (optional)
     * @param bool
     * @return ClientSession object
     * @throws InvalidArgumentException when username is not provided
     */
    public static function create($username, $password, $save_session)
    {
        if (empty($username)) {
            throw new InvalidArgumentException(_('Username required'));
        }
        else if (empty($password)) {
            throw new InvalidArgumentException(_('Password required'));
            //return ClientSessionAnonymous::create($username);
        }
        else {
            return RegisteredClientSession::create($username, $password, $save_session);
        }
    }
    
    /**
     * Get session object for already created session
     * @param string $session_id session id
     * @param numeric $user_id user id
     * @return ClientSessionAnonymous|ClientSessionUser
     * @throws ClientSessionExpiredException when session does not exist
     */
    public static function get($session_id, $user_id)
    {
        try{
            $session_info = DBConnection::get()->query
            (
                "SELECT * FROM `" . DB_PREFIX . "client_sessions` 
                WHERE cid = :sessionid AND uid = :userid",
                DBConnection::FETCH_ALL,
                array
                (
                    ':sessionid'   => (string) $session_id,
                    ':userid'   => (int) $user_id
                )
            );
            $size = count($session_info);
            if ($size != 1) {
                throw new ClientSessionExpiredException(_('Session not valid. Please sign in.'));
            }else {
                
                //Valid session found, get more user info
                $user_info = DBConnection::get()->query
                (
                    "SELECT `user`,`role`
                    FROM `" . DB_PREFIX . "users`
                    WHERE `id` = :userid",
                    DBConnection::FETCH_ALL,
                    array
                    (
                        ':userid'   => (int) $user_id
                    )
                );
                // here an if statement will come for Guest and registered
                return new RegisteredClientSession( $session_info[0]["cid"], 
                                                    $session_info[0]["uid"],
                                                    $user_info[0]["user"]);
            }
        }catch (DBException $e){
            throw new UserException(
                _('An error occurred while verifying session.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }

    /**
     * Destroy session, you could also call it logout
     * @throws ClientSessionExpiredException when session does not exist
     */
    public function destroy()
    {
        try{
            $count = DBConnection::get()->query(
                "DELETE FROM `".DB_PREFIX."client_sessions`
    	        WHERE `cid` = :session_id AND uid = :user_id",
                DBConnection::ROW_COUNT,
                array(
                    ':user_id'    => (int) $this->user_id,
                    ':session_id' => (string) $this->session_id
                )
            );
        }catch(DBException $e){
            throw new ClientSessionExpiredException(
                _('An error occurred while signing out.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }
    
    public function clientQuit()
    {
        try{
            DBConnection::get()->beginTransaction();
            $result = DBConnection::get()->query(
                "SELECT `save` FROM `".DB_PREFIX."client_sessions`
    	        WHERE `cid` = :session_id AND uid = :user_id",
                DBConnection::FETCH_ALL,
                array(
                    ':user_id'    => (int) $this->user_id,
                    ':session_id' => (string) $this->session_id
                )
            );
            if (count($result) == 1)
            {
                if($result[0]['save'] == 1)
                {
                    $this->setOnline(false);
                }
                else 
                    $this->destroy();
            }
            DBConnection::get()->commit();
        }catch(DBException $e){
            throw new ClientSessionExpiredException(
                    _('An error occurred while logging out.') .' '.
                    _('Please contact a website administrator.')
            );
        }
    }

    /**
     * Sets the public address of a player
     * @param int $id user id 
     * @param string $token user token
     * @param int $ip user ip
     * @param int $port user port
     * @param int $private_port the private port (for LANs and special NATs)
     * @throws UserException if the request fails
     */
    public static function setPublicAddress($id, $token, $ip, $port, $private_port)
    {
        try{
            //Query the database to set the ip and port
            $count = DBConnection::get()->query
            (
                "UPDATE `" . DB_PREFIX . "client_sessions`
                SET `ip` = :ip , `port` = :port, `private_port` = :private_port
                WHERE `uid` = :userid AND `cid` = :token",
                DBConnection::ROW_COUNT,
                array
                (
                    ':ip'           => $ip,
                    ':port'         => $port,
                    ':private_port' => $private_port,
                    ':userid'       => $id,
                    ':token'        => $token
                )
            );
            // if count = 0 that may be a re-update of an existing key
            if ($count > 1) {
                throw new UserException(htmlspecialchars(_('Could not set the ip:port')));
            }
        }catch (DBException $e){
            throw new UserException(
                _('An error occurred while setting ip:port.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }

    /**
     * Unsets the public address of a user
     * @param int $id user id 
     * @param string $token user token
     * @throws UserException if the request fails
     */
    public static function unsetPublicAddress($id, $token)
    {
        try{
            $count = DBConnection::get()->query
            (
                "UPDATE `" . DB_PREFIX . "client_sessions`
                SET `ip` = '0' , `port` = '0'
                WHERE `uid` = :userid AND `cid` = :token",
                DBConnection::ROW_COUNT,
                array
                (
                    ':userid'   => $id,
                    ':token'    => $token
                )
            );
            if ($count == 0) {
                throw new ClientSessionException(_('ID:Token must be wrong.'));
            }
            elseif ($count > 1){
                throw new ClientSessionException(_('Weird count of updates'));
            }
        }catch (DBException $e){
            throw new UserException(
                _('An error occurred while unsetting ip:port.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }

    /**
     * Get the public address of a player
     * @param int $peer_id id of the peer
     * @return the ip and port of the player
     * @throws UserException if the request fails
     */
    public function getPeerAddress($peer_id)
    {
        try{
            //FIXME :   A check should be done that the requester is the host of a server
            //          the requestee has joined. (Else anybody with an account could call this with the correct POST parameters)
                  
            //Query the database to set the ip and port
            $result = DBConnection::get()->query
            (
                "SELECT `ip`, `port`, `private_port`
                FROM `" . DB_PREFIX . "client_sessions`
                WHERE `uid` = :peerid",
                DBConnection::FETCH_ALL,
                array
                (
                    ':peerid'   => $peer_id
                )
            );
            $size = count($result);
            if ($size == 0) {
                throw new UserException(_('That user is not signed in.'));
            }elseif ($size > 1) {
                throw new UserException(_('Too much users match the request'));
            }else {
                return $result[0];
            }
        }catch (DBException $e){
            throw new UserException(
                _('An error occurred while getting a peer\'s ip:port.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }

    public function requestServerConnection($server_id)
    {
        try{
            $count = DBConnection::get()->query
            (
                "INSERT INTO `" . DB_PREFIX . "server_conn` (serverid, userid, request) 
                VALUES ( :serverid, :userid, 1) 
                ON DUPLICATE KEY 
                UPDATE request = '1', serverid = :serverid",
                DBConnection::ROW_COUNT,
                array
                (
                    ':userid'       => $this->user_id,
                    ':serverid'     => $server_id
                )
            );
            if ($count > 2 || $count < 0) {
                throw new DBException();
            }
            return $count;
        }catch (DBException $e){
            throw new UserException(
                _('An error occurred while requesting a server connection.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }

    public function quickJoin()
    {
        try{
            //Query the database to add the request entry
            $result = DBConnection::get()->query
            (
                "SELECT `id`, `hostid`, `ip`, `port`, `private_port` FROM `" . DB_PREFIX . "servers`
                LIMIT 1",
                DBConnection::FETCH_ALL
            );
            if (count($result) == 0)
                throw new UserException(_('No server found'));
            DBConnection::get()->query
            (
                "INSERT INTO `" . DB_PREFIX . "server_conn` (serverid, userid, request) 
                VALUES ( :serverid, :userid, 1) ON DUPLICATE KEY UPDATE request='1'",
                DBConnection::NOTHING,
                array
                (
                    ':userid'       => $this->user_id,
                    ':serverid'     => $result[0]['id']
                )
            );
            return $result[0];
        }catch (DBException $e){
            throw new UserException(
                _('An error occurred while quick joining.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }

    public function getServerConnectionRequests($ip, $port)
    {
        try{
            $serverid = DBConnection::get()->query
            (
                "SELECT `id` FROM `" . DB_PREFIX . "servers`
                WHERE `hostid` = :hostid AND `ip` = :ip AND `port` = :port LIMIT 1",
                DBConnection::FETCH_ALL,
                array
                (
                    ':hostid'   => $this->user_id,
                    ':ip'       => $ip,
                    ':port'     => $port
                )
            );
            $connection_requests = DBConnection::get()->query
            (
                "SELECT `userid`
                FROM `" . DB_PREFIX . "server_conn`
                WHERE `serverid` = :server_id AND `request` = '1'",
                DBConnection::FETCH_ALL,
                array
                (
                    ':server_id'   => $serverid[0]['id']
                )
            );
            //Set the request bit to zero for all users we fetch
            $index = 0;
            $parameters = array();
            $query_parts = array();
            foreach($connection_requests as $user){
                $parameter = ":userid" . $index;
                $index++;
                $query_parts[]= "`userid` = " . $parameter;
                $parameters[$parameter] = $user['userid'];
            }
            if($index > 0){
                $count = DBConnection::get()->query
                (
                    "UPDATE `" . DB_PREFIX . "server_conn`
                        SET `request` = 0
                        WHERE " . implode(" OR ",$query_parts),
                    DBConnection::ROW_COUNT,
                    $parameters
                );
                //Perhaps check if $count and $index are equal
            }
            return $connection_requests;
        }catch (DBException $e){
            throw new UserException(
                _('An error occurred while fetching server connection requests.') .' '.
                _('Please contact a website administrator.')
            );
        }
    }

    public function setOnline($online = true)
    {
        try{
            $count = DBConnection::get()->query
            (
                "UPDATE `" . DB_PREFIX ."client_sessions`
                SET online = :online
                WHERE uid = :id",
                DBConnection::ROW_COUNT,
                array
                (
                    ':id'   => (int) $this->user_id,
                    ':online' => ($online ? 1 : 0)
                )
            );
        }catch (DBException $e){
            throw new FriendException(
                _('An unexpected error occured while updating your status.') . ' ' .
                _('Please contact a website administrator.'));
        }
    }
	

    /**
     * Generate a alphanumerical session id
     * @return string session id
     */
    protected static function calcSessionId()
    {
        return substr(md5(uniqid('', true)), 0, 24);
    }
    
}

/**
 * ClientSession implementation for registered users
 */
class RegisteredClientSession extends ClientSession
{

    /**
     * New instance
     * @param string $session_id
     * @param int $user_id
     * @param string $user_name
     */
    protected function __construct($session_id, $user_id, $user_name)
    {
        parent::__construct($session_id, $user_id, $user_name);
        
        
        
    }
    
    /**
     * 
     * @param int $visiterid
     * @return string
     */
    public function getFriendsOf($visitingid)
    {
        return Friend::getFriendsAsXML($visitingid, $this->user_id === $visitingid);
    }

    /**
     * Create session for registered user
     * @param string $username username
     * @param string $password password (plain)
     * @param bool 
     * @return RegisterdClientSession
     * @throws ClientSessionConnectException when credentials are wrong
     */
    public static function create($username, $password, $save_session)
    {
        try{      
            $result = Validate::credentials($username,$password);
            $size = count($result);
            if ($size == 0) {
                throw new ClientSessionConnectException(_('Username and/or password invalid.'));
            }elseif ($size > 1) {
                throw new DBException();
            }else{
                $session_id = ClientSession::calcSessionId();
                $user_id = $result[0]["id"];
                $username = $result[0]["user"];
                $count = DBConnection::get()->query
                (
                    "INSERT INTO `" . DB_PREFIX ."client_sessions` (cid, uid, save)
                    VALUES (:session_id, :user_id, :save) 
                    ON DUPLICATE KEY UPDATE cid = :session_id, online = 1",
                    DBConnection::ROW_COUNT,
                    array
                    (
                        ':session_id'   => (string) $session_id,
                        ':user_id'   => (int) $user_id,
                        ':save'   => ($save_session ? 1 : 0),
                    )
                );
                if ($count > 2 || $count < 0)
                    throw new DBException();
                User::updateLoginTime($result[0]['id']);
                return new RegisteredClientSession($session_id, $user_id, $username);
            }
        }catch (DBException $e){
            throw new ClientSessionConnectException(
                _('An unexpected error occured while creating your session.') . ' ' .
                _('Please contact a website administrator.'));
        }
    }
    
    /**
     *
     * @param int $ip
     * @param int $port
     * @param string $server_name
     * @param int $max_players
     * @return Server
     */
    public function createServer($ip, $port, $private_port, $server_name, $max_players)
    {
        ClientSession::setPublicAddress($this->user_id, $this->session_id, $ip, $port, $private_port);
        return Server::create($ip, $port, $private_port, $this->user_id, $server_name, $max_players);
    }
    
    /**
     *
     * @param int $ip
     * @param int $port
     * @throws UserException
     */
    public function stopServer($ip, $port)
    {
        try{
            // empty the public ip:port
            ClientSession::setPublicAddress($this->user_id, $this->session_id, 0, 0);
            // now setup the serv info
            $count = DBConnection::get()->query
            (
                    "DELETE FROM `" . DB_PREFIX . "servers`
                WHERE `ip`= :ip AND `port`= :port AND `hostid`= :id",
                    DBConnection::ROW_COUNT,
                    array
                    (
                            ':ip'   => $ip,
                            ':port' => $port,
                            ':id'   => $this->user_id
                    )
            );
            if ($count != 1)
                throw new UserException(_('Not the good number of servers deleted.'));
        }catch (DBException $e){
            throw new UserException(
                    _('An error occurred while ending a server.') .' '.
                    _('Please contact a website administrator.')
            );
        }
    }
    
    
    public function friendRequest($friendid)
    {
        if($friendid == $this->user_id)
            throw new FriendException(
                _('You cannot ask yourself to be your friend!'));
        try{
            DBConnection::get()->beginTransaction();
            $result = DBConnection::get()->query
            (
                    "SELECT asker_id, receiver_id FROM `" . DB_PREFIX ."friends`
                    WHERE (asker_id = :asker AND receiver_id = :receiver) 
                        OR (asker_id = :receiver AND receiver_id = :asker)",
                    DBConnection::FETCH_ALL,
                    array
                    (
                            ':asker'   => (int) $this->user_id,
                            ':receiver'   => (int) $friendid
                    )
            );
            if(count($result) > 0)
            {
                DBConnection::get()->commit();
                if($result[0]['asker_id'] == $this->user_id){
                    //The request was already in there, should not be possible normally
                    //ignore it but log this! FIXME
                }else{
                    // The friend already did a friend request! interpret as accepting the friend request!
                    $this->acceptFriendRequest($friendid);
                }
            }
            else
            {
                $count1 = DBConnection::get()->query
                (
                    "INSERT INTO `" . DB_PREFIX ."friends` (asker_id, receiver_id, date)
                    VALUES (:asker, :receiver, CURRENT_DATE())
                    ON DUPLICATE KEY UPDATE asker_id = :asker",
                    DBConnection::ROW_COUNT,
                    array
                    (
                            ':asker'   => (int) $this->user_id,
                            ':receiver'   => (int) $friendid
                    )
                );
                $count2 = DBConnection::get()->query
                (
                    "INSERT INTO `" . DB_PREFIX ."notifications` (`to`, `from`, `type`)
                    VALUES (:to, :from, 'f_request')
                    ON DUPLICATE KEY UPDATE `to` = :to",
                    DBConnection::ROW_COUNT,
                    array
                    (
                            ':to'   => (int) $friendid,
                            ':from' => (int) $this->user_id
                            
                    )
                );
                DBConnection::get()->commit();
            }
        }catch (DBException $e){
            throw new FriendException(
                _('An unexpected error occured while adding your friend request.') . ' ' .
                _('Please contact a website administrator.'));
        }    
    }
    
    public function acceptFriendRequest($friendid)
    {
        try{
            $count = DBConnection::get()->query
            (
                "UPDATE `" . DB_PREFIX ."friends`
                SET request = 0
                WHERE asker_id = :asker AND receiver_id = :receiver",
                DBConnection::ROW_COUNT,
                array
                (
                        ':asker'   => (int) $friendid,
                        ':receiver'   => (int) $this->user_id
                )
            );
        }catch (DBException $e){
            throw new FriendException(
                    _('An unexpected error occured while accepting a friend request.') . ' ' .
                    _('Please contact a website administrator.'));
        }
    }
    
    public function declineFriendRequest($friendid)
    {
        try{
            $count = DBConnection::get()->query
            (
                "DELETE FROM `" . DB_PREFIX ."friends`
                WHERE asker_id = :asker AND receiver_id = :receiver",
                DBConnection::ROW_COUNT,
                array
                (
                        ':asker'   => (int) $friendid,
                        ':receiver'   => (int) $this->user_id
                )
            );
        }catch (DBException $e){
            throw new FriendException(
                    _('An unexpected error occured while declining a friend request.') . ' ' .
                    _('Please contact a website administrator.'));
        }
    }
    
    public function cancelFriendRequest($friendid)
    {
        try{
            $count = DBConnection::get()->query
            (
                "DELETE FROM `" . DB_PREFIX ."friends`
                WHERE asker_id = :asker AND receiver_id = :receiver",
                DBConnection::ROW_COUNT,
                array
                (
                        ':asker'   => (int) $this->user_id,
                        ':receiver'   => (int) $friendid
                )
            );
        }catch (DBException $e){
            throw new FriendException(
                    _('An unexpected error occured while cancelling your friend request.') . ' ' .
                    _('Please contact a website administrator.'));
        }
    }
    
    public function removeFriend($friendid)
    {
        $this->cancelFriendRequest($friendid);
        $this->declineFriendRequest($friendid);
    }
    
    public function getOnlineFriends(){
        return Friend::getOnlineFriendsOf($this->user_id);
    }
    
    public function getNotifications(){
        try{
            DBConnection::get()->beginTransaction();
            $result = DBConnection::get()->query
            (
                "SELECT `from`, `type` FROM `" . DB_PREFIX ."notifications`
                WHERE `to` = :to",
                DBConnection::FETCH_ALL,
                array
                (
                        ':to'   => (int) $this->user_id
                )
            );
            $count = DBConnection::get()->query
            (
                "DELETE FROM `" . DB_PREFIX ."notifications`
                WHERE `to` = :to",
                DBConnection::ROW_COUNT,
                array
                (
                        ':to'   => (int) $this->user_id
                )
            );
            DBConnection::get()->commit();
            $result_array = array ();
            $result_array['f_request'] = array();
            foreach($result as $notification){
                if($notification['type'] == 'f_request')
                    $result_array['f_request'][] = $notification['from'];
            }
            return $result_array;
            
        }catch (DBException $e){
            throw new FriendException(
                    _('An unexpected error occured while fetching new notifications.') . ' ' .
                    _('Please contact a website administrator.'));
        }
    }
    
    public function poll()
    {
        $this->setOnline();
        $online_friends = $this->getOnlineFriends();
        $notifications = $this->getNotifications();
        $partial_output = new XMLOutput();
        $partial_output->startElement('poll');
        $partial_output->writeAttribute('success','yes');
        $partial_output->writeAttribute('info','');
        if($online_friends){
            $partial_output->writeAttribute('online', $online_friends);
        }
        if(!empty($notifications['f_request'])){
            foreach($notifications['f_request'] as $requester_id){
                $partial_output->insert(User::getFromID($requester_id)->asXML('new_friend_request'));
            }
        }
        $partial_output->endElement();
        return $partial_output->asString();
    }
    
    public function hostVote($hostid, $vote)
    {
        $vote = (int) $vote;
        if($vote != 1 || $vote != -1) 
            throw new ClientSessionException(_("Invalid vote. Your rating has to be either -1 or 1."));
        try{
            $count2 = DBConnection::get()->query
            (
                "INSERT INTO `" . DB_PREFIX ."host_votes` (`userid`, `hostid`, `vote`)
                VALUES (:userid, :hostid, :vote)
                ON DUPLICATE KEY UPDATE `to` = :to",
                DBConnection::ROW_COUNT,
                array
                (
                        ':hostid'   => (int) $hostid,
                        ':userid' => (int) $this->user_id,
                        ':vote' => (int) $vote    
                )
            );
        }catch (DBException $e){
            throw new ClientSessionException(
                    _('An unexpected error occured while casting your host vote.') . ' ' .
                    _('Please contact a website administrator.'));
        }
    }
    
    public function onAchieving($achievementid)
    {
        Achievement::achieve($this->user_id, $achievementid);
    }
    
    public function getAchievements($id = 0)
    {
        if ($id == 0)
            return Achievement::getAchievementsOf($this->user_id);
        else
            return Achievement::getAchievementsOf($id);
    }   
}
