<?php
/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
 *
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
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

$bug_id = isset($_GET["bug_id"]) ? $_GET["bug_id"] : "";

// TODO redirect to a 404 page
if (!$bug_id)
{
    exit("No bug id provided");
}

if (!Bug::exists($bug_id))
{
    exit("Bug $bug_id does not exist");
}

$tpl = StkTemplate::get("bugs-view.tpl");
$bug = Bug::get($_GET["bug_id"]);
$comments = [];
foreach ($bug->getCommentsData() as $comment)
{
    $comments[] = [
        "id"          => $comment["id"],
        "user_name"   => User::getFromID($comment["user_id"])->getUserName(),
        "date"        => $comment["date"],
        "description" => $comment["description"]
    ];
}

$tpl_data = [
    "id"           => $bug->getId(),
    "title"        => $bug->getTitle(),
    "user_id"      => $bug->getUserId(),
    "user_name"    => User::getFromID($bug->getUserId())->getUserName(),
    "addon"        => $bug->getAddonId(),
    "date_report"  => $bug->getDateReport(),
    "date_edit"    => $bug->getDateEdit(),

    // close data
    "date_close"   => $bug->getDateClose(),
    "close_reason" => $bug->getCloseReason(),
    "close_id"     => $bug->getCloseId(),
    "close_name"   => ($bug->isClosed() ? User::getFromID($bug->getCloseId())->getUserName() : ""),
    "is_closed"    => $bug->isClosed(),

    "description"  => $bug->getDescription(),
    "comments"     => $comments
];

$tpl->assign("bug", $tpl_data)
    ->assign("current_url", urlencode(Util::getCurrentUrl(false, false)))
    ->assign("can_add_comment", User::hasPermission(AccessControl::PERM_ADD_BUG_COMMENT))
    ->assign("can_edit_bug", (User::getLoggedId() === $tpl_data["id"]) || User::hasPermission(AccessControl::PERM_EDIT_BUGS))
    ->assign("can_delete_bug", User::hasPermission(AccessControl::PERM_EDIT_BUGS))
    ->assign("can_edit_comment", User::hasPermission(AccessControl::PERM_EDIT_BUGS));

echo $tpl;
