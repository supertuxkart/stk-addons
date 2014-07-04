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
AccessControl::setLevel(AccessControl::PERM_VIEW_BASIC_PAGE);

$user_name = (isset($_GET["user"]) && !empty($_GET["user"])) ? $_GET['user'] : "";

try
{
    $user = User::getFromUserName($user_name);
    $user_role = $user->getRole();
}
catch(UserException $e)
{
    exit("Error " . $e->getMessage());
}

$tpl = new StkTemplate("user-panel.tpl");
$tplData = array(
    "username"          => $user->getUserName(),
    "date_registration" => $user->getDateRegistration(),
    "real_name"         => $user->getRealName(),
    "role"              => $user_role,
    "homepage"          => $user->getHomepage(),
    "addon_types"       => array()
);

// fill users addons
foreach (Addon::getAllowedTypes() as $type)
{
    switch ($type)
    {
        case 'tracks':
            $heading = _h('Tracks');
            $no_items = _h('This user has not uploaded any tracks.');
            break;
        case 'karts':
            $heading = _h('Karts');
            $no_items = _h('This user has not uploaded any karts.');
            break;
        case 'arenas':
            $heading = _h('Arenas');
            $no_items = _h('This user has not uploaded any arenas.');
            break;
        default:
            $heading = "Something went wrong";
            $no_items = "";
    }
    $addon_type = array(
        "name"    => $type,
        "heading" => $heading,
        "no_items" => $no_items,
        "items" => array()
    );

    $addons = $user->getAddonsData($type);
    if (empty($addons)) // no addons for you
    {
        $tplData["addon_types"][] = $addon_type;
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
            $isOwner = ($addon['uploader'] === User::getLoggedId());
            $canEdit = User::hasPermission(AccessControl::PERM_EDIT_ADDONS);

            if (!$isOwner && !$canEdit)
            {
                continue;
            }
            $addon["css_class"] = "unavailable";
        }

        $addonList[] = $addon;
    }

    // add to user template data
    $addon_type["items"] = $addonList;
    $tplData["addon_types"][] = $addon_type;
}

// config form
// Allow current user to change own profile, and administrators to change all profiles
if (User::hasPermissionOnRole($user_role) || $user->getId() === User::getLoggedId())
{
    $tplData["config"] = array();

    // role
    $role = array();
    if (User::getLoggedId() === $user->getId()) // user can not edit his own role
    {
        $role["disabled"] = 'disabled';
    }

    $role["options"] = array();

    // check if current user can edit that role, if not we can not change to that role
    foreach (AccessControl::getRoles() as $db_role)
    {
        // has permission
        $canEdit = User::hasPermissionOnRole($db_role);
        $isOwner = ($user_role === $db_role);

        if ($canEdit || $isOwner)
        {
            $role["options"][$db_role] = $db_role;
            if ($isOwner)
            {
                $role["selected"] = $db_role;
            }
        }
    }
    $tplData["config"]["role"] = $role;

    // activated
    if ($user->isActive())
    {
        $tplData["config"]["activated"] = "checked";
    }

    // password form
    if ($user->getId() === User::getLoggedId())
    {
        $tplData["config"]["password"] = array("min" => 8);
    }
}

$tpl->assign("user", $tplData);
echo $tpl;
