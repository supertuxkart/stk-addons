<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *                2013 Glenn De Jonghe
 *                2014 Daniel Butum <danibutum at gmail dot com>
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

/**
 * Class User
 */
class User
{
    /**
     * Is the current user logged in
     * @var bool
     */
    protected static $logged_in = false;

    /**
     * Current user id
     * @var int
     */
    protected static $user_id = 0;

    /**
     * Required session vars to be a valid session
     * @var array
     */
    protected static $sessionRequired = array("userid", "user", "real_name", "last_login", "role");

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var string
     */
    protected $userName = "";

    /**
     * @var array
     */
    protected $userData = array();

    /**
     * @param int    $id
     * @param string $userName
     * @param array  $userData
     */
    public function __construct($id, $userName, array $userData = array())
    {
        $this->id = $id;
        $this->userName = $userName;
        $this->userData = $userData;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @return int
     */
    public function getUserID()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUserRole()
    {
        return $this->userData["role"];
    }

    /**
     * @return string
     */
    public function getUserFullName()
    {
        return $this->userData["name"];
    }

    /**
     * @return array
     */
    public function getUserData()
    {
        return $this->userData;
    }

    /**
     * @param string $type
     *
     * @return array|int|null
     * @throws UserException
     */
    public function getAddonsData($type)
    {
        if (!Addon::isAllowedType($type))
        {
            if (DEBUG_MODE)
            {
                throw new UserException(sprintf("Addon type=%s does not exist", $type));
            }

            return array();
        }

        try
        {
            $addons = DBConnection::get()->query(
                'SELECT `a`.*, `r`.`status`
                FROM `' . DB_PREFIX . 'addons` `a`
                LEFT JOIN `' . DB_PREFIX . $type . '_revs` `r`
                ON `a`.`id` = `r`.`addon_id`
                WHERE `a`.`uploader` = :uploader
                AND `a`.`type` = :addon_type',
                DBConnection::FETCH_ALL,
                array(
                    ":uploader"   => (int)$this->id,
                    ":addon_type" => $type,
                )
            );
        }
        catch(DBException $e)
        {
            if (DEBUG_MODE)
            {
                throw new UserException("A database error occured");
            }

            return array();
        }

        return $addons;
    }

    /**
     * Validate and set the user password
     *
     * @param string $old_password
     * @param string $new_password_1
     * @param string $new_password_2
     *
     * @return bool true on success
     * @throws UserException when old password does not match
     */
    public function setPass($old_password, $new_password_1, $new_password_2)
    {
        // TODO: FIX error message on old password
        $new_password = Validate::password($new_password_1, $new_password_2);

        if (Validate::password($old_password, null, $_SESSION['user']) !== $this->userData['pass'])
        {
            throw new UserException(htmlspecialchars(_('Your old password is not correct.')));
        }

        if (static::getId() === $this->id)
        {
            static::changePassword($new_password);
        }

        return true;
    }

    /**
     * TODO throw exceptions
     * Set the user config, only if the current user has permissions
     *
     * @param null $available the user active option
     * @param null $role      the role of the user
     *
     * @return bool true on success false otherwise
     */
    public function setConfig($available = null, $role = null)
    {
        if (static::hasPermissionOnRole($this->userData['role']))
        {

            // Set availability status
            if ($available === 'on')
            {
                $available = 1;
            }
            else
            {
                $available = 0;
            }
            try
            {
                DBConnection::get()->query(
                    'UPDATE ' . DB_PREFIX . 'users
                    SET `active` = :active
                    WHERE `id` = :id',
                    DBConnection::NOTHING,
                    array(
                        ":id"     => (int)$this->id,
                        ":active" => $available,
                    )
                );
            }
            catch(DBException $e)
            {
                return false;
            }

            // Set permission level
            if ($role)
            {
                if (static::hasPermissionOnRole($role))
                {
                    try
                    {
                        DBConnection::get()->query(
                            'UPDATE ' . DB_PREFIX . 'users fdssdf
                            SET `role` = :role
                            WHERE `id` = :id',
                            DBConnection::NOTHING,
                            array(
                                ":id"   => (int)$this->id,
                                ":role" => $role,
                            )
                        );
                    }
                    catch(DBException $e)
                    {
                        return false;
                    }
                }
            }

            // success
            return true;
        }

        // we do not have permission
        return false;
    }

    /**
     * @param string $tag
     *
     * @return string
     */
    public function asXML($tag = 'user')
    {
        $user_xml = new XMLOutput();
        $user_xml->startElement($tag);
        $user_xml->writeAttribute('id', $this->id);
        $user_xml->writeAttribute('user_name', $this->userName);
        $user_xml->endElement();

        return $user_xml->asString();
    }


    /**
     * @return bool
     * @throws UserException
     */
    public static function init()
    {
        if (defined('API'))
        {
            return null;
        }

        // Validate user's session on every page
        if (session_id() === "")
        {
            session_name("STK_SESSID");
            session_start();
        }

        // Check if any session variables are not set
        foreach(static::$sessionRequired as $key)
        {
            // One or more of the session variables was not set - this may be an issue, so force logout
            if(!isset($_SESSION[$key]))
            {
                if (DEBUG_MODE)
                {
                    echo sprintf("Session key = '%s' was not set", $key);
                    //var_debug("Init");
                }
                static::logout();

                return null;
            }
        }

        // Validate session if complete set of variables is available
        try
        {
            $count = DBConnection::get()->query(
                "SELECT `id`,`user`,`name`,`role`
    	        FROM `" . DB_PREFIX . "users`
                WHERE `user` = :username
                AND `last_login` = :lastlogin
                AND `name` = :realname
                AND `active` = 1",
                DBConnection::ROW_COUNT,
                array(
                    ':username'  => (string)$_SESSION['user'],
                    ':lastlogin' => $_SESSION['last_login'],
                    ':realname'  => (string)$_SESSION['real_name']
                )
            );
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred trying to validate your session.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }


        if ($count !== 1)
        {
            static::logout();

            return false;
        }
        static::$user_id = $_SESSION['userid'];
        static::$logged_in = true;
        //var_debug("Init");
    }

    /**
     * @return bool
     */
    public static function isLoggedIn()
    {
        return static::$logged_in;
    }

    /**
     * @return int
     */
    public static function getId()
    {
        return static::$user_id;
    }

    /**
     * @param $id
     */
    public static function setId($id)
    {
        static::$user_id = $id;
    }

    /**
     * Get all the users from the database in an associative array
     *
     * @return array|int
     * @throws UserException
     */
    public static function getAllData()
    {
        try
        {
            $users = DBConnection::get()->query(
                'SELECT * FROM ' . DB_PREFIX . 'users
                ORDER BY `user` ASC, `id` ASC',
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            if (DEBUG_MODE)
            {
                throw new UserException("Error on selecting all users");
            }

            return array();
        }

        return $users;
    }

    /**
     * @param string $field
     * @param mixed  $value
     * @param int    $value_type
     *
     * @return User
     * @throws UserException
     */
    protected static function getFromField($field, $value, $value_type = DBConnection::PARAM_STR)
    {
        try
        {
            $user = DBConnection::get()->query(
                "SELECT *
                FROM `" . DB_PREFIX . "users`
                WHERE " . sprintf("`%s` = :%s", $field, $field),
                DBConnection::FETCH_FIRST,
                array(
                    ':' . $field => $value
                ),
                array(
                    ':' . $field => $value_type // value to bind to see DBConnection
                )
            );

            // empty result
            if (empty($user))
            {
                throw new UserException(htmlspecialchars(
                    _("Tried to fetch an user that doesn't exist.") . ' ' .
                    _('Please contact a website administrator.')
                ));
            }

            // fetch the first
            return new User($user["id"], $user['user'], $user);
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred while performing your search query.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
    }

    /**
     * @param int $id
     *
     * @throws UserException
     * @return User
     */
    public static function getFromID($id)
    {
        return static::getFromField("id", $id, DBConnection::PARAM_INT);
    }

    /**
     * @param string $username
     *
     * @throws UserException
     * @return User
     */
    public static function getFromUserName($username)
    {
        return static::getFromField("user", $username, DBConnection::PARAM_STR);
    }

    /**
     *
     * @param string $search_string
     *
     * @throws UserException
     * @return array of Users
     */
    public static function searchUsers($search_string)
    {
        $terms = preg_split("/[\s,]+/", strip_tags($search_string));
        $index = 0;
        $parameters = array();
        $query_parts = array();
        foreach ($terms as $term)
        {
            if (strlen($term) > 2)
            {
                $parameter = ":userid" . $index;
                $index++;
                $query_parts[] = "`user` RLIKE " . $parameter;
                $parameters[$parameter] = $term;
            }
        }
        $matched_users = array();
        if ($index > 0)
        {
            try
            {
                $result = DBConnection::get()->query(
                    "SELECT id, user
                    FROM `" . DB_PREFIX . "users`
                    WHERE " . implode(" OR ", $query_parts),
                    DBConnection::FETCH_ALL,
                    $parameters
                );
                foreach ($result as $user)
                {
                    $matched_users[] = new User($user['id'], $user['user']);
                }
            }
            catch(DBException $e)
            {
                throw new UserException(htmlspecialchars(
                    _('An error occurred while performing your search query.') . ' ' .
                    _('Please contact a website administrator.')
                ));
            }
        }

        return $matched_users;
    }

    /**
     *
     * @param string $search_string
     *
     * @throws UserException
     * @return multitype:User
     */
    public static function searchUsersAsXML($search_string)
    {
        $partial_output = new XMLOutput();
        $partial_output->startElement('users');
        foreach (static::searchUsers($search_string) as $user)
        {
            $partial_output->insert($user->asXML());
        }
        $partial_output->endElement();

        return $partial_output->asString();
    }

    /**
     * Get the role of the current user
     * @return string Role identifier
     */
    public static function getRole()
    {
        if (!static::isLoggedIn())
        {
            return 'unregistered';
        }
        else // retrieve from database
        {
            try
            {
                $role_result = DBConnection::get()->query(
                    'SELECT `role`
                     FROM `' . DB_PREFIX . 'users`
                     WHERE `user` = :user',
                    DBConnection::FETCH_FIRST,
                    array(':user' => $_SESSION['user'])
                );
                if (empty($role_result))
                {
                    return 'unregistered';
                }

                // backwards compatibility

                return static::oldRoleToNew($role_result['role']);
            }
            catch(DBException $e)
            {
                return 'unregistered';
            }
        }
    }

    /**
     * Convert old role system to new
     *
     * @param string $oldRole
     *
     * @return string
     */
    public static function oldRoleToNew($oldRole)
    {
        if($oldRole === 'basicUser')
        {
            return 'user';
        }

        return $oldRole;
    }

    /**
     * Set the permission in the session
     *
     * @param string $role
     */
    protected static function setPermissions($role)
    {
        $_SESSION["role"] = AccessControl::getPermissions(static::oldRoleToNew($role));
    }

    /**
     * Get the permission for the session
     *
     * @return array
     */
    public static function getPermissions()
    {
        if(!static::isLoggedIn())
        {
            return array();
        }

        return $_SESSION["role"];
    }

    /**
     * Check if current user has the permission
     *
     * @param string $permission
     *
     * @return bool
     */
    public static function hasPermission($permission)
    {
        return in_array($permission, static::getPermissions());
    }

    /**
     * See if the current user has permission over a user
     *
     * @param string $role_singular
     *
     * @return bool
     */
    public static function hasPermissionOnRole($role_singular)
    {
        return static::hasPermission('edit' . ucfirst(static::oldRoleToNew($role_singular)) . 's');
    }


    /**
     * Try to log in a user
     *
     * @param string $username
     * @param string $password
     *
     * @throws UserException on invalid credentials
     */
    public static function login($username, $password)
    {
        $result = Validate::credentials($username, $password);

        // Check if the user exists
        if (count($result) !== 1)
        {
            static::logout();
            throw new UserException(htmlspecialchars(_('Your username or password is incorrect.')));
        }

        static::$user_id = $result[0]['id'];
        static::$logged_in = true;

        $_SESSION['userid'] = $result[0]["id"];
        $_SESSION['user'] = $result[0]["user"];
        $_SESSION['real_name'] = $result[0]["name"];
        $_SESSION['last_login'] = static::updateLoginTime(static::getId());
        static::setPermissions($result[0]["role"]);


        // backwards compatibility. Convert unsalted password to a salted one
        if (strlen($password) === 64)
        {
            $password = Validate::password($password);
            static::changePassword($password);
            Log::newEvent("Converted the password of '$username' to use a password salting algorithm");
        }
    }

    /**
     * Logout the user
     */
    public static function logout()
    {
        foreach(static::$sessionRequired as $key)
        {
            unset($_SESSION[$key]);
        }

        session_destroy();
        session_start();
        static::$user_id = 0;
        static::$logged_in = false;
    }

    /**
     * @param $userid
     *
     * @return mixed
     * @throws UserException
     * @throws InvalidArgumentException
     */
    public static function updateLoginTime($userid)
    {
        if(!$userid)
        {
            throw new InvalidArgumentException("User id is not set");
        }

        try
        {
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "users`
                SET `last_login` = NOW()
                WHERE `id` = :userid",
                DBConnection::NOTHING,
                array
                (
                    ':userid' => $userid
                )
            );
            $result = DBConnection::get()->query(
                "SELECT `last_login`
                FROM `" . DB_PREFIX . "users`
                WHERE `id` = :userid",
                DBConnection::FETCH_ALL,
                array
                (
                    ':userid' => $userid
                )
            );
            if (count($result) !== 1)
            {
                throw new DBException();
            }

            return $result[0]['last_login'];
        }
        catch(DBException $e)
        {
            static::logout();
            throw new UserException(htmlspecialchars(
                _('An error occurred while recording last login time.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
    }

    /**
     * Change the password of the supplied user; if none supplied, currently logged in user is used.
     *
     * @param string $new_password
     * @param int    $user_id defaults to currently logged in user.
     *
     * @throws UserException
     */
    public static function changePassword($new_password, $user_id = 0)
    {
        if ($user_id === 0)
        {
            if (!static::isLoggedIn())
            {
                throw new UserException(htmlspecialchars(_('You must be logged in to change a password.')));
            }
            else
            {
                $user_id = static::getId();
            }
        }

        try
        {
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "users`
                SET `pass`   = :pass
    	        WHERE `id` = :userid",
                DBConnection::ROW_COUNT,
                array(
                    ':userid' => (int)$user_id,
                    ':pass'   => (string)$new_password
                )
            );
            if ($count === 0)
            {
                throw new DBException();
            }
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occured while trying to change your password.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
    }

    /**
     * @param string $current
     * @param string $new1
     * @param string $new2
     * @param string $userid
     *
     * @throws UserException
     */
    public static function verifyAndChangePassword($current, $new1, $new2, $userid)
    {
        try
        {
            DBConnection::get()->beginTransaction();
            $count = DBConnection::get()->query(
                "SELECT `id`
                FROM `" . DB_PREFIX . "users`
                WHERE `id` = :userid AND `pass` = :pass",
                DBConnection::ROW_COUNT,
                array
                (
                    ':userid' => (int)$userid,
                    ':pass'   => Validate::password($current, null, null, $userid)
                )
            );

            if ($count < 1)
            {
                throw new UserException(htmlspecialchars(_('Current password invalid.')));
            }

            $new_hashed = Validate::password($new1, $new2);
            static::changePassword($new_hashed, $userid);
            DBConnection::get()->commit();

        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occured while trying to change your password.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

    }

    /**
     * Activate a new user
     *
     * @param int    $userid
     * @param string $ver_code
     *
     * @throws UserException when activation failed
     */
    public static function activate($userid, $ver_code)
    {
        Verification::verify($userid, $ver_code);
        try
        {
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "users`
                SET `active` = '1' 
    	        WHERE `id` = :userid",
                DBConnection::ROW_COUNT,
                array(
                    ':userid' => $userid
                )
            );
            if ($count === 0)
            {
                throw new DBException();
            }
            Verification::delete($userid);
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred trying to activate your useraccount.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
        Log::newEvent("User with ID '{$userid}' activated.");
    }

    /**
     * @param string $username
     * @param string $email
     *
     * @throws UserException
     */
    public static function recover($username, $email)
    {
        // Check all form input
        $username = Validate::username($username);
        $email = Validate::email($email);
        try
        {
            $userid = Validate::account($username, $email);
            $verification_code = Verification::generate($userid);

            // Send verification email
            try
            {
                $mail = new SMail;
                $mail->passwordResetNotification($email, $userid, $username, $verification_code, 'password-reset.php');
            }
            catch(Exception $e)
            {
                Log::newEvent('Password reset email for "' . $username . '" could not be sent.');
                throw new UserException($e->getMessage() . ' ' . _('Please contact a website administrator.'));
            }
            Log::newEvent("Password reset request for user '$username'");

        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred trying to validate your username and email-address for password reset.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
    }

    /**
     * Register a new user account
     *
     * @param string $username Must be unique
     * @param string $password
     * @param string $password_conf
     * @param string $email    Must be unique
     * @param string $name
     * @param string $terms
     *
     * @throws UserException
     */
    public static function register($username, $password, $password_conf, $email, $name, $terms)
    {
        // Sanitize inputs
        $username = Validate::username($username);
        $password = Validate::password($password, $password_conf);
        $email = Validate::email($email);
        $name = Validate::realName($name);
        $terms = Validate::checkbox($terms, htmlspecialchars(_('You must agree to the terms to register.')));
        DBConnection::get()->beginTransaction();

        // Make sure requested username is not taken
        try
        {
            $result = DBConnection::get()->query(
                "SELECT `user` 
    	        FROM `" . DB_PREFIX . "users`
    	        WHERE `user` LIKE :username",
                DBConnection::FETCH_FIRST,
                array(
                    ':username' => $username
                )
            );
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred trying to validate your username.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
        if (!empty($result))
        {
            throw new UserException(htmlspecialchars(
                _('This username is already taken.')
            ));
        }

        // Make sure the email address is unique
        try
        {
            $result = DBConnection::get()->query(
                "SELECT `email` 
    	        FROM `" . DB_PREFIX . "users`
    	        WHERE `email` LIKE :email",
                DBConnection::FETCH_FIRST,
                array(
                    ':email' => $email
                )
            );
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred trying to validate your email address.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
        if (!empty($result))
        {
            throw new UserException(htmlspecialchars(
                _('This email address is already taken.')
            ));
        }

        // No exception occurred - continue with registration
        try
        {
            $count = DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "users`
                 (`user`,`pass`,`name`, `role`, `email`, `active`, `reg_date`)
                 VALUES(:username, :password, :name, :role, :email, 0, CURRENT_DATE())",
                DBConnection::ROW_COUNT,
                array
                (
                    ':username' => $username,
                    ':password' => $password,
                    ':name'     => $name,
                    ':role'     => "basicUser",
                    ':email'    => $email
                )
            );
            if ($count != 1)
            {
                throw new DBException();
            }
            $userid = DBConnection::get()->lastInsertId();
            DBConnection::get()->commit();
            $verification_code = Verification::generate($userid);

            // Send verification email
            try
            {
                $mail = new SMail;
                $mail->newAccountNotification($email, $userid, $username, $verification_code, 'register.php');
            }
            catch(Exception $e)
            {
                Log::newEvent("Registration email for user '$username' with id '$userid' failed.");
                throw new UserException($e->getMessage() . ' ' . _('Please contact a website administrator.'));
            }
            Log::newEvent("Registration submitted for user '$username' with id '$userid'.");
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred while creating your account.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }


    }
}
// TODO move init
User::init();
