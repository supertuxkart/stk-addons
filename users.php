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

// set current user if not defined
$_GET['user'] = (isset($_GET['user'])) ? $_GET['user'] : User::getLoggedUserName();
$action = (isset($_GET['action'])) ? $_GET['action'] : null;

$tpl = StkTemplate::get('user.tpl')
    ->assignTitle(_h('Users'))
    ->assign("img_location", IMG_LOCATION)
    ->addScriptInclude("user.js");
$tplData = ["items" => [], "status" => "", "body" => ""];

// get all users from the database, create links
$users = User::getAllData();
$templateUsers = [];
foreach ($users as $user)
{
    // Make sure that the user is active, or the viewer has permission to
    // manage this type of user
    if (User::hasPermissionOnRole($user['role']) || $user['active'] == 1)
    {
        $templateUsers[] = [
            'username' => $user['user'],
            'active'   => (int)$user["active"]
        ];
    }
}

$tplData['items'] = $templateUsers;
$tplData['body'] = Util::ob_get_require_once(ROOT_PATH . 'users-panel.php');

// output the view
$tpl->assign('user', $tplData);
echo $tpl;
