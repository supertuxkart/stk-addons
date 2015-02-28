<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *           2013      Glenn De Jonghe
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
    const MIN_PASSWORD = 8;

    const MAX_PASSWORD = 60;

    const MIN_USERNAME = 3;

    const MAX_USERNAME = 30;

    const MIN_REALNAME = 2;

    const MAX_REALNAME = 64;

    const MAX_EMAIL = 64;

    const MAX_HOMEPAGE = 64;

    const MAX_AVATAR = 64;

    // fake enumeration
    const PASSWORD_ID = 1;

    const PASSWORD_USERNAME = 2;

    const CREDENTIAL_ID = 1;

    const CREDENTIAL_USERNAME = 2;

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
     * The username
     * @var string
     */
    protected $username;

    /**
     * The real name
     * @var string
     */
    protected $realname;

    /**
     * The password hashed
     * @var string
     */
    protected $password;

    /**
     * The user email
     * @var string
     */
    protected $email;

    /**
     * The user role
     * @var string
     */
    protected $role;

    /**
     * Flag that indicates if the user is active
     * @var bool
     */
    protected $is_active = false;

    /**
     * The last login date
     * @var string
     */
    protected $date_login;

    /**
     * The registration date
     * @var
     */
    protected $date_registration;

    /**
     * The homepage of the user
     * @var string
     */
    protected $homepage;

    /**
     * The avatar
     * @var string
     */
    protected $avatar;

    /**
     * Required session vars to be a valid session. All user vars are under the "user" key
     * @var array
     */
    protected static $session_required = ["id", "user_name", "real_name", "date_last_login", "role", "permissions"];


    /**
     * The user constructor
     *
     * @param array $data        retrieved from the database
     * @param bool  $from_friend flag that indicates this constructor was called from the friend class
     */
    public function __construct(array $data, $from_friend = false)
    {
        $this->id = (int)$data["id"];
        $this->username = $data["user"];

        if ($from_friend) // we called the constructor from the friend class
        {
            return;
        }

        $this->realname = $data["name"];
        $this->password = $data["pass"];
        $this->email = $data["email"];
        $this->role = $data["role"];
        $this->is_active = (bool)$data["is_active"];
        $this->date_login = $data["last_login"];
        $this->date_registration = $data["reg_date"];
        $this->homepage = $data["homepage"];
        $this->avatar = $data["avatar"];
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
        return $this->username;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @return string
     */
    public function getRealName()
    {
        return $this->realname;
    }

    /**
     * @return string
     */
    public function getHomepage()
    {
        if ($this->homepage && !Util::isURL($this->homepage))
        {
            return "";
        }

        return $this->homepage;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * @return string
     */
    public function getDateLogin()
    {
        return $this->date_login;
    }

    /**
     * @return string
     */
    public function getDateRegistration()
    {
        return $this->date_registration;
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
            throw new UserException(exception_message_db(_(" get the addons for a user")));
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
        $user_xml->writeAttribute('user_name', h($this->getUserName()));
        $user_xml->endElement();

        return $user_xml->asString();
    }

    /**
     * Log in a user
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
            $user = static::validateCredentials($password, $username, static::CREDENTIAL_USERNAME);
        }
        catch(UserException $e)
        {
            static::logout();
            throw new UserException($e->getMessage());
        }

        $id = $user->getId();
        $role = $user->getRole();

        // init session vars
        $session = Session::user();
        $session->init()
            ->set("id", $id)
            ->set("user_name", $user->getUserName())
            ->set("real_name", $user->getRealName())
            ->set("date_last_login", static::updateLoginTime($id))
            ->set("role", $role)
            ->set("permissions", AccessControl::getPermissions($role));
        static::setFriends(Friend::getFriendsOf($id, true));
    }

    /**
     * Logout the user
     */
    public static function logout()
    {
        Session::flush();
        Session::destroy();
        Session::start(); // restart session
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
        if (API_MODE || CRON_MODE)
        {
            return;
        }

        // start session
        Session::start();

        // there is nothing to init
        if (Session::user()->isEmpty())
        {
            return;
        }

        // Check if any session variables are not set
        foreach (static::$session_required as $key)
        {
            // One or more of the session variables was not set - this may be an issue, so force logout
            if (!Session::user()->has($key))
            {
                if (DEBUG_MODE)
                {
                    trigger_error(sprintf("Session key = '%s' was not set", $key));
                }
                static::logout();

                return;
            }
        }

        // Validate session if complete set of variables is available
        $session = Session::user();
        try
        {
            $count = DBConnection::get()->query(
                "SELECT `id`,`user`,`name`,`role`
    	        FROM `" . DB_PREFIX . "users`
                WHERE `user` = :username
                AND `last_login` = :lastlogin
                AND `name` = :realname
                AND `is_active` = 1",
                DBConnection::ROW_COUNT,
                [
                    ':username'  => $session->get('user_name'),
                    ':lastlogin' => $session->get('date_last_login'),
                    ':realname'  => $session->get('real_name')
                ]
            );
        }
        catch(DBException $e)
        {
            throw new UserException(exception_message_db(_("validate your session")));
        }

        if ($count !== 1)
        {
            // todo, set flash message
            static::logout();
        }
    }

    /**
     * Checks if the user is logged ing
     *
     * @return bool
     */
    public static function isLoggedIn()
    {
        return Session::user()->get("id", -1) !== -1;
    }

    /**
     * Get the id of the current logged in user
     *
     * @return int the id of the user or -1 if the user is not logged in
     */
    public static function getLoggedId()
    {
        return Session::user()->get("id", -1);
    }

    /**
     * Get the user name of the current logged in user
     *
     * @return string
     */
    public static function getLoggedUserName()
    {
        return static::isLoggedIn() ? Session::user()->get("user_name") : "";
    }

    /**
     * Get the real name of the current logged in user
     *
     * @return string
     */
    public static function getLoggedRealName()
    {
        return static::isLoggedIn() ? Session::user()->get("real_name") : "";
    }

    /**
     * Get the role of the current user logged in user
     *
     * @return string return the role or 'unregistered' if the user is not logged int
     */
    public static function getLoggedRole()
    {
        return static::isLoggedIn() ? Session::user()->get("role") : "unregistered";
    }

    /**
     * Get all the users from the database in an associative array
     *
     * @param bool $is_active get only the active users
     * @param int  $limit
     * @param int  $current_page
     *
     * @return User[] array of users
     * @throws UserException
     */
    public static function getAll($is_active = true, $limit = -1, $current_page = 1)
    {
        if ($is_active)
        {
            $users = static::getAllFromTable("users", "ORDER BY `user` ASC, `id` ASC", "`is_active` = '1'", $limit, $current_page);
        }
        else
        {
            $users = static::getAllFromTable("users", "ORDER BY `user` ASC, `id` ASC", "", $limit, $current_page);
        }

        $return = [];
        foreach ($users as $user)
        {
            $return[] = new User($user);
        }

        return $return;
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
        $data = static::getFromField("users", "id", $user_id, DBConnection::PARAM_INT, _h("User ID does not exist"));

        return new User($data);
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
        $data = static::getFromField("users", "user", $username, DBConnection::PARAM_STR, _h("Username does not exist"));

        return new User($data);
    }

    /**
     * Filter an array of users of the template menu view
     *
     * @param User[]      $users
     * @param string|null $current_username the currently selected username or null if not user is selected
     *
     * @return array
     */
    public static function filterMenuTemplate($users, $current_username = null)
    {
        $template_users = [];
        foreach ($users as $user)
        {
            // Make sure that the user is active, or the viewer has permission to
            // manage this type of user
            if ($user->isActive() || static::hasPermissionOnRole($user->getRole()))
            {
                // set css class
                $class = $user->isActive() ? "" : "disabled ";
                if ($current_username && $current_username === $user->getUserName())
                {
                    $class .= "active ";
                }

                $template_users[] = [
                    'username' => h($user->getUserName()),
                    'class'    => $class
                ];
            }
        }

        return $template_users;
    }

    /**
     * Search a user
     *
     * @param string $search_string the string can pe space or comma separated to search multiple users
     *
     * @throws UserException
     * @return User[]
     */
    public static function search($search_string)
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
                "SELECT *
                FROM `" . DB_PREFIX . "users`
                WHERE " . implode(" OR ", $query_parts) . " LIMIT 50",
                DBConnection::FETCH_ALL,
                $parameters
            );
        }
        catch(DBException $e)
        {
            throw new UserException(exception_message_db(_('perform your search query')));
        }

        $matched_users = [];
        foreach ($users as $user)
        {
            $matched_users[] = new User($user);
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
            /**@var User $user */
            $partial_output->insert($user->asXML());
        }
        $partial_output->endElement();

        return $partial_output->asString();
    }

    /**
     * Cache the friends into the session
     *
     * @param Friend[] $friends
     */
    public static function setFriends(array $friends)
    {
        Session::user()->set("friends", serialize($friends));
    }

    /**
     * Get the friends from the cache
     *
     * @return Friend[]
     */
    public static function getFriends()
    {
        return unserialize(Session::user()->get("friends"));
    }

    /**
     * Refresh the friends of the logged in user
     */
    public static function refreshFriends()
    {
        static::setFriends(Friend::getFriendsOf(static::getLoggedId(), true));
    }

    /**
     * See if we are friends with a username
     *
     * @param string $username
     *
     * @return null|Friend
     */
    public static function isLoggedFriendsWith($username)
    {
        $friends = static::getFriends();
        foreach ($friends as $friend)
        {
            if ($friend->getUser()->getUserName() === $username)
            {
                return $friend;
            }
        }

        return null;
    }

    /**
     * Get the permission for the session
     *
     * @return array
     */
    public static function getPermissions()
    {
        return static::isLoggedIn() ? Session::user()->get("permissions") : [];
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
     * @param bool $active count only the active users
     *
     * @return int
     * @throws UserException
     */
    public static function count($active = true)
    {
        try
        {
            if ($active)
            {
                $count = DBConnection::get()->count("users", "`is_active` = '1'");
            }
            else
            {
                $count = DBConnection::get()->count("users");
            }
        }
        catch(DBException $e)
        {
            throw new UserException(exception_message_db(_("count the number of users")));
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
        if (!$homepage && !$real_name) // nothing to do
        {
            return;
        }

        if ($real_name)
        {
            static::validateRealName($real_name);
        }
        if ($homepage)
        {
            static::validateHomepage($homepage);
        }

        // throw exception if something is wrong (the user does not exist, or a database error)
        $user = static::getFromID($user_id);

        // verify permissions
        $is_owner = (static::getLoggedId() === $user->getId());
        $can_edit = static::hasPermissionOnRole($user->getRole());
        if (!$is_owner && !$can_edit)
        {
            throw new UserException(_h("You do not have the permission to update the profile"));
        }

        // update session
        Session::user()->set("real_name", $real_name);

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
            throw new UserException(exception_message_db(_('update your profile')));
        }
    }

    /**
     * Set the user role and availability.
     * Only a select few users can call this function.
     *
     * @param int    $user_id
     * @param string $role
     * @param bool   $available
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

        // can not edit your own role
        if ($user->getId() === static::getLoggedId())
        {
            throw new UserException(_h("You can not edit your own role"));
        }

        // update, manually set the active status, the verification will be cleaned by a cron job, see Verification::cron
        try
        {
            DBConnection::get()->update(
                "users",
                "`id` = :id",
                [
                    ":id"        => $user->getId(),
                    ":role"      => $role,
                    ":is_active" => $available
                ],
                [
                    ":id"        => DBConnection::PARAM_INT,
                    ":is_active" => DBConnection::PARAM_BOOL
                ]
            );
        }
        catch(DBException $e)
        {
            throw new UserException(exception_message_db(_('update your role')));
        }
    }

    /**
     * Update the login time of a user
     *
     * @param int $user_id
     *
     * @return string
     * @throws UserException
     * @throws InvalidArgumentException
     */
    public static function updateLoginTime($user_id)
    {
        if (!$user_id)
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
                [':userid' => $user_id]
            );

            $user = DBConnection::get()->query(
                "SELECT `last_login`
                FROM `" . DB_PREFIX . "users`
                WHERE `id` = :userid",
                DBConnection::FETCH_FIRST,
                [':userid' => $user_id]
            );
        }
        catch(DBException $e)
        {
            static::logout();
            throw new UserException(exception_message_db(_('record the last login time')));
        }

        return $user['last_login'];
    }

    /**
     * Change the password of the supplied user.
     * Use with care
     *
     * @param int    $user_id
     * @param string $new_password the new password hash
     *
     * @throws UserException
     */
    public static function changePassword($user_id, $new_password)
    {
        try
        {
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "users`
                SET `pass`   = :pass
    	        WHERE `id` = :userid",
                DBConnection::ROW_COUNT,
                [
                    ':userid' => $user_id,
                    ':pass'   => Util::getPasswordHash($new_password)
                ],
                [":userid" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new UserException(exception_message_db(_('change your password')));
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
        static::validateNewPassword($new_password, $new_password_verify);

        try
        {
            DBConnection::get()->beginTransaction();

            $user = static::validateCredentials($current_password, $user_id, static::CREDENTIAL_ID);

            // only user can change his password
            if ($user->getId() !== $user_id)
            {
                throw new UserException(_h('You do not have the permission to change the password'));
            }

            static::changePassword($user_id, $new_password);

            DBConnection::get()->commit();
        }
        catch(DBException $e)
        {
            DBConnection::get()->rollback();
            throw new UserException(exception_message_db(_('change your password')));
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
                SET `is_active` = '1'
    	        WHERE `id` = :userid",
                DBConnection::ROW_COUNT,
                [":userid" => $userid],
                [":userid" => DBConnection::PARAM_INT]
            );

            if ($count === 0)
            {
                throw new DBException();
            }
        }
        catch(DBException $e)
        {
            throw new UserException(exception_message_db(_('activate your user account')));
        }

        Verification::delete($userid);
        Log::newEvent("User with ID '{$userid}' activated.");
    }

    /**
     * Recover an account
     *
     * @param string $username
     * @param string $email
     *
     * @throws UserException
     */
    public static function recover($username, $email)
    {
        // validate
        static::validateUserName($username);
        static::validateEmail($email);

        $userid = static::validateUsernameEmail($username, $email);
        $verification_code = Verification::generate($userid);

        try
        {
            // Send verification email
            try
            {
                SMail::get()->passwordResetNotification($email, $userid, $username, $verification_code, 'password-reset.php');
            }
            catch(SMailException $e)
            {
                Log::newEvent('Password reset email for "' . $username . '" could not be sent.');
                throw new UserException($e->getMessage() . ' ' . _h('Please contact a website administrator.'));
            }
        }
        catch(DBException $e)
        {
            throw new UserException(exception_message_db(_('validate your username and email address for password reset')));
        }

        Log::newEvent("Password reset request for user '$username'");
    }

    /**
     * Register a new user account
     *
     * @param string $username Must be unique
     * @param string $password
     * @param string $password_conf
     * @param string $email    Must be unique
     * @param string $realname
     * @param string $terms
     *
     * @throws UserException
     */
    public static function register($username, $password, $password_conf, $email, $realname, $terms)
    {
        // validate
        static::validateUserName($username);
        static::validateNewPassword($password, $password_conf);
        static::validateEmail($email);
        static::validateRealName($realname);
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
            throw new UserException(exception_message_db(_('validate your username')));
        }
        if ($result)
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
            throw new UserException(exception_message_db(_('validate your email address')));
        }
        if ($result)
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
                    ":pass"    => Util::getPasswordHash($password),
                    ":name"    => $realname,
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
                SMail::get()->newAccountNotification($email, $userid, $username, $verification_code, 'register.php');
            }
            catch(SMailException $e)
            {
                Log::newEvent("Registration email for user '$username' with id '$userid' failed.");
                throw new UserException($e->getMessage() . ' ' . _h('Please contact a website administrator.'));
            }
        }
        catch(DBException $e)
        {
            throw new UserException(exception_message_db(_('create your account')));
        }

        Log::newEvent("Registration submitted for user '$username' with id '$userid'.");
    }

    /**
     * Check if the username/password matches
     *
     * @param string $password        unhashed password
     * @param mixed  $field_value     the field value
     * @param int    $credential_type denotes the $field_type credential, can be ID or username
     *
     * @throws UserException
     * @throws InvalidArgumentException
     * @return User
     */
    public static function validateCredentials($password, $field_value, $credential_type)
    {
        // validate
        if ($credential_type === static::CREDENTIAL_ID)
        {
            $user = static::validateCheckPassword($password, $field_value, static::PASSWORD_ID);
        }
        elseif ($credential_type === static::CREDENTIAL_USERNAME)
        {
            static::validateUserName($field_value);

            $user = static::validateCheckPassword($password, $field_value, static::PASSWORD_USERNAME);
        }
        else
        {
            throw new InvalidArgumentException("credential type is invalid");
        }

        if (!$user->isActive())
        {
            throw new UserException(_h("Your account is not active"));
        }

        return $user;
    }

    /**
     * Check the password length and check it against the database
     *
     * @param string $password
     * @param string $field_value
     * @param int    $field_type
     *
     * @return User
     * @throws UserException
     * @throws InvalidArgumentException when the $field_type is invalid
     */
    protected static function validateCheckPassword($password, $field_value, $field_type)
    {
        // Check password properties
        static::validatePassword($password);

        try
        {
            if ($field_type === static::PASSWORD_ID) // get by id
            {
                $user = static::getFromID($field_value);
            }
            elseif ($field_type === static::PASSWORD_USERNAME) // get by username
            {
                $user = static::getFromUserName($field_value);
            }
            else
            {
                throw new InvalidArgumentException(_h("Invalid validation field type"));
            }
        }
        catch(UserException $e)
        {
            throw new UserException(_h("Username or password is invalid"));
        }

        // the field value exists, so something about the user is true
        $db_password_hash = $user->getPassword();

        // verify if password is correct
        $salt = Util::getSaltFromPassword($db_password_hash);
        if (Util::getPasswordHash($password, $salt) !== $db_password_hash)
        {
            throw new UserException(_h("Username or password is invalid"));
        }

        return $user;
    }

    /**
     * Checks a username/email address combination and returns the user id if valid
     *
     * @param string $username
     * @param string $email
     *
     * @throws UserException when username/email combination is invalid, or multiple accounts are found
     * @return int the id of the valid user
     */
    public static function validateUsernameEmail($username, $email)
    {
        $users = DBConnection::get()->query(
            "SELECT `id`
	        FROM `" . DB_PREFIX . "users`
	        WHERE `user` = :username
            AND `email` = :email
            AND `is_active` = 1",
            DBConnection::FETCH_ALL,
            [':username' => $username, ':email' => $email]
        );

        if (!$users)
        {
            throw new UserException(_h('Username and email address combination not found.'));
        }
        if (count($users) > 1)
        {
            throw new UserException(_h("Multiple accounts with the same username and email combination."));
        }

        return $users[0]['id'];
    }

    /**
     * Check if the input is a valid alphanumeric username
     *
     * @param string $username Alphanumeric username
     *
     * @throws UserException
     */
    public static function validateUserName($username)
    {
        $username = Util::str_strip_space($username);

        static::validateFieldLength(
            _h("username"),
            $username,
            static::MIN_USERNAME,
            static::MAX_USERNAME,
            false, // whitespace is already gone from our call to str_strip_space
            true // username is alpha numeric, use normal ascii
        );

        // check if alphanumeric
        if (!preg_match('/^[a-z0-9]+$/i', $username))
        {
            throw new UserException(_h('Your username can only contain alphanumeric characters'));
        }
    }

    /**
     * Validate if the password is the correct length
     *
     * @param string $password
     *
     * @throws UserException
     */
    public static function validatePassword($password)
    {
        static::validateFieldLength(_h("password"), $password, static::MIN_PASSWORD, static::MAX_PASSWORD);
    }

    /**
     * Validate if the 2 passwords match and are the correct length
     *
     * @param string $new_password
     * @param string $new_password_verify
     *
     * @throws UserException
     */
    public static function validateNewPassword($new_password, $new_password_verify)
    {
        static::validatePassword($new_password);

        // check if they match
        if ($new_password !== $new_password_verify)
        {
            throw new UserException(_h('Passwords do not match'));
        }
    }

    /**
     * Validate the real name
     *
     * @param string $name
     *
     * @throws UserException
     */
    public static function validateRealName($name)
    {
        static::validateFieldLength(_h("real name"), $name, static::MIN_REALNAME, static::MAX_REALNAME);
    }

    /**
     * Check if the input is a valid email address
     *
     * @param string $email Email address
     *
     * @throws UserException
     */
    public static function validateEmail($email)
    {
        if (!Util::isEmail($email))
        {
            throw new UserException(h(sprintf(_('"%s" is not a valid email address.'), h($email))));
        }

        if (mb_strlen($email) > static::MAX_EMAIL)
        {
            throw new UserException(_h("Email is to long."));
        }
    }

    /**
     * Check if homepage is valid
     *
     * @param string $homepage
     *
     * @throws UserException
     */
    public static function validateHomepage($homepage)
    {
        if (!Util::isURL($homepage))
        {
            throw new UserException(_h("Homepage url is not valid"));
        }

        if (mb_strlen($homepage) > static::MAX_HOMEPAGE)
        {
            throw new UserException(_h("Homepage is to long."));
        }
    }
}

// start session and validate it
// TODO find better position to put this in
User::init();
