<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *                2013 Glenn De Jonghe
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

require_once(INCLUDE_DIR . 'Validate.class.php');
require_once(INCLUDE_DIR . 'Verification.class.php');
require_once(INCLUDE_DIR . 'DBConnection.class.php');
require_once(INCLUDE_DIR . 'exceptions.php');
require_once(INCLUDE_DIR . 'SMail.class.php');
require_once(INCLUDE_DIR . 'Log.class.php');

class User
{
    public static $logged_in = false;
    public static $user_id = 0;
    
    protected $id = 0;
    protected $user_name = "";
    
    public function __construct($id, $user_name)
    {
        $this->id = $id;
        $this->user_name = $user_name;
    }
    
    public function getUserName()
    {
    return $this->user_name;
    }
    
    public function getUserID()
    {
    return $this->id;
    }
    
    public function asXML($tag = 'user')
    {
        $user_xml = new XMLOutput();
        $user_xml->startElement($tag);
        $user_xml->writeAttribute('id', $this->id);
        $user_xml->writeAttribute('user_name', $this->user_name);
        $user_xml->endElement();
        return $user_xml->asString();
    }
    
    
    static function init() {
        if(defined('API')) return;
        // Validate user's session on every page
        if (session_id() == "") {
            session_start();
        }

        // Check if any session variables are not set
        if (!isset($_SESSION['userid']) ||
                !isset($_SESSION['user']) ||
                !isset($_SESSION['real_name']) ||
                !isset($_SESSION['last_login']) ||
                !isset($_SESSION['role']))
        {
            // One or more of the session variables was not set - this may
            // be an issue, so force logout
            User::logout();
            return;
        }
        // Validate session if complete set of variables is available
   
        try{
            $count = DBConnection::get()->query(
                "SELECT `id`,`user`,`name`,`role`
    	        FROM `" . DB_PREFIX . "users`
                WHERE `user` = :username
                AND `last_login` = :lastlogin
                AND `name` = :realname
                AND `active` = 1",
                DBConnection::ROW_COUNT,
                array(
                    ':username'     => (string) $_SESSION['user'],
                    ':lastlogin'    => $_SESSION['last_login'],
                    ':realname'     => (string) $_SESSION['real_name']
                )
            );
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
                _('An error occurred trying to validate your session.') .' '.
                _('Please contact a website administrator.')
            ));
        }
        
        
        if ($count !== 1) {
            User::logout();
            return false;
        }
        User::$user_id = $_SESSION['userid'];
        User::$logged_in = true;
    }
    
    static function updateLoginTime($userid)
    {       
        try{
            $result = DBConnection::get()->query(
                "UPDATE `".DB_PREFIX."users`
                SET `last_login` = NOW()
                WHERE `id` = :userid",
                DBConnection::NOTHING,
                array
                (
                    ':userid'   => $userid
                )
            );
            $result = DBConnection::get()->query(
                "SELECT `last_login`
                FROM `".DB_PREFIX."users`
                WHERE `id` = :userid",
                DBConnection::FETCH_ALL,
                array
                (
                    ':userid'   => $userid
                )
            );
            if (count($result) !== 1) {
                throw new PDOException();
            }
            return $result[0]['last_login'];
        }
        catch (PDOException $e){
            User::logout();
            throw new UserException(htmlspecialchars(
                _('An error occurred while recording last login time.') .' '.
                _('Please contact a website administrator.')
            ));
        }
        return $time;
    }

    static function login($username,$password)
    {
        $result = Validate::credentials($username, $password);
        // Check if the user exists
        if(count($result) != 1) {
            User::logout();
            throw new UserException(htmlspecialchars(_('Your username or password is incorrect.')));
        }
    
        $_SESSION['userid'] = $result[0]["id"];      
        $_SESSION['user'] = $result[0]["user"];
        $_SESSION['real_name'] = $result[0]["name"];
        User::$user_id = $result[0]['id'];
        include(ROOT.'include/allow.php');
        setPermissions($result[0]['role']);
        $_SESSION['last_login'] = User::updateLoginTime(User::$user_id);
        User::$logged_in = true;
        
        
        // Convert unsalted password to a salted one
        if (strlen($password) === 64) {
            $password = Validate::password($password);
            User::change_password($password);
            Log::newEvent("Converted the password of '$username' to use a password salting algorithm");
        }
        
        return true;
    }

    static function logout()
    {
        unset($_SESSION['userid']);
        unset($_SESSION['user']);
        unset($_SESSION['role']);
        unset($_SESSION['real_name']);
        unset($_SESSION['last_login']);
        session_destroy();
        session_start();
        User::$user_id = 0;
        User::$logged_in = false;
    }
    
    /**
     * Change the password of the supplied user; if none supplied, currently logged in user is used.
     * @param string $new_password
     * @param int $userid defaults to currently logged in user.
     * @throws UserException
     */
    static function change_password($new_password, $userid = 0) {
        if ($userid === 0)
            if (!User::$logged_in)
                throw new UserException(htmlspecialchars(_('You must be logged in to change a password.')));
            else
                $userid = User::$user_id;
   
        try{
            $count = DBConnection::get()->query(
                "UPDATE `".DB_PREFIX."users`
                SET `pass`   = :pass
    	        WHERE `id` = :userid",
                DBConnection::ROW_COUNT,
                array(
                        ':userid'   => (int) $userid,
                        ':pass'     => (string) $new_password
                )
            );
            if ($count === 0)
                throw new DBException();
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
                _('An error occured while trying to change your password.') .' '.
                _('Please contact a website administrator.')
            ));
        }
    }
    
    static function verifyAndChangePassword($current, $new1, $new2, $userid)
    {
        try{
            DBConnection::get()->beginTransaction();
            $count = DBConnection::get()->query(
                "SELECT `id`
                FROM `" . DB_PREFIX . "users`
                WHERE `id` = :userid AND `pass` = :pass",
                DBConnection::ROW_COUNT,
                array
                (
                    ':userid'   => (int) $userid,
                    ':pass'   => Validate::password($current, null, null, $userid)
                )
            );

            if($count < 1)
                throw new UserException(htmlspecialchars(_('Current password invalid.')));
                
            $new_hashed = Validate::password($new1, $new2);
            User::change_password($new_hashed, $userid);
            DBConnection::get()->commit();
        
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
                    _('An error occured while trying to change your password.') .' '.
                    _('Please contact a website administrator.')
            ));
        }
        
    }
    
    /**
     * Activate a new user
     * @param int $userid
     * @param string $ver_code 
     * @throws UserException when activation failed
     */
    static function activate($userid, $ver_code) {
        Verification::verify($userid, $ver_code);
        try{
            $count = DBConnection::get()->query(
                "UPDATE `".DB_PREFIX."users` 
                SET `active` = '1' 
    	        WHERE `id` = :userid",
                DBConnection::ROW_COUNT,
                array(
                        ':userid'   => $userid
                )
            );
            if ($count === 0)
                throw new DBException();
            Verification::delete($userid);
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
                    _('An error occurred trying to activate your useraccount.') .' '.
                    _('Please contact a website administrator.')
            ));
        }        
        Log::newEvent("User with ID '{$userid}' activated.");
    }
    
    public static function recover($username, $email)
    {
        // Check all form input
        $username = Validate::username($username);
        $email = Validate::email($email);       
        try{
            $userid = Validate::account($username, $email);
            $verification_code = Verification::generate($userid);
            
            // Send verification email
            try {
                $mail = new SMail;
                $mail->passwordResetNotification($email, $userid, $username, $verification_code, 'password-reset.php');
            }
            catch (Exception $e) {
                Log::newEvent('Password reset email for "'.$username.'" could not be sent.');
                throw new UserException($e->getMessage().' '._('Please contact a website administrator.'));
            }
            Log::newEvent("Password reset request for user '$username'");
            
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
                    _('An error occurred trying to validate your username and email-address for password reset.') .' '.
                    _('Please contact a website administrator.')
            ));
        }
    }

    /**
     * Register a new user account
     * @param string $username Must be unique
     * @param string $password
     * @param string $password_conf
     * @param string $email Must be unique
     * @param string $name
     * @param string $terms
     * @throws UserException 
     */
    public static function register($username, $password, $password_conf, $email, $name, $terms)
    {
	    // Sanitize inputs
	    $username = Validate::username($username);
	    $password = Validate::password($password, $password_conf);
	    $email = Validate::email($email);
	    $name = Validate::realname($name);
	    $terms = Validate::checkbox($terms,htmlspecialchars(_('You must agree to the terms to register.')));
	    DBConnection::get()->beginTransaction();
	    // Make sure requested username is not taken
        try{
            $result = DBConnection::get()->query(
                "SELECT `user` 
    	        FROM `".DB_PREFIX."users` 
    	        WHERE `user` LIKE :username",
                DBConnection::FETCH_ALL,
                array(
                    ':username'   => $username
    	        )	        
            );
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
                _('An error occurred trying to validate your username.') .' '.
                _('Please contact a website administrator.')
            ));
        }
        if(count($result) !== 0){
	        throw new UserException(htmlspecialchars(
	            _('This username is already taken.')
            ));
        }
	    // Make sure the email address is unique
        try{
            $result = DBConnection::get()->query(
                "SELECT `email` 
    	        FROM `".DB_PREFIX."users` 
    	        WHERE `email` LIKE :email",
                DBConnection::FETCH_ALL,
                array(
                    ':email'   => $email
    	        )	        
            );
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
                _('An error occurred trying to validate your email address.') .' '.
                _('Please contact a website administrator.')
            ));
        }
        if(count($result) !== 0){
	        throw new UserException(htmlspecialchars(
	            _('This email address is already taken.')
            ));
        }

	    // No exception occurred - continue with registration
        try{
            $count = DBConnection::get()->query
            (
                "INSERT INTO `".DB_PREFIX."users` 
                (`user`,`pass`,`name`, `role`, `email`, `active`, `reg_date`)
                VALUES(:username, :password, :name, :role, :email, 0, CURRENT_DATE())",
                DBConnection::ROW_COUNT,
                array
                (
                    ':username'     => $username,
                    ':password'     => $password,
                    ':name'         => $name,
                    ':role'         => "basicUser",
                    ':email'        => $email                            
                )
            );
            if($count != 1){
                throw new DBException();
            }
            $userid = DBConnection::get()->lastInsertId();
            DBConnection::get()->commit();
            $verification_code = Verification::generate($userid);
            // Send verification email
            try {
                $mail = new SMail;
                $mail->newAccountNotification($email, $userid, $username, $verification_code, 'register.php');
            }
            catch (Exception $e) {
                Log::newEvent("Registration email for user '$username' with id '$userid' failed.");
                throw new UserException($e->getMessage().' '._('Please contact a website administrator.'));
            }
            Log::newEvent("Registration submitted for user '$username' with id '$userid'.");
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
		        _('An error occurred while creating your account.') .' '. 
		        _('Please contact a website administrator.')
            ));
        }
        

    }
        
    
    public static function fetchFromID($id)
    {
        try{
            $result = DBConnection::get()->query
            (
                "SELECT user
                FROM `" . DB_PREFIX . "users`
                WHERE id = :id",
                DBConnection::FETCH_ALL,
                array
                (
                    ':id'     => (int) $id                         
                )
            );
            foreach ($result as $user)
            {
                return new User($id, $user['user']);
            }
            throw new UserException(htmlspecialchars(
                _("Tried to fetch an user that doesn't exist.") .' '.
                _('Please contact a website administrator.')
            ));
        }catch(DBException $e){
            throw new UserException(htmlspecialchars(
                    _('An error occurred while performing your search query.') .' '.
                    _('Please contact a website administrator.')
            ));
        }    
    }
    
    /**
     *
     * @param string $search_string
     * @throws UserException
     * @return multitype:User
     */
    public static function searchUsers($search_string) {   
        $terms = preg_split("/[\s,]+/", strip_tags($search_string));
        $index = 0;
        $parameters = array();
        $query_parts = array();
        foreach($terms as $term){
            if(strlen($term) > 2){
                $parameter = ":userid" . $index;
                $index++;
                $query_parts[]= "`user` RLIKE " . $parameter;
                $parameters[$parameter] = $term;
            }
        }
        $matched_users = array();
        if($index > 0){
            try{
                $result = DBConnection::get()->query
                (
                    "SELECT id, user
                    FROM `" . DB_PREFIX . "users`
                    WHERE " . implode(" OR ",$query_parts),
                    DBConnection::FETCH_ALL,
                    $parameters
                );
                foreach ($result as $user)
                {
                    $matched_users[] = new User($user['id'], $user['user']);
                }
            }catch(DBException $e){
                throw new UserException(htmlspecialchars(
                    _('An error occurred while performing your search query.') .' '.
                    _('Please contact a website administrator.')
                ));
            }
        }
        return $matched_users;
    }
    
    /**
     *
     * @param string $search_string
     * @throws UserException
     * @return multitype:User
     */
    public static function searchUsersAsXML($search_string) {
        $partial_output = new XMLOutput();
        $partial_output->startElement('users');
        foreach (User::searchUsers($search_string) as $user){
            $partial_output->insert($user->asXML());
        }
        $partial_output->endElement();
        return $partial_output->asString();
    }
    
    /**
     * Get the role of the current user
     * @return string Role identifier
     */
    public static function getRole() {
	    if (!User::$logged_in) {
	        return 'unregistered';
	    } else {
            try {
                $role_result = DBConnection::get()->query(
                        'SELECT `role`
                         FROM `'.DB_PREFIX.'users`
                         WHERE `user` = :user',
                        DBConnection::FETCH_ALL,
                        array(':user' => $_SESSION['user']));
                if (count($role_result) === 0) return 'unregistered';
                return $role_result[0]['role'];
            } catch (DBException $e) {
                return 'unregistered';
            }
        }
    }
}

User::init();

function loadUsers()
{
    global $js;
    $users = new coreUser();
    $users = $users->getAll();
    echo <<< EOF
<ul>
<li>
<a class="menu-item" href="javascript:loadFrame({$_SESSION['userid']},'users-panel.php')">
<img class="icon" src="image/user.png" />
EOF;
    echo htmlspecialchars(_('Me')) . '</a></li>';
    foreach ($users as $user) {
        // Make sure that the user is active, or the viewer has permission to
        // manage this type of user
        if ($_SESSION['role']['manage' . $user['role'] . 's']
                || $user['active'] == 1
        ) {
            echo '<li><a class="menu-item';
            if ($user['active'] == 0) {
                echo ' unavailable';
            }
            echo '" href="javascript:loadFrame(' . $user['id'] . ',\'users-panel.php\')">';
            echo '<img class="icon"  src="image/user.png" />';
            echo $user['user'] . "</a></li>";
            // When running for the list of users, check if we want to load this
            // user's profile. Doing this here is more efficient than searching
            // for the user name with another query. Also, leaving this here
            // cause the lookup to fail if permissions were invalid.
            if ($user['user'] === $_GET['user']) {
                $js .= 'loadFrame(' . $user['id'] . ',\'users-panel.php\')';
            }
        }
    }
    echo "</ul>";

}