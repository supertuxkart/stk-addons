<?php
/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
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

$tpl = StkTemplate::get("bugs/add.tpl")
    ->assign("current_url", URL::encode(URL::getCurrent(false, false)));

// check permission
if (!User::hasPermission(AccessControl::PERM_ADD_BUG))
{
    $tpl->assign("bug", []);
    exit($tpl);
}

$tpl->assign(
    "bug",
    [
        "title"        => ["min" => Bug::MIN_TITLE,        "max" => Bug::MAX_TITLE],
        "description"  => ["min" => Bug::MIN_DESCRIPTION,  "max" => Bug::MAX_DESCRIPTION],
        "close_reason" => ["min" => Bug::MIN_CLOSE_REASON, "max" => Bug::MAX_CLOSE_REASON]
    ]
);
echo $tpl;
