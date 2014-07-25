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

$current_page = PaginationTemplate::getPageNumber();
$limit = PaginationTemplate::getLimitNumber();

// get all users from the database, create links
$users = User::getAll($limit, $current_page);
$templateUsers = [];
$count = 1;
foreach ($users as $user)
{
    // Make sure that the user is active, or the viewer has permission to
    // manage this type of user
    if (User::hasPermissionOnRole($user['role']) || $user['active'] == 1)
    {
        $count++;
        $templateUsers[] = [
            'username' => $user['user'],
            'active'   => (int)$user["active"]
        ];
    }
}

$pagination = PaginationTemplate::get()
    ->setItemsPerPage($limit)
    ->setTotalItems($count)
    ->setCurrentPage($current_page);

$tpl = StkTemplate::get("user-menu.tpl")
    ->assign("img_location", IMG_LOCATION)
    ->assign("menu_users", $templateUsers)
    ->assign("pagination", $pagination->toString());

echo $tpl;
