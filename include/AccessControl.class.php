<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
 *           2012 Stephen Just <stephenjust@users.sf.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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
 * Class AccessControl
 * That handles the roles system and permission access
 */
class AccessControl
{
    /**
     * Can view the most basic pages
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
     * Cache for the roles, with key name of the role and value the id of the role
     *
     * @var array
     */
    protected static $roles = [];

    /**
     * Cache for the role permissions, with the key the role name and the value an array of permissions
     *
     * @var array
     */
    protected static $permissions = [];

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
            static::PERM_EDIT_ADMINS
        ];
    }

    /**
     * Add a new role to the database.
     * Only high privilege users can call this method
     *
     * @param string $role
     *
     * @throws AccessControlException
     */
    public static function addRole($role)
    {
        // validate
        if (!User::hasPermission(static::PERM_EDIT_PERMISSIONS))
        {
            throw new AccessControlException("You do not have the permission to add a role");
        }
        if (static::isRole($role))
        {
            throw new AccessControlException("The role already exists");
        }

        try
        {
            DBConnection::get()->insert("roles", [":name" => $role]);
        }
        catch(DBException $e)
        {
            throw new AccessControlException('An error occurred while trying add a role');
        }
    }

    /**
     * Delete a role from the database
     * Only high privilege users can call this method
     *
     * @param string $role
     *
     * @throws AccessControlException
     */
    public static function deleteRole($role)
    {
        // validate
        if (!User::hasPermission(static::PERM_EDIT_PERMISSIONS))
        {
            throw new AccessControlException("You do not have the permission to delete a role");
        }
        if (!static::isRole($role))
        {
            throw new AccessControlException("The role does not exist");
        }

        // find out if there are any users with that role, TODO check production server because of old roles
        try
        {
            $count = DBConnection::get()->count("users", "`role` = :role", [":role" => $role]);
        }
        catch(DBException $e)
        {
            throw new AccessControlException("An error occurred while trying to count users");
        }

        // there are users with that role, not good
        if ($count)
        {
            throw new AccessControlException("There are already users with that role. Can not delete. Do it manually");
        }

        try
        {
            DBConnection::get()->delete("roles", "`name` = :name", [":name" => $role]);
        }
        catch(DBException $e)
        {
            throw new AccessControlException("An error occurred while trying to delete a role");
        }
    }

    /**
     * Retrieve all the roles available from the database
     *
     * @param bool $refresh_cache flag set to refer
     *
     * @return array
     * @throws AccessControlException
     */
    public static function getRoles($refresh_cache = false)
    {
        // retrieve from cache
        if (static::$roles && !$refresh_cache)
        {
            return array_keys(static::$roles);
        }

        // retrieve from db
        try
        {
            $roles = DBConnection::get()->query(
                "SELECT * FROM " . DB_PREFIX . "roles",
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            throw new AccessControlException(h(
                _('An error occurred while trying to retrieve roles.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        // put into the cache
        foreach ($roles as $role)
        {
            // role => id
            static::$roles[$role["name"]] = $role["id"];
        }

        return array_keys(static::$roles);
    }

    /**
     * Checks if a role is valid
     *
     * @param,string $role
     *
     * @return bool
     */
    public static function isRole($role)
    {
        return in_array($role, static::getRoles());
    }

    /**
     * Checks if a permission is valid
     *
     * @param,string $permission
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
     * @param string $role
     *
     * @return array
     * @throws AccessControlException
     */
    public static function getPermissions($role = "user")
    {
        // retrieve from cache
        if (isset(static::$permissions[$role]))
        {
            return static::$permissions[$role];
        }

        try
        {
            $permissions = DBConnection::get()->query(
                sprintf(
                    "SELECT `p`.`permission`
                    FROM `%s` `r` INNER JOIN `%s` `p`
                    ON `r`.`id` = `p`.`role_id`
                    WHERE `r`.`name` = :roleName",
                    DB_PREFIX . "roles",
                    DB_PREFIX . "role_permissions"
                ),
                DBConnection::FETCH_ALL,
                [":roleName" => $role]
            );
        }
        catch(DBException $e)
        {
            throw new AccessControlException(h(
                _('An error occurred while trying to retrieve permissions') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        // put into the cache
        static::$permissions[$role] = [];
        foreach ($permissions as $permission)
        {
            static::$permissions[$role][] = $permission["permission"];
        }

        return static::$permissions[$role];
    }

    /**
     * Set the permission of a role in the database
     * Use with precaution. The validation is performed inside this method
     * Only users with high privilege can call this method
     *
     * @param string $role
     * @param array  $permissions
     *
     * @throws AccessControlException
     */
    public static function setPermissions($role, array $permissions)
    {
        // validate
        if (!User::hasPermission(static::PERM_EDIT_PERMISSIONS))
        {
            throw new AccessControlException(_h("You do not have the permission to change a roles permissions"));
        }
        if (!static::isRole($role))
        {
            throw new AccessControlException(_h("The role is not valid"));
        }

        $role_id = static::$roles[$role]; // getRoles is called by isRole
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
        catch(DBException $e)
        {
            throw new AccessControlException(h(
                _('An error occurred while trying to clean the role permissions') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        // insert new permissions
        try
        {
            DBConnection::get()->query(
                sprintf(
                    "INSERT INTO %s (`role_id`, `permission`) VALUES %s",
                    DB_PREFIX . "role_permissions",
                    implode(", ", $insert_values)
                ),
                DBConnection::NOTHING
            );
        }
        catch(DBException $e)
        {
            throw new AccessControlException(h(
                _('An error occurred while trying to insert new permissions') . ' ' .
                _('Please contact a website administrator.')
            ));
        }
    }

    /**
     * Set access level restriction on a page, redirect if no permission
     *
     * @param string $permission
     *
     * @return bool
     * @throws AccessControlException
     */
    public static function setLevel($permission)
    {
        assert(static::isPermission($permission) === true);

        if (!User::hasPermission($permission))
        {
            AccessControl::showAccessDeniedPage();
        }
    }

    /**
     * Show a 401 page
     */
    public static function showAccessDeniedPage()
    {
        Util::redirectTo(SITE_ROOT . "error.php?e=401");
    }
}
