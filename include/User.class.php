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
class User extends Base
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
    protected static $sessionRequired = ["id", "user_name", "real_name", "date_last_login", "role", "permissions"];

    /**
     * @param string $message
     *
     * @throws UserException
     */
    protected static function throwException($message)
    {
        throw new UserException($message);
    }

    /**
     * The id of the user
     * @var int
     */
    protected $id = -1;

    /**
     * @var array
     */
    protected $userData = [];

    /**
     * @param int   $id
     * @param array $userData retrieved from the database
     */
    public function __construct($id, array $userData = [])
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
     * @return string
     */
    public function getEmail()
    {
        return $this->userData["email"];
    }

    /**
     * @return mixed
     */
    public function getPassword()
    {
        return $this->userData["pass"];
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
        return (int)$this->userData["active"] === 1;
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
                [":uploader" => $this->id, ":addon_type" => $type],
                [":uploader" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new UserException(_h("A database error occurred"));
        }

        return $addons;
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
        $user_xml->writeAttribute('id', $this->getId());
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
    protected static function sessionGet($key)
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

        if (!isset($_SESSION["user"]))
        {
            $_SESSION["user"] = [];
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
        if (session_id() === "")
        {
            session_name("STK_SESSID");
            if (!session_start())
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
        foreach (static::$sessionRequired as $key)
        {
            // One or more of the session variables was not set - this may be an issue, so force logout
            if (!static::sessionExists($key))
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
                [
                    ':username'  => static::sessionGet('user_name'),
                    ':lastlogin' => static::sessionGet('date_last_login'),
                    ':realname'  => static::sessionGet('real_name')
                ]
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
        try
        {
            $user = Validate::credentials($password, $username, Validate::CREDENTIAL_USERNAME);
        }
        catch(UserException $e)
        {
            static::logout();
            throw new UserException($e->getMessage());
        }

        $id = $user->getId();
        $role = $user->getRole();

        // init session vars
        static::sessionInit($id);
        static::sessionSet("id", $id);
        static::sessionSet("user_name", $user->getUserName());
        static::sessionSet("real_name", $user->getRealName());
        static::sessionSet("date_last_login", static::updateLoginTime($id));
        static::sessionSet("role", $role);
        static::setPermissions($role);

        // backwards compatibility. Convert unsalted password to a salted one
        if (!Util::isPasswordSalted($user->getPassword())) // TODO check server because the master repo had this implemented wrong
        {
            $password = Util::getPasswordHash($password);
            static::changePassword($id, $password);
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
        return static::isLoggedIn() ? static::sessionGet("real_name") : "";
    }

    /**
     * Get the role of the current user logged in user
     *
     * @return string return the role or 'unregistered' if the user is not logged int
     */
    public static function getLoggedRole()
    {
        return static::isLoggedIn() ? static::oldRoleToNew(static::sessionGet("role")) : "unregistered";
    }

    /**
     * Get all the users from the database in an associative array
     *
     * @param int $limit
     * @param int $current_page
     *
     * @return array|int
     * @throws UserException
     */
    public static function getAll($limit = -1, $current_page = 1)
    {
        return static::getAllFromTable("users", "ORDER BY `user` ASC, `id` ASC", $limit, $current_page);
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
        $data = static::getFromField("users", "id", $user_id, DBConnection::PARAM_INT, "User does not exist");

        return new User($data["id"], $data);
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
        $data = static::getFromField("users", "user", $username, DBConnection::PARAM_STR, "User does not exist");

        return new User($data["id"], $data);
    }

    /**
     * Search a user
     *
     * @param string $search_string   the string can pe space or comma separated to search multiple users
     * @param bool   $return_instance flag that indicates if we want to return an array of instances
     *
     * @throws UserException
     * @return User[]|array array of users
     */
    public static function search($search_string, $return_instance = true)
    {
        // split by space or comma
        $terms = preg_split("/[\s,]+/", $search_string);
        $index = 0;
        $parameters = [];
        $query_parts = [];
        foreach ($terms as $term)
        {
            if (mb_strlen($term) > 2)
            {
                // build sql query
                $parameter = ":userid" . $index;
                $index++;
                $query_parts[] = "`user` RLIKE " . $parameter;
                $parameters[$parameter] = $term;
            }
        }

        // nothing to search for
        if (!$index)
        {
            return [];
        }

        try
        {
            $users = DBConnection::get()->query(
                "SELECT id, user, role, active
                FROM `" . DB_PREFIX . "users`
                WHERE " . implode(" OR ", $query_parts),
                DBConnection::FETCH_ALL,
                $parameters
            );
        }
        catch(DBException $e)
        {
            throw new UserException(h(
                _('An error occurred while performing your search query.') . ' .' .
                _('Please contact a website administrator.')
            ));
        }

        $matched_users = [];
        if ($return_instance)
        {
            foreach ($users as $user)
            {
                $matched_users[] = new User($user['id'], $user);
            }
        }
        else
        {
            $matched_users = $users;
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
        foreach (static::search($search_string) as $user)
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
        if ($oldRole === "basicUser")
        {
            return "user";
        }
        elseif ($oldRole === "root" || $oldRole === "administrator")
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
        return static::isLoggedIn() ? static::sessionGet("permissions") : [];
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
        return $permission ? in_array($permission, static::getPermissions()) : false;
    }

    /**
     * Check if current user is an admin one
     *
     * @return bool
     */
    public static function isAdmin()
    {
        return static::hasPermission(AccessControl::PERM_EDIT_ADMINS);
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
        if (!static::isLoggedIn()) // do not bother to check permissions if the user is not logged in
        {
            return false;
        }

        $role = static::oldRoleToNew($role);
        $can_edit_users = static::hasPermission(AccessControl::PERM_EDIT_USERS);
        $can_edit_admins = static::isAdmin();

        // user is admin, he can edit other admins and other users
        // there is no need to check if he has the edit users permission
        if ($can_edit_admins)
        {
            return true;
        }

        // user can edit other users
        if ($can_edit_users)
        {
            $other_role_permission = AccessControl::getPermissions($role);

            // other role is not an admin one, this means we can can edit
            if (!in_array(AccessControl::PERM_EDIT_ADMINS, $other_role_permission))
            {
                return true; // user has permission on role
            }
        }

        return false; // user does not have permission on role
    }

    /**
     * Get the total number of users
     *
     * @return int
     * @throws UserException
     */
    public static function count()
    {
        try
        {
            $count = DBConnection::get()->count("users");
        }
        catch(DBException $e)
        {
            throw new UserException(_h("Tried to count the number of users"));
        }

        return $count;
    }

    /**
     * Set the user config, only if the current user has permissions
     *
     * @param int    $user_id
     * @param string $homepage  the new homepage
     * @param string $real_name the new name
     *
     * @throws UserException
     */
    public static function updateProfile($user_id, $homepage, $real_name)
    {
        // throw exception if something is wrong (the user does not exist, or a database error)
        $user = static::getFromID($user_id);

        // verify permissions
        $isOwner = (User::getLoggedId() === $user->getId());
        $canEdit = static::hasPermissionOnRole($user->getRole());
        if (!$isOwner && !$canEdit)
        {
            throw new UserException(_h("You do not have the permission to update the profile"));
        }

        // clean
        $homepage = h($homepage);
        $real_name = h($real_name);

        try
        {
            DBConnection::get()->update(
                "users",
                "`id` = :id",
                [
                    ":id"       => $user->getId(),
                    ":homepage" => $homepage,
                    ":name"     => $real_name
                ],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new UserException(h(
                _('An error occurred while updating the profile') . '. ' .
                _('Please contact a website administrator.')
            ));
        }
    }

    /**
     * Set the user role and availability.
     * Only a select few users can call this function.
     *
     * @param int    $user_id
     * @param string $role
     * @param string $available
     *
     * @throws UserException
     */
    public static function updateRole($user_id, $role, $available)
    {
        // validate
        if (!AccessControl::isRole($role))
        {
            throw new UserException(_h("The role specified is not valid"));
        }

        $canEdit = static::hasPermissionOnRole($role);
        if (!$canEdit)
        {
            throw new UserException(_h("You do not have the permission to edit the role"));
        }

        // also does validation
        $user = static::getFromID($user_id);
        $available = Util::getCheckboxInt($available);

        // can not edit your own role
        if ($user->getId() === User::getLoggedId())
        {
            throw new UserException(_h("You can not edit your own role"));
        }

        // update
        try
        {
            DBConnection::get()->update(
                "users",
                "`id` = :id",
                [
                    ":id"     => $user->getId(),
                    ":role"   => $role,
                    ":active" => $available
                ],
                [":id" => DBConnection::PARAM_INT, ":active" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new UserException(h(
                _('An error occurred while updating the role') . '. ' .
                _('Please contact a website administrator.')
            ));
        }
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
        if (!$userid)
        {
            throw new InvalidArgumentException(_h("User id is not set"));
        }

        try
        {
            // TODO use one query
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "users`
                SET `last_login` = NOW()
                WHERE `id` = :userid",
                DBConnection::NOTHING,
                [':userid' => $userid]
            );
            $user = DBConnection::get()->query(
                "SELECT `last_login`
                FROM `" . DB_PREFIX . "users`
                WHERE `id` = :userid",
                DBConnection::FETCH_FIRST,
                [':userid' => $userid]
            );
        }
        catch(DBException $e)
        {
            static::logout();
            throw new UserException(h(
                _('An error occurred while recording last login time.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        return $user['last_login'];
    }

    /**
     * Change the password of the supplied user.
     * Use with care
     *
     * @param int    $user_id
     * @param string $hash_new_password the new password hash
     *
     * @throws UserException
     */
    public static function changePassword($user_id, $hash_new_password)
    {
        try
        {
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "users`
                SET `pass`   = :pass
    	        WHERE `id` = :userid",
                DBConnection::ROW_COUNT,
                [':userid' => $user_id, ':pass' => $hash_new_password],
                [":userid" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new UserException(h(
                _('An error occured while trying to change your password.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        if (!$count)
        {
            throw new UserException("Change password no rows affected");
        }
    }

    /**
     * Verify and change to a new password
     *
     * @param string $current_password
     * @param string $new_password
     * @param string $new_password_verify
     * @param int    $user_id
     *
     * @throws UserException
     */
    public static function verifyAndChangePassword($current_password, $new_password, $new_password_verify, $user_id)
    {
        // verify it they added their password correctly, throws exception
        $new_password_hash = Validate::newPassword($new_password, $new_password_verify);

        try
        {
            DBConnection::get()->beginTransaction();

            $user = Validate::credentials($current_password, $user_id, Validate::CREDENTIAL_ID);

            // only user can change his password
            if ($user->getId() !== $user_id)
            {
                throw new UserException(_h('You do not have the permission to change the password'));
            }

            static::changePassword($user_id, $new_password_hash);
            DBConnection::get()->commit();
        }
        catch(DBException $e)
        {
            DBConnection::get()->rollback();
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
                [":userid" => $userid],
                [":userid" => DBConnection::PARAM_INT]
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
        $password_hash = Validate::newPassword($password, $password_conf);
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
                [':username' => $username]
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
                [':email' => $email]
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
                [
                    ":user"    => $username,
                    ":pass"    => $password_hash,
                    ":name"    => $name,
                    ":email"   => $email,
                    "role"     => "'user'",
                    "reg_date" => "CURRENT_DATE()"
                ]
            );

            if ($count !== 1)
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
