<?php
/**
 * copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2012      Stephen Just <stephenjust@users.sf.net>
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
 * Class AccessControl
 * That handles the roles system and permission access
 */
class AccessControl
{
    /**
     * Can view the most basic pages, if this is set, then the user is logged in
     * @var string
     */
    const PERM_VIEW_BASIC_PAGE = "view_basic_page";

    /**
     * Can add an addon
     * @var string
     */
    const PERM_ADD_ADDON = "add_addon";

    /**
     * Can add a bug
     * @var string
     */
    const PERM_ADD_BUG = "add_bug";

    /**
     * Can add a comment to a bug
     * @var string
     */
    const PERM_ADD_BUG_COMMENT = "add_bug_comment";

    /**
     * Can update/delete/insert addons
     * @var string
     */
    const PERM_EDIT_ADDONS = "edit_addons";

    /**
     * Edit bugs means close, edit bugs and delete, edit comments
     * @var string
     */
    const PERM_EDIT_BUGS = "edit_bugs";

    /**
     * Edit normal users (non-admins), means delete/change/insert
     * @var string
     */
    const PERM_EDIT_USERS = "edit_users";

    /**
     * The user is an admin can edit other admins
     * @var string
     */
    const PERM_EDIT_ADMINS = "edit_admins";

    /**
     * Can edit permissions
     * @var string
     */
    const PERM_EDIT_PERMISSIONS = "edit_permissions";

    /**
     * @var string
     */
    const PERM_EDIT_SETTINGS = "edit_settings";

    /**
     * The user with this permission will be able to display his server with official tag
     * @var string
     */
    const PERM_OFFICIAL_SERVERS = "official_servers";

    /**
     * @var string
     */
    const PERM_SUMBIT_RANKINGS = "submit_rankings";

    /**
     * Cache for the roles, with key name of the role and value the id of the role
     *
     * @var array
     */
    private static $roles = [];

    /**
     * Cache for the role permissions, with the key the role name and the value an array of permissions
     *
     * @var array
     */
    private static $permissions = [];

    /**
     * @return array
     */
    public static function getPermissionsChecked()
    {
        return [
            static::PERM_VIEW_BASIC_PAGE,
            static::PERM_ADD_ADDON,
            static::PERM_ADD_BUG,
            static::PERM_ADD_BUG_COMMENT,
            static::PERM_EDIT_ADDONS,
            static::PERM_EDIT_BUGS,
            static::PERM_EDIT_USERS,
            static::PERM_EDIT_SETTINGS,
            static::PERM_EDIT_PERMISSIONS,
            static::PERM_EDIT_ADMINS,
            static::PERM_OFFICIAL_SERVERS,
            static::PERM_SUMBIT_RANKINGS
        ];
    }

    /**
     * Add a new role to the database.
     * Only high privilege users can call this method
     *
     * @param string $role_name
     *
     * @throws AccessControlException
     */
    public static function addRole($role_name)
    {
        // validate
        if (!$role_name)
        {
            throw new AccessControlException("The role is empty");
        }
        if (static::isRole($role_name))
        {
            throw new AccessControlException("The role already exists");
        }

        try
        {
            DBConnection::get()->insert("roles", [":name" => $role_name]);
        }
        catch (DBException $e)
        {
            throw new AccessControlException(exception_message_db('add a role'));
        }
    }

    /**
     * Rename a role
     *
     * @param string $old_role
     * @param string $new_role
     *
     * @throws AccessControlException
     */
    public static function renameRole($old_role, $new_role)
    {
        // validate
        if (!$new_role)
        {
            throw new AccessControlException("The new role is empty");
        }
        if (!static::isRole($old_role))
        {
            throw new AccessControlException("The old role does not exist");
        }
        if (static::isRole($new_role))
        {
            throw new AccessControlException("The rename role already exists");
        }

        try
        {
            DBConnection::get()->update(
                "roles",
                "`name` = :where",
                [
                    ":where" => $old_role,
                    ":name"  => $new_role
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AccessControlException(exception_message_db('add a role'));
        }
    }

    /**
     * Delete a role from the database
     * Only high privilege users can call this method
     *
     * @param string $role_name
     *
     * @throws AccessControlException
     */
    public static function deleteRole($role_name)
    {
        // validate
        if (!static::isRole($role_name))
        {
            throw new AccessControlException("The role does not exist");
        }

        $role_id = static::getRoles()[$role_name];
        // find out if there are any users with that role,
        try
        {
            $count = DBConnection::get()->count("users", "`role_id` = :role_id", [":role_id" => $role_id]);
        }
        catch (DBException $e)
        {
            throw new AccessControlException(exception_message_db("count users"));
        }

        // there are users with that role, not good
        if ($count)
        {
            throw new AccessControlException("There are already users with that role. Can not delete. Do it manually");
        }

        try
        {
            DBConnection::get()->delete("roles", "`id` = :id", [":id" => $role_id]);
        }
        catch (DBException $e)
        {
            throw new AccessControlException(exception_message_db("delete a role"));
        }
    }

    /**
     * Retrieve all the role names available from the database
     *
     * @param bool $refresh_cache flag set to refer
     *
     * @return array
     * @throws AccessControlException
     */
    public static function getRoleNames($refresh_cache = false)
    {
        return array_keys(static::getRoles($refresh_cache));
    }

    /**
     * Retrieve all the role table data from the database
     *
     * @param bool $refresh_cache flag set to refer
     *
     * @return array an associative array wih value being the 'id' of the role
     *               and the key being the 'name' of the role
     * @throws AccessControlException
     */
    public static function getRoles($refresh_cache = false)
    {
        if (static::$roles && !$refresh_cache)
        {
            return static::$roles;
        }

        // retrieve from db
        try
        {
            $roles = DBConnection::get()->query(
                "SELECT * FROM `{DB_VERSION}_roles`",
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
        {
            throw new AccessControlException(exception_message_db(_('retrieve roles')));
        }

        // put into the cache
        foreach ($roles as $role)
        {
            // role name => role_id
            static::$roles[$role["name"]] = $role["id"];
        }

        return static::$roles;
    }

    /**
     * Checks if a role is valid
     *
     * @param string $role_name
     *
     * @return bool
     */
    public static function isRole($role_name)
    {
        try
        {
            return in_array($role_name, static::getRoleNames());
        }
        catch (AccessControlException $e)
        {
            Debug::addException($e);
            return false;
        }
    }

    /**
     * Checks if a permission is valid
     *
     * @param string $permission
     *
     * @return bool
     */
    public static function isPermission($permission)
    {
        return in_array($permission, static::getPermissionsChecked());
    }

    /**
     * Get all the permission of a role
     *
     * @param string $role_name
     *
     * @return array
     * @throws AccessControlException
     */
    public static function getPermissions($role_name)
    {
        // retrieve from cache
        if (isset(static::$permissions[$role_name]))
        {
            return static::$permissions[$role_name];
        }

        try
        {
            $roles_permissions = DBConnection::get()->query(
                "SELECT R.name, P.permission
                FROM `{DB_VERSION}_roles` R
                INNER JOIN `{DB_VERSION}_role_permissions` P
                    ON R.`id` = P.`role_id`",
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
        {
            throw new AccessControlException(exception_message_db(_('retrieve permissions')));
        }

        // fill cache
        foreach ($roles_permissions as $role_permission)
        {
            $name = $role_permission["name"];
            $permission = $role_permission["permission"];

            if (!isset(static::$permissions[$name]))
            {
                static::$permissions[$name] = [];
            }
            static::$permissions[$name][] = $permission;
        }

        return isset(static::$permissions[$role_name]) ? static::$permissions[$role_name] : [];
    }

    /**
     * Set the permission of a role in the database
     * Use with precaution. The validation is performed inside this method
     * Only users with high privilege can call this method
     *
     * @param string $role_name
     * @param array  $permissions
     *
     * @throws AccessControlException
     */
    public static function setPermissions($role_name, array $permissions)
    {
        // validate
        if (!static::isRole($role_name))
        {
            throw new AccessControlException(_h("The role is not valid"));
        }

        $role_id = static::getRoles()[$role_name];
        $insert_values = [];

        foreach ($permissions as $permission) // validate permission and populate insert values
        {
            if (!static::isPermission($permission))
            {
                throw new AccessControlException(sprintf("%s is not a valid permission", h($permission)));
            }

            // VALUES (1, 'viewBasicPage'), (1, 'addAddon')
            $insert_values[] = sprintf("(%s, '%s')", (string)$role_id, $permission);
        }

        // clean current permission of the user
        try
        {
            DBConnection::get()->delete(
                "role_permissions",
                "`role_id` = :role_id",
                [":role_id" => $role_id],
                [":role_id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new AccessControlException(exception_message_db(_('clean the role permissions')));
        }

        // insert new permissions
        try
        {
            DBConnection::get()->query(
                sprintf(
                    "INSERT INTO {DB_VERSION}_role_permissions (`role_id`, `permission`) VALUES %s",
                    implode(", ", $insert_values)
                ),
                DBConnection::NOTHING
            );
        }
        catch (DBException $e)
        {
            throw new AccessControlException(exception_message_db(_('insert new permissions')));
        }
    }

    /**
     * Set access level restriction on a page, redirect if no permission
     *
     * @param string $permission
     */
    public static function setLevel($permission)
    {
        Assert::true(static::isPermission($permission));

        if (!User::hasPermission($permission))
        {
            Util::redirectError(401);
        }
    }
}
