<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2012-2014 Stephen Just <stephenjust@users.sf.net>
 *           2014      Daniel Butum <danibutum at gmail dot com>
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

require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
AccessControl::setLevel('basicPage');

$user_name = '';
if (isset($_GET["user"]) && !empty($_GET["user"]))
{
    $user_name = $_GET['user'];
}
elseif (isset($_GET["id"]) && !empty($_GET["id"])) // use "id" as name, fallback for javascript
{
    $user_name = $_GET['id'];
}

$user = User::getFromUserName($user_name);
$userData = $user->getUserData();

$user_panel_tpl = new StkTemplate("user-panel.tpl");

// TODO maybe put his onto a list
$user_tpl = array(
    "username"    => array(
        "label" => _h('Username:'),
        "value" => $userData["user"]
    ),
    "reg_date"    => array(
        "label" => _h('Registration Date:'),
        "value" => $userData["reg_date"]
    ),
    "real_name"   => array(
        "label" => _h('Real Name:'),
        "value" => $userData["name"]
    ),
    "role"        => array(
        "label" => _h('Role:'),
        "value" => $userData["role"]
    ),
    "homepage"    => array(
        "label" => _h('Homepage:'),
        "value" => $userData["homepage"]
    ),
    "addon_types" => array()
);

// fill users addons
foreach (Addon::getAllowedTypes() as $type)
{
    switch ($type)
    {
        case 'tracks':
            $heading = _h('User\'s Tracks');
            $no_items = _h('This user has not uploaded any tracks.');
            break;
        case 'karts':
            $heading = _h('User\'s Karts');
            $no_items = _h('This user has not uploaded any karts.');
            break;
        case 'arenas':
            $heading = _h('User\'s Arenas');
            $no_items = _h('This user has not uploaded any arenas.');
            break;
        default:
            $heading = "Something went wrong";
            $no_items = "";
    }
    $addon_type = array(
        "name"    => $type,
        "heading" => $heading
    );

    $addons = $user->getAddonsData($type);
    if (empty($addons)) // no addons for you
    {
        $addon_type["no_items"] = $no_items;
        $user_tpl["addon_types"][] = $addon_type;
        continue;
    }

    $addonList = array();
    foreach ($addons as $addon)
    {
        // Only list the latest revision of the add-on
        if (!($addon["status"] & F_LATEST))
        {
            continue;
        }

        $addon["css_class"] = "";
        if (!($addon["status"] & F_APPROVED)) // not approved
        {
            if ($_SESSION['role']['manageaddons'] == false && $addon['uploader'] !== User::getId())
            {
                continue;
            }
            $addon["css_class"] = "unavailable";
        }

        $addonList[] = $addon;
    }

    $addon_type["list"] = $addonList;
    $user_tpl["addon_types"][] = $addon_type;
}

// config form
// Allow current user to change own profile, and administrators to change all profiles
if ($_SESSION['role']['manage' . $userData['role'] . 's']
    || $userData["id"] === User::getId()
)
{
    $user_tpl["config"] = array(
        "header"          => _h("Configuration"),
        "activated_label" => _h('User Activated:'),
        "submit_value"    => _h('Save Configuration'),
    );

    // role
    $role = array();
    if (User::getId() === $userData["id"])
    {
        $role["disabled"] = 'disabled';
    }
    $role["options"] = array();
    foreach (AccessControl::getPermissionTypes() as $permission)
    {
        // has permission
        if ($_SESSION['role']['manage' . $permission . 's'] || $userData["role"] === $permission)
        {
            $role["options"][$permission] = $permission;
            if ($userData["role"] === $permission)
            {
                $role["selected"] = $permission;
            }
        }
    }
    $user_tpl["config"]["role"] = $role;

    // activated
    if ($userData["active"] == 1)
    {
        $user_tpl["config"]["activated"] = "checked";
    }

    // password form
    if ($userData["id"] === User::getId())
    {
        $user_tpl["config"]["password"] = array(
            "header"              => _h('Change Password'),
            "old_pass_label"      => _h('Old Password:'),
            "new_pass_label"      => _h('New Password:') . '(' . htmlspecialchars(
                    sprintf(_('Must be at least %d characters long.'), 8)
                ) . ')',
            "new_pass_conf_label" => _h('New Password (Confirm):'),
            "submit_value"        => _h('Change Password')
        );
    }
}

$user_panel_tpl->assign("user", $user_tpl);
echo $user_panel_tpl;
