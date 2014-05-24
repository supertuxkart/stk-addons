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

    const PERM_EDIT_BUGS = "editBugs";

    const PERM_EDIT_USERS = "editUsers";

    const PERM_EDIT_MODERATORS = "editModerators";

    const PERM_EDIT_ADMINISTRATORS = "editAdministrators";

    const PERM_EDIT_ROOTS = "editRoots";

    const PERM_EDIT_SETTINGS = "editSettings";

    /**
     * Cache for the roles
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
            static::PERM_EDIT_MODERATORS,
            static::PERM_EDIT_ADMINISTRATORS,
            static::PERM_EDIT_ROOTS,
            static::PERM_EDIT_SETTINGS
        );
    }

    /**
     * @return array
     * @throws UserException
     */
    public static function getRoles()
    {
        // retrieve from cache
        if (!empty(static::$roles))
        {
            echo "From cache";

            return static::$roles;
        }

        // retrieve from db
        try
        {
            $roles = DBConnection::get()->query(
                "SELECT `name` FROM " . DB_PREFIX . "roles",
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
                _('An error occurred while trying to retrieve roles.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        // put onto cache
        foreach ($roles as $role)
        {
            static::$roles[] = $role["name"];
        }


        return static::$roles;
    }

    /**
     * @param string $role
     *
     * @return array
     * @throws UserException
     */
    public static function getPermissions($role = "user")
    {
        try
        {
            $permissions = DBConnection::get()->query(
                sprintf(
                    "SELECT `p`.`permission`
                    FROM `%s` `r` INNER JOIN `%s` `p`
                    ON `r`.`role_id` = `p`.`role_id`
                    WHERE `r`.`name` = :roleName",
                    DB_PREFIX . 'roles',
                    DB_PREFIX . "role_permissions"
                ),
                DBConnection::FETCH_ALL,
                array(":roleName" => $role)
            );
        }
        catch(DBException $e)
        {
            throw new UserException(htmlspecialchars(
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
        $tpl->assign('ad_reason', htmlspecialchars(_('You do not have permission to access this page.')));
        $tpl->assign('ad_action', htmlspecialchars(_('You will be redirected to the home page.')));
        $tpl->assign('ad_redirect_url', File::rewrite('index.php'));
        echo $tpl;

        exit;
    }
}
