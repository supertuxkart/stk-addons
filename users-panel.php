<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2012-2014 Stephen Just <stephenjust@users.sf.net>
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
require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
AccessControl::setLevel(AccessControl::PERM_VIEW_BASIC_PAGE);

$user_name = !empty($_GET["user"]) ? $_GET['user'] : "";

try
{
    $user = User::getFromUserName($user_name);
    $user_role = $user->getRole();
}
catch(UserException $e)
{
    exit("Error: " . $e->getMessage());
}

// define permissions
$can_elevate_user = User::hasPermissionOnRole($user_role);
$is_owner = ($user->getId() === User::getLoggedId());
$is_admin = User::isAdmin();

// user is not active yet, only allow high privilege users to see it
if (!$user->isActive() && !$can_elevate_user)
{
    Util::redirectError(401);
}

$tpl = StkTemplate::get("users/panel.tpl")
    ->assign("is_owner", $is_owner)
    ->assign("can_edit_role", $can_elevate_user && !$is_owner) // change role and activated status
    ->assign("can_see_settings", $can_elevate_user || $is_owner)
    ->assign("can_see_email", $is_owner || $is_admin);

$friends = Friend::getFriendsOf($user->getId(), $is_owner);
$tpl_data = [
    "username"      => h($user->getUserName()),
    "user_id"       => $user->getId(),
    "date_register" => $user->getDateRegister(),
    "real_name"     => h($user->getRealName()),
    "email"         => h($user->getEmail()),
    "role"          => $user_role,
    "homepage"      => h($user->getHomepage()),
    "addon_types"   => [],
    "settings"      => [
        "profile"  => [],
        "elevate"  => [],
        "password" => []
    ],
    "friends"       => $friends,
    "achievements"  => Achievement::getAchievementsOf($user->getId())
];

// refresh friends cache and build friend array
$logged_friend = [];
if ($is_owner)
{
    User::setFriends($friends);
}
else // build buttons
{
    $friend = User::isLoggedFriendsWith($user->getUserName());

    if ($friend)
    {
        $logged_friend = [
            "is_pending" => $friend->isPending(),
            "is_asker"   => $friend->isAsker(),
        ];
    }
}
$tpl->assign("logged_friend", $logged_friend);

// fill users addons
$tpl_data['has_addons'] = false;
foreach (Addon::getAllowedTypes() as $type)
{
    switch ($type)
    {
        case Addon::TRACK:
            $heading = _h('Tracks');
            $no_items = _h('This user has not uploaded any tracks.');
            break;

        case Addon::KART:
            $heading = _h('Karts');
            $no_items = _h('This user has not uploaded any karts.');
            break;

        case Addon::ARENA:
            $heading = _h('Arenas');
            $no_items = _h('This user has not uploaded any arenas.');
            break;

        default:
            $heading = "Something went wrong";
            $no_items = "";
    }
    $addon_type = [
        "name"     => Addon::typeToString($type),
        "heading"  => $heading,
        "no_items" => $no_items,
        "items"    => []
    ];

    $addons = $user->getAddonsData($type);
    if (!$addons) // no addons for this type
    {
        $tpl_data["addon_types"][] = $addon_type;
        continue;
    }

    $addons_tpl = [];
    foreach ($addons as $addon)
    {
        // Only list the latest revision of the add-on
        if (!Addon::isLatest($addon["status"]))
        {
            continue;
        }

        $addon["css_class"] = "";
        if (!Addon::isApproved($addon["status"])) // not approved
        {
            $isOwner = ($addon['uploader'] === User::getLoggedId());
            $canEdit = User::hasPermission(AccessControl::PERM_EDIT_ADDONS);

            if (!$isOwner && !$canEdit)
            {
                continue;
            }
            $addon["css_class"] = "unavailable";
        }

        $addons_tpl[] = $addon;
    }

    // add to user template data
    $addon_type["items"] = $addons_tpl;
    $tpl_data['has_addons'] = $tpl_data['has_addons'] || !empty($addon_type["items"]);
    $tpl_data["addon_types"][] = $addon_type;
}


// can change the users role and activation field
// only if we are not the active user and have the permission
if ($can_elevate_user && !$is_owner)
{
    // role
    $role = ["options" => []];

    // check if current user can edit that role, if not we can not change to that role
    foreach (AccessControl::getRoleNames() as $db_role)
    {
        // has permission
        $can_edit = User::hasPermissionOnRole($db_role);
        $is_owner = ($user_role === $db_role);

        if ($can_edit || $is_owner)
        {
            $role["options"][$db_role] = $db_role;
            if ($is_owner)
            {
                $role["selected"] = $db_role;
            }
        }
    }
    $tpl_data["settings"]["elevate"] = $role;

    // activated
    if ($user->isActive())
    {
        $tpl_data["settings"]["elevate"]["activated"] = "checked";
    }
}

$tpl->assign("user", $tpl_data);
echo $tpl;
