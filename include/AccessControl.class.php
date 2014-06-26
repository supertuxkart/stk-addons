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
 */
class AccessControl
{

    // view pages like users panel etc, non public, must be logged in
    const PERM_VIEW_BASIC_PAGE = "viewBasicPage";

    const PERM_ADD_ADDON = "addAddon";

    const PERM_ADD_BUG = "addBug";

    const PERM_EDIT_ADDONS = "editAddons";

    const PERM_EDIT_BUGS = "editBugs"; // edit bugs means close,edit bugs and delete, edit comments

    const PERM_EDIT_USERS = "editUsers";

    const PERM_EDIT_MODERATORS = "editModerators";

    const PERM_EDIT_ADMINISTRATORS = "editAdministrators";

    const PERM_EDIT_ROOTS = "editRoots";

    const PERM_EDIT_SETTINGS = "editSettings";

    /**
     * Cache for the roles, with key name of the role and value the id of the role
     *
     * @var array
     */
    protected static $roles = array();

    /**
     * @return array
     */
    public static function getPermissionsChecked()
    {
        return array(
            static::PERM_VIEW_BASIC_PAGE,
            static::PERM_ADD_ADDON,
            static::PERM_ADD_BUG,
            static::PERM_EDIT_ADDONS,
            static::PERM_EDIT_USERS,
            static::PERM_EDIT_BUGS,
            static::PERM_EDIT_SETTINGS,
            static::PERM_EDIT_MODERATORS,
            static::PERM_EDIT_ADMINISTRATORS,
            static::PERM_EDIT_ROOTS
        );
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
        if (!empty(static::$roles) && !$refresh_cache)
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

        // put onto cache
        foreach ($roles as $role)
        {
            static::$roles[$role["name"]] = $role["id"];
        }


        return array_keys(static::$roles);
    }

    /**
     * Checks if a role exists
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
     * Checks if a permission exists
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
     * @param string $role
     *
     * @return array
     * @throws AccessControlException
     */
    public static function getPermissions($role = "user")
    {
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
                array(":roleName" => $role)
            );
        }
        catch(DBException $e)
        {
            throw new AccessControlException(h(
                _('An error occurred while trying to retrieve permissions') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        $return_permission = array();
        foreach ($permissions as $permission)
        {
            $return_permission[] = $permission["permission"];
        }

        return $return_permission;
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
        if (!User::hasPermissionOnRole("root"))
        {
            throw new AccessControlException(_h("You do not have the permission to change a roles permissions"));
        }
        if (!static::isRole($role))
        {
            throw new AccessControlException(_h("The role is not valid"));
        }

        $role_id = static::$roles[$role];
        $insert_values = array();

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
                array(":role_id" => $role_id),
                array(":role_id" => DBConnection::PARAM_INT)
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
        if (!in_array($permission, static::getPermissionsChecked()))
        {
            throw new AccessControlException(sprintf(_h("Invalid access level: %s"), $permission));
        }

        $allow = false;
        if (User::hasPermission($permission))
        {
            $allow = true;
        }

        if ($allow === false)
        {
            AccessControl::showAccessDeniedPage();
        }
    }

    /**
     * Show a 404 page
     */
    public static function showAccessDeniedPage()
    {
        header('HTTP/1.0 401 Unauthorized');
        $tpl = new StkTemplate('access-denied.tpl');
        $tpl->assign('ad_reason', _h('You do not have permission to access this page.'));
        $tpl->assign('ad_action', _h('You will be redirected to the home page.'));
        $tpl->assign('ad_redirect_url', File::rewrite('index.php'));
        echo $tpl;

        exit;
    }
}
