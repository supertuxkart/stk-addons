<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *           2013      Glenn De Jonghe
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stk-addons.
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
 * Class User
 */
class User extends Base implements IAsXML
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
     * The id of the user
     * @var int
     */
    private $id = -1;

    /**
     * The username
     * @var string
     */
    private $username;

    /**
     * The real name
     * @var string
     */
    private $realname;

    /**
     * The password hashed
     * @var string
     */
    private $password;

    /**
     * The user email
     * @var string
     */
    private $email;

    /**
     * The user role
     * @var string
     */
    private $role;

    /**
     * Flag that indicates if the user is active
     * @var bool
     */
    private $is_active = false;

    /**
     * The last login date
     * @var string
     */
    private $date_login;

    /**
     * The registration date
     * @var string
     */
    private $date_register;

    /**
     * The homepage of the user
     * @var string
     */
    private $homepage;

    /**
     * Required session vars to be a valid session. All user vars are under the "user" key
     * @var array
     */
    private static $session_required = ["id", "username", "realname", "date_login", "role", "permissions"];

    /**
     * The user constructor
     *
     * @param array $data        retrieved from the database
     * @param bool  $from_friend flag that indicates this constructor was called from the friend class
     */
    public function __construct(array $data, $from_friend = false)
    {
        $this->id = (int)$data["id"];
        $this->username = $data["username"];

        if ($from_friend) // we called the constructor from the friend class
        {
            return;
        }

        $this->realname = $data["realname"];
        $this->password = $data["password"];
        $this->email = $data["email"];
        $this->role = $data["role_name"];
        $this->is_active = (bool)$data["is_active"];
        $this->date_login = $data["date_login"];
        $this->date_register = $data["date_register"];
        $this->homepage = $data["homepage"];
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
    public function getDateRegister()
    {
        return $this->date_register;
    }

    /**
     * Get the users addons data
     *
     * @param int $type
     *
     * @return array|int|null
     * @throws UserException
     */
    public function getAddonsData($type)
    {
        if (!Addon::isAllowedType($type))
        {
            throw new UserException(
                sprintf("Addon type=%s does not exist", Addon::typeToString($type)),
                ErrorType::USER_ADDON_TYPE_NOT_EXIST
            );
        }

        try
        {
            $addons = DBConnection::get()->query(
                'SELECT `a`.*, `r`.`status`
                FROM `' . DB_PREFIX . 'addons` `a`
                LEFT JOIN `' . DB_PREFIX . 'addon_revisions` `r`
                    ON `a`.`id` = `r`.`addon_id`
                WHERE `a`.`uploader` = :uploader
                    AND `a`.`type` = :addon_type',
                DBConnection::FETCH_ALL,
                [
                    ":uploader"   => $this->id,
                    ":addon_type" => $type
                ],
                [":uploader" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new UserException(exception_message_db(_("get the addons for a user")), ErrorType::USER_DB_EXCEPTION);
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
     * @return UserException
     */
    public static function getException()
    {
        return new UserException();
    }

    /**
     * Get common SQL select all statement
     * @return string
     */
    private static function getSQLAll()
    {
        return "SELECT U.*, R.name AS role_name
                FROM " . DB_PREFIX . "users U
                INNER JOIN " . DB_PREFIX . "roles R
                    ON U.role_id = R.id ";
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
            $user = static::validateCredentials($password, $username, static::CREDENTIAL_USERNAME, true);
        }
        catch (UserException $e)
        {
            static::logout();
            throw $e;
        }

        $id = $user->getId();
        $role = $user->getRole();

        // init session vars
        Session::regenerateID();
        $session = Session::user();
        $session->init()
            ->set("id", $id)
            ->set("username", $user->getUserName())
            ->set("realname", $user->getRealName())
            ->set("date_login", static::updateLoginTime($id))
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
        Session::regenerateID();
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
                Debug::addMessage(sprintf("Session key = '%s' was not set", $key));
                static::logout();

                return;
            }
        }

        // Validate session if complete set of variables is available
        $session = Session::user();
        try
        {
            $count = DBConnection::get()->query(
                "SELECT *
    	        FROM `" . DB_PREFIX . "users`
                WHERE `username` = :username
                AND `date_login` = :date_login
                AND `realname` = :realname
                AND `is_active` = 1",
                DBConnection::ROW_COUNT,
                [
                    ':username'   => $session->get('username'),
                    ':date_login' => $session->get('date_login'),
                    ':realname'   => $session->get('realname')
                ]
            );
        }
        catch (DBException $e)
        {
            throw new UserException(exception_message_db(_("validate your session")), ErrorType::USER_VALID_SESSION);
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
        return static::isLoggedIn() ? Session::user()->get("username") : "";
    }

    /**
     * Get the real name of the current logged in user
     *
     * @return string
     */
    public static function getLoggedRealName()
    {
        return static::isLoggedIn() ? Session::user()->get("realname") : "";
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
        $order_by = "ORDER BY U.`username` ASC, U.`id` ASC";
        if ($is_active)
        {
            $users = static::getAllFromTable(
                static::getSQLAll() . "WHERE U.`is_active` = '1' " . $order_by,
                $limit,
                $current_page
            );
        }
        else
        {
            $users = static::getAllFromTable(static::getSQLAll() . $order_by, $limit, $current_page);
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
        $data = static::getFromField(
            static::getSQLAll(),
            "U.id",
            $user_id,
            DBConnection::PARAM_INT,
            _h("User ID does not exist"),
            ":id"
        );

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
        $data = static::getFromField(
            static::getSQLAll(),
            "U.username",
            $username,
            DBConnection::PARAM_STR,
            _h("Username does not exist"),
            ":username"
        );

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
                $parameter = ":user_id" . $index;
                $index++;
                $query_parts[] = "`username` RLIKE " . $parameter;
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
                static::getSQLAll() .
                "WHERE " . implode(" OR ", $query_parts) . " LIMIT 50",
                DBConnection::FETCH_ALL,
                $parameters
            );
        }
        catch (DBException $e)
        {
            throw new UserException(exception_message_db(_('perform your search query')), ErrorType::USER_SEARCH);
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
        catch (DBException $e)
        {
            throw new UserException(exception_message_db(_("count the number of users")), ErrorType::USER_COUNT);
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
            throw new UserException(
                _h("You do not have the permission to update the profile"),
                ErrorType::USER_INVALID_PERMISSION
            );
        }

        // update session
        Session::user()->set("realname", $real_name);

        try
        {
            DBConnection::get()->update(
                "users",
                "`id` = :id",
                [
                    ":id"       => $user->getId(),
                    ":homepage" => $homepage,
                    ":realname" => $real_name
                ],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new UserException(exception_message_db(_('update your profile')), ErrorType::USER_UPDATE_PROFILE);
        }
    }

    /**
     * Set the user role and availability.
     * Only a select few users can call this function.
     *
     * @param int    $user_id
     * @param string $role_name
     * @param bool   $available
     *
     * @throws UserException
     */
    public static function updateRole($user_id, $role_name, $available)
    {
        // validate
        if (!AccessControl::isRole($role_name))
        {
            throw new UserException(_h("The specified role is not valid"), ErrorType::USER_INVALID_ROLE);
        }

        $canEdit = static::hasPermissionOnRole($role_name);
        if (!$canEdit)
        {
            throw new UserException(
                _h("You do not have the permission to edit the role"),
                ErrorType::USER_INVALID_PERMISSION
            );
        }

        // also does validation
        $user = static::getFromID($user_id);

        // can not edit your own role
        if ($user->getId() === static::getLoggedId())
        {
            throw new UserException(_h("You can not edit your own role"), ErrorType::USER_INVALID_PERMISSION);
        }

        // update, manually set the active status, the verification will be cleaned by a cron job, see Verification::cron
        try
        {
            DBConnection::get()->update(
                "users",
                "`id` = :id",
                [
                    ":id"        => $user->getId(),
                    ":role_id"   => AccessControl::getRoles()[$role_name],
                    ":is_active" => $available
                ],
                [
                    ":id"        => DBConnection::PARAM_INT,
                    ":role_id"   => DBConnection::PARAM_INT,
                    ":is_active" => DBConnection::PARAM_BOOL
                ]
            );
        }
        catch (DBException $e)
        {
            throw new UserException(exception_message_db(_('update your role')), ErrorType::USER_UPDATE_ROLE);
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
                SET `date_login` = NOW()
                WHERE `id` = :user_id",
                DBConnection::NOTHING,
                [':user_id' => $user_id]
            );

            $user = DBConnection::get()->query(
                "SELECT `date_login`
                FROM `" . DB_PREFIX . "users`
                WHERE `id` = :user_id",
                DBConnection::FETCH_FIRST,
                [':user_id' => $user_id]
            );
        }
        catch (DBException $e)
        {
            static::logout();
            throw new UserException(
                exception_message_db(_('record the last login time')),
                ErrorType::USER_UPDATE_LAST_LOGIN
            );
        }

        return $user['date_login'];
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
                SET `password`   = :password
    	        WHERE `id` = :user_id",
                DBConnection::ROW_COUNT,
                [
                    ':user_id'  => $user_id,
                    ':password' => Util::getPasswordHash($new_password)
                ],
                [":user_id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new UserException(exception_message_db(_('change your password')), ErrorType::USER_CHANGE_PASSWORD);
        }

        if (!$count)
        {
            throw new UserException("Change password no rows affected", ErrorType::USER_CHANGE_PASSWORD);
        }

        Session::regenerateID();
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
                throw new UserException(
                    _h('You do not have the permission to change the password'),
                    ErrorType::USER_INVALID_PERMISSION
                );
            }

            static::changePassword($user_id, $new_password);

            DBConnection::get()->commit();
        }
        catch (DBException $e)
        {
            DBConnection::get()->rollback();
            throw new UserException(exception_message_db(_('change your password')), ErrorType::USER_CHANGE_PASSWORD);
        }

        Session::regenerateID();
    }

    /**
     * Activate a new user
     *
     * @param int    $user_id
     * @param string $ver_code
     *
     * @throws UserException when activation failed
     */
    public static function activate($user_id, $ver_code)
    {
        Verification::verify($user_id, $ver_code);
        try
        {
            $count = DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "users`
                SET `is_active` = '1'
    	        WHERE `id` = :user_id",
                DBConnection::ROW_COUNT,
                [":user_id" => $user_id],
                [":user_id" => DBConnection::PARAM_INT]
            );

            if ($count === 0)
            {
                throw new DBException();
            }
        }
        catch (DBException $e)
        {
            throw new UserException(
                exception_message_db(_('activate your user account')),
                ErrorType::USER_ACTIVATE_ACCOUNT
            );
        }

        Verification::delete($user_id);
        StkLog::newEvent("User with ID '{$user_id}' activated.");
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

        $user_id = static::validateUsernameEmail($username, $email);
        $verification_code = Verification::generate($user_id);

        try
        {
            // Send verification email
            try
            {
                StkMail::get()->passwordResetNotification(
                    $email,
                    $user_id,
                    $username,
                    $verification_code,
                    'password-reset.php'
                );
            }
            catch (StkMailException $e)
            {
                StkLog::newEvent('Password reset email for "' . $username . '" could not be sent.', LogLevel::ERROR);
                throw new UserException(
                    $e->getMessage() . ' ' . _h('Please contact a website administrator.'),
                    ErrorType::USER_SENDING_RECOVER_EMAIL
                );
            }
        }
        catch (DBException $e)
        {
            throw new UserException(
                exception_message_db(_('validate your username and email address for password reset')),
                ErrorType::USER_SENDING_RECOVER_EMAIL
            );
        }

        StkLog::newEvent("Password reset request for user '$username'");
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

        $db = DBConnection::get();
        $db->beginTransaction();
        // Make sure requested username is not taken
        try
        {
            $result = $db->query(
                "SELECT `username`
    	        FROM `" . DB_PREFIX . "users`
    	        WHERE `username` LIKE :username",
                DBConnection::FETCH_FIRST,
                [':username' => $username]
            );
        }
        catch (DBException $e)
        {
            throw new UserException(
                exception_message_db(_('validate your username')),
                ErrorType::VALIDATE_USERNAME_NOT_EXISTS
            );
        }
        if ($result)
        {
            throw new UserException(_h('This username is already taken.'), ErrorType::VALIDATE_USERNAME_TAKEN);
        }

        // Make sure the email address is unique
        try
        {
            $result = $db->query(
                "SELECT `email`
    	        FROM `" . DB_PREFIX . "users`
    	        WHERE `email` LIKE :email",
                DBConnection::FETCH_FIRST,
                [':email' => $email]
            );
        }
        catch (DBException $e)
        {
            throw new UserException(
                exception_message_db(_('validate your email address')),
                ErrorType::VALIDATE_EMAIL_NOT_EXISTS
            );
        }
        if ($result)
        {
            throw new UserException(_h('This email address is already taken.'), ErrorType::VALIDATE_EMAIL_TAKEN);
        }

        // No exception occurred - continue with registration
        try
        {
            $count = $db->insert(
                "users",
                [
                    ":username"     => $username,
                    ":password"     => Util::getPasswordHash($password),
                    ":realname"     => $realname,
                    ":email"        => $email,
                    "date_register" => "CURRENT_DATE()"
                ]
            );

            if ($count !== 1)
            {
                throw new DBException("Multiple rows affected(or none). Not good");
            }

            $user_id = $db->lastInsertId();
            $db->commit();
            $verification_code = Verification::generate($user_id);

            // Send verification email
            try
            {
                StkMail::get()->newAccountNotification($email, $user_id, $username, $verification_code, 'register.php');
            }
            catch (StkMailException $e)
            {
                StkLog::newEvent("Registration email for user '$username' with id '$user_id' failed.");
                throw new UserException(
                    $e->getMessage() . ' ' . _h('Please contact a website administrator.'),
                    ErrorType::USER_SENDING_CREATE_EMAIL
                );
            }
        }
        catch (DBException $e)
        {
            throw new UserException(exception_message_db(_('create your account')), ErrorType::USER_CREATE_ACCOUNT);
        }

        StkLog::newEvent("Registration submitted for user '$username' with id '$user_id'.");
    }

    /**
     * Check if the username/password matches
     *
     * @param string $password             unhashed password
     * @param mixed  $field_value          the field value
     * @param int    $credential_type      denotes the $field_type credential, can be ID or username
     * @param bool   $allow_username_space legacy parameter, old usernames allowed space in them
     *
     * @throws UserException
     * @throws InvalidArgumentException
     * @return User
     */
    public static function validateCredentials($password, $field_value, $credential_type, $allow_username_space = false)
    {
        // validate
        if ($credential_type === static::CREDENTIAL_ID)
        {
            $user = static::validateCheckPassword($password, $field_value, static::PASSWORD_ID);
        }
        elseif ($credential_type === static::CREDENTIAL_USERNAME)
        {
            static::validateUserName($field_value, $allow_username_space);

            $user = static::validateCheckPassword($password, $field_value, static::PASSWORD_USERNAME);
        }
        else
        {
            throw new InvalidArgumentException("credential type is invalid");
        }

        if (!$user->isActive())
        {
            throw new UserException(_h("Your account is not active"), ErrorType::USER_INACTIVE_ACCOUNT);
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
        catch (UserException $e)
        {
            throw new UserException(_h("Username or password is invalid"), ErrorType::VALIDATE_USERNAME_OR_PASSWORD);
        }

        // the field value exists, so something about the user is true
        $db_password_hash = $user->getPassword();

        // verify if password is correct
        $salt = Util::getSaltFromPassword($db_password_hash);
        if (Util::getPasswordHash($password, $salt) !== $db_password_hash)
        {
            throw new UserException(_h("Username or password is invalid"), ErrorType::VALIDATE_USERNAME_OR_PASSWORD);
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
	        WHERE `username` = :username
            AND `email` = :email
            AND `is_active` = 1",
            DBConnection::FETCH_ALL,
            [
                ':username' => $username,
                ':email'    => $email
            ]
        );

        if (!$users)
        {
            throw new UserException(
                _h('Username and email address combination not found.'),
                ErrorType::VALIDATE_USERNAME_AND_EMAIL
            );
        }
        if (count($users) > 1)
        {
            throw new UserException(
                _h("Multiple accounts with the same username and email combination."),
                ErrorType::VALIDATE_MULTIPLE_USERNAME_AND_EMAIL
            );
        }

        return $users[0]['id'];
    }

    /**
     * Check if the input is a valid alphanumeric username
     *
     * @param string $username    Alphanumeric username
     * @param bool   $allow_space legacy parameter, old usernames allowed space in them
     *
     * @throws UserException
     */
    public static function validateUserName($username, $allow_space = false)
    {
        static::validateFieldLength(
            _h("username"),
            $username,
            static::MIN_USERNAME,
            static::MAX_USERNAME,
            true, // allow space here, fail below, if that is the case
            false // username is alpha numeric, use normal ascii
        );

        // check if alphanumeric
        $space = $allow_space ? " " : "";
        if (!preg_match("/^[a-z0-9\.\-\_$space]+$/i", $username))
        {
            throw new UserException(
                _h('Your username can only contain alphanumeric characters, periods, dashes and underscores'),
                ErrorType::VALIDATE_USERNAME
            );
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
            throw new UserException(_h('Passwords do not match'), ErrorType::VALIDATE_PASSWORDS_MATCH);
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
            throw new UserException(
                h(sprintf(_('"%s" is not a valid email address.'), $email)),
                ErrorType::VALIDATE_EMAIL
            );
        }

        if (mb_strlen($email) > static::MAX_EMAIL)
        {
            throw new UserException(_h("Email is to long."), ErrorType::VALIDATE_EMAIL_LONG);
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
            throw new UserException(_h("Homepage url is not valid"), ErrorType::VALIDATE_HOMEPAGE_URL);
        }

        if (mb_strlen($homepage) > static::MAX_HOMEPAGE)
        {
            throw new UserException(_h("Homepage is to long."), ErrorType::VALIDATE_HOMEPAGE_LONG);
        }
    }
}

// start session and validate it
// TODO find better position to put this in
if (!TEST_MODE) User::init();
