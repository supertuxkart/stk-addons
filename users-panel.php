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

// define permissions
$can_elevate_user = (User::hasPermissionOnRole($user_role));
$is_owner = ($user->getId() === User::getLoggedId());
$is_admin = User::isAdmin();

$tpl = StkTemplate::get("user-panel.tpl")
    ->assign("is_owner", $is_owner)
    ->assign("can_elevate_user", $can_elevate_user) // change role and activated status
    ->assign("can_see_email", $is_owner || $is_admin);

$tplData = array(
    "username"          => $user->getUserName(),
    "user_id"           => $user->getId(),
    "date_registration" => $user->getDateRegistration(),
    "real_name"         => $user->getRealName(),
    "email"             => $user->getEmail(),
    "role"              => $user_role,
    "homepage"          => $user->getHomepage(),
    "addon_types"       => array(),
    "settings"          => array(
        "profile"  => array(),
        "elevate"  => array(),
        "password" => array()
    )
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
        "name"     => $type,
        "heading"  => $heading,
        "no_items" => $no_items,
        "items"    => array()
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

// can change the users role and activation field
// only if we are not the active user and have the permission
if ($can_elevate_user && !$is_owner)
{
    // role
    $role = array();

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
    $tplData["settings"]["elevate"] = $role;

    // activated
    if ($user->isActive())
    {
        $tplData["settings"]["elevate"]["activated"] = "checked";
    }
}

$tpl->assign("user", $tplData);
echo $tpl;
