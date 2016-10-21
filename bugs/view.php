<?php
/**
 * copyright 2014-2015 Daniel Butum <danibutum at gmail dot com>
 *
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
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

$bug_id = isset($_GET["bug_id"]) ? (int)$_GET["bug_id"] : 0;
$bug = null;

if (!$bug_id)
{
    Util::redirectError(404);
}
try
{
    $bug = Bug::get($bug_id);
}
catch(BugException $e)
{
    Util::redirectError(404);
}

$tpl = StkTemplate::get("bugs/view.tpl");

// clean comments
$comments_data = $bug->getCommentsData();
Util::htmlPurifyApply($comments_data, "description");

$tpl_data = [
    "id"           => $bug->getId(),
    "title"        => h($bug->getTitle()),
    "user_id"      => $bug->getUserId(),
    "user_name"    => h($bug->getUserName()),
    "addon"        => $bug->getAddonId(),
    "date_report"  => $bug->getDateReport(),
    "date_edit"    => $bug->getDateEdit(),

    // close data
    "close_reason" => Util::htmlPurify($bug->getCloseReason()),
    "close_name"   => h($bug->getCloseUserName()),
    "date_close"   => $bug->getDateClose(),
    "close_id"     => $bug->getCloseId(),
    "is_closed"    => $bug->isClosed(),

    "description"  => Util::htmlPurify($bug->getDescription()),
    "comments"     => $comments_data
];

$can_edit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);
$tpl->assign("bug", $tpl_data)
    ->assign("current_url", urlencode(Util::getCurrentUrl(false, false)))
    ->assign("can_add_comment", User::hasPermission(AccessControl::PERM_ADD_BUG_COMMENT))
    ->assign("can_edit_bug", (User::getLoggedId() === $tpl_data["id"]) || $can_edit)
    ->assign("can_delete_bug", $can_edit)
    ->assign("can_edit_comment", $can_edit);

echo $tpl;
