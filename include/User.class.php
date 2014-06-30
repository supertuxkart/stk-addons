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
     * Flag to indicate if the a user is logged in
     * @var bool
     */
    protected static $logged_in = false;

    /**
     * Current user id that is logged in. Keep it around because we do not want to check the session every time.
     * @var int
     */
    protected static $logged_user_id = -1;

    /**
     * Required session vars to be a valid session. All user vars are under the "user" key
     * @var array
     */
    protected static $sessionRequired = array("id", "user_name", "real_name", "date_last_login", "role", "permissions");

    /**
     * The id of the user
     * @var int
     */
    protected $id = -1;

    /**
     * @var array
     */
    protected $userData = array();

    /**
     * @param int    $id
     * @param array  $userData retrieved from the database
     */
    public function __construct($id, array $userData = array())
    {
        $this->id = $id;
        $this->userData = $userData;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->userData["user"];
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return static::oldRoleToNew($this->userData["role"]);
    }

    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->userData["name"];
    }

    /**
     * @return string
     */
    public function getHomepage()
    {
        return $this->userData["homepage"];
    }

    /**
     * @return mixed
     */
    public function getAvatar()
    {
        return $this->userData["avatar"];
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->userData["active"] === 1;
    }

    /**
     * @return string
     */
    public function getDateLogin()
    {
        return $this->userData["last_login"];
    }

    /**
     * @return string
     */
    public function getDateRegistration()
    {
        return $this->userData["reg_date"];
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
            throw new UserException(sprintf("Addon type=%s does not exist", $type));
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
            throw new UserException(_h("A database error occurred"));
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

        if (Validate::password($old_password, null, static::sessionGet("user_name")) !== $this->userData['pass'])
        {
            throw new UserException(_h('Your old password is not correct.'));
        }

        if (static::getLoggedId() === $this->id)
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
        if(!static::hasPermissionOnRole($this->userData['role']))
        {
            // we do not have permission
            return false;
        }

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
            DBConnection::get()->update(
                "users",
                "`id` = :id",
                array(
                    ":id"     => $this->id,
                    ":active" => $available
                ),
                array(
                    ":id" => DBConnection::PARAM_INT,
                    ":active" => DBConnection::PARAM_INT
                )
            );
        }
        catch(DBException $e)
        {
            return false;
        }

        // change role, the current user can change to that role only if he can edit that role
        if ($role && static::hasPermissionOnRole($role))
        {
            try
            {
                DBConnection::get()->update(
                    "users",
                    "`id` = :id",
                    array(
                        ":id"   => $this->id,
                        ":role" => $role
                    ),
                    array(":id" => DBConnection::PARAM_INT)
                );
            }
            catch(DBException $e)
            {
                return false;
            }
        }

        // success
        return true;
    }

    /**
     * Get the user as a xml structure
     *
     * @param string $tag the root tag for the user xml
     *
     * @return string
     */
    public function asXML($tag = 'user')
    {
        $user_xml = new XMLOutput();
        $user_xml->startElement($tag);
        $user_xml->writeAttribute('id', $this->getUserId());
        $user_xml->writeAttribute('user_name', $this->getUserName());
        $user_xml->endElement();

        return $user_xml->asString();
    }

    /**
     * Set a value in the session for the user
     *
     * @param mixed $key
     * @param mixed $value
     */
    protected static function sessionSet($key, $value)
    {
        $_SESSION["user"][$key] = $value;
    }

    /**
     * Get a key from the session for the user
     *
     * @param mixed $key
     *
     * @return mixed
     */
    protected  static function sessionGet($key)
    {
        return $_SESSION["user"][$key];
    }

    /**
     * See if the key exists in the user session
     *
     * @param mixed $key
     *
     * @return bool
     */
    protected static function sessionExists($key)
    {
        return isset($_SESSION["user"][$key]);
    }

    /**
     * Init the session. Should be called only once (by login)
     *
     * @param int $user_id
     */
    protected static function sessionInit($user_id)
    {
        static::sessionStart();

        if(!isset($_SESSION["user"]))
        {
            $_SESSION["user"] = array();
            static::$logged_in = true;
            static::$logged_user_id = $user_id;
        }
        else
        {
            trigger_error("initSession has found a session that is already started. Maybe it is not used right");
        }
    }

    /**
     * Clear the session data associated with the user
     */
    protected static function sessionClear()
    {
        static::sessionStart();

        unset($_SESSION["user"]);
        static::$logged_in = false;
        static::$logged_user_id = -1;
    }

    /**
     * Start a session, only if was no previous started
     */
    protected static function sessionStart()
    {
        // session is already started
        if(session_id() === "")
        {
            session_name("STK_SESSID");
            if(!session_start())
            {
                trigger_error("Session failed to start");
            }
        }
    }

    /**
     * Init the user session and do some validation on the current data in the session.
     * This should be called once per user
     *
     * @throws UserException
     */
    public static function init()
    {
        // do not init session if we are in the api part area
        if (defined('API'))
        {
            return;
        }

        // start session
        static::sessionStart();

        // Check if any session variables are not set
        foreach(static::$sessionRequired as $key)
        {
            // One or more of the session variables was not set - this may be an issue, so force logout
            if(!static::sessionExists($key))
            {
//                trigger_error(sprintf("Session key = '%s' was not set", $key));
//                var_debug("Init");

                static::logout();

                return;
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
                    ':username'  => static::sessionGet('user_name'),
                    ':lastlogin' => static::sessionGet('date_last_login'),
                    ':realname'  => static::sessionGet('real_name')
                )
            );
        }
        catch(DBException $e)
        {
            throw new UserException(h(
                _('An error occurred trying to validate your session.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }


        if ($count !== 1)
        {
            static::logout();

            return;
        }

        static::$logged_in = true;
        static::$logged_user_id = static::sessionGet("id");
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
            throw new UserException(_h('Your username or password is incorrect.'));
        }


        $user = $result[0];

        // init session vars
        static::sessionInit($user["id"]);
        static::sessionSet("id", $user["id"]);
        static::sessionSet("user_name", $user["user"]);
        static::sessionSet("real_name", $user["name"]);
        static::sessionSet("date_last_login", static::updateLoginTime($user["id"]));
        static::sessionSet("role", $user["role"]);
        static::setPermissions($user["role"]);

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
        static::sessionClear();

        session_destroy();
        static::sessionStart();
    }

    /**
     * @return bool
     */
    public static function isLoggedIn()
    {
        return static::$logged_in;
    }

    /**
     * Get the id of the current logged in user
     *
     * @return int the id of the user or -1 if the user is not logged in
     */
    public static function getLoggedId()
    {
        return static::$logged_user_id;
    }

    /**
     * Get the user name of the current logged in user
     *
     * @return string
     */
    public static function getLoggedUserName()
    {
        return static::isLoggedIn() ? static::sessionGet("user_name") : "";
    }

    /**
     * Get the real name of the current logged in user
     *
     * @return string
     */
    public static function getLoggedRealName()
    {
        return static::isLoggedIn() ? static::sessionGet("real_name") : "anonymous";
    }

    /**
     * Get the role of the current user logged in user
     *
     * @return string return the role or 'unregistered' if the user is not logged int
     */
    public static function getLoggedRole()
    {
        if (!static::isLoggedIn())
        {
            return "unregistered";
        }

        return static::oldRoleToNew(static::sessionGet("role"));
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
            throw new UserException(_h("Error on selecting all users"));
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
                array(':' . $field => $value),
                array(':' . $field => $value_type) // bind value
            );
        }
        catch(DBException $e)
        {
            throw new UserException(h(
                _('An error occurred while performing your search query.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        // empty result
        if (empty($user))
        {
            throw new UserException(h(
                _("Tried to fetch an user that doesn't exist.") . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        // fetch the first
        return new User($user["id"], $user);
    }

    /**
     * Get a user instance by user id
     *
     * @param int $user_id
     *
     * @throws UserException
     * @return User
     */
    public static function getFromID($user_id)
    {
        return static::getFromField("id", $user_id, DBConnection::PARAM_INT);
    }

    /**
     * Get a user instance by the user name
     *
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
        $terms = preg_split("/[\s,]+/", h($search_string));
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
                $users = DBConnection::get()->query(
                    "SELECT id, user
                    FROM `" . DB_PREFIX . "users`
                    WHERE " . implode(" OR ", $query_parts),
                    DBConnection::FETCH_ALL,
                    $parameters
                );
            }
            catch(DBException $e)
            {
                throw new UserException(h(
                    _('An error occurred while performing your search query.') . ' ' .
                    _('Please contact a website administrator.')
                ));
            }

            foreach ($users as $user)
            {
                $matched_users[] = new User($user['id'], $user);
            }
        }

        return $matched_users;
    }

    /**
     * Get the search result as a xml string
     *
     * @param string $search_string
     *
     * @throws UserException
     * @return string
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
     * Convert old role system to new
     *
     * @param string $oldRole
     *
     * @return string
     */
    public static function oldRoleToNew($oldRole)
    {
        // TODO maybe make a script that does this in production
        if($oldRole === "basicUser")
        {
            return "user";
        }
        elseif($oldRole === "root" || $oldRole === "administrator")
        {
            return "admin";
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
        static::sessionSet("permissions", AccessControl::getPermissions(static::oldRoleToNew($role)));
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

        return static::sessionGet("permissions");
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
     * See if the current user has permission over a user (that is he can edit/delete this user)
     *
     * @param string $role the role we want to check that the current user has permission over
     *
     * @return bool
     */
    public static function hasPermissionOnRole($role)
    {
        $role = static::oldRoleToNew($role);
        $can_edit_users = static::hasPermission(AccessControl::PERM_EDIT_USERS);
        $can_edit_admins = static::hasPermission(AccessControl::PERM_EDIT_ADMINS);

        // user can edit other users
        if($can_edit_users)
        {
            $other_role_permission = AccessControl::getPermissions($role);

            // other role is not an admin one, this means we can can edit
            if(!in_array(AccessControl::PERM_EDIT_ADMINS, $other_role_permission))
            {
                return true; // user has permission on role
            }
        }

        // user is admin, he can edit other admins and other users
        // there is no need to check if he has the edit users permission
        if($can_edit_admins)
        {
            return true;
        }

        return false; // user does not have permission on role
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
            throw new InvalidArgumentException(_h("User id is not set"));
        }

        try
        {
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "users`
                SET `last_login` = NOW()
                WHERE `id` = :userid",
                DBConnection::NOTHING,
                array(':userid' => $userid)
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
            throw new UserException(h(
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
                throw new UserException(_h('You must be logged in to change a password.'));
            }
            else
            {
                $user_id = static::getLoggedId();
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
            throw new UserException(h(
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
                throw new UserException(_h('Current password invalid.'));
            }

            $new_hashed = Validate::password($new1, $new2);
            static::changePassword($new_hashed, $userid);
            DBConnection::get()->commit();

        }
        catch(DBException $e)
        {
            throw new UserException(h(
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
            throw new UserException(h(
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
                throw new UserException($e->getMessage() . ' ' . _h('Please contact a website administrator.'));
            }
            Log::newEvent("Password reset request for user '$username'");

        }
        catch(DBException $e)
        {
            throw new UserException(h(
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
        Validate::checkbox($terms, _h('You must agree to the terms to register.'));
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
            throw new UserException(h(
                _('An error occurred trying to validate your username.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
        if (!empty($result))
        {
            throw new UserException(_h('This username is already taken.'));
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
            throw new UserException(h(
                _('An error occurred trying to validate your email address.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
        if (!empty($result))
        {
            throw new UserException(_h('This email address is already taken.'));
        }

        // No exception occurred - continue with registration
        try
        {
            $count = DBConnection::get()->insert(
                "users",
                array(
                    ":user" => $username,
                    ":pass" => $password,
                    ":name" => $name,
                    ":email" => $email,
                    "role" => "user",
                    "reg_date" => "CURRENT_DATE()"
                )
            );

            if ($count != 1)
            {
                throw new DBException("Multiple rows affected(or none). Not good");
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
                throw new UserException($e->getMessage() . ' ' . _h('Please contact a website administrator.'));
            }
            Log::newEvent("Registration submitted for user '$username' with id '$userid'.");
        }
        catch(DBException $e)
        {
            throw new UserException(h(
                _('An error occurred while creating your account.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }


    }
}

// start session and validate it
// TODO find better position to put this in
User::init();
