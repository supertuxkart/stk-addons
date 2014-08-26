<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
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
require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
AccessControl::setLevel(AccessControl::PERM_EDIT_ADDONS);

$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;
$_GET['view'] = (isset($_GET['view'])) ? $_GET['view'] : 'overview';

$tpl = StkTemplate::get("manage.tpl")
    ->assignTitle(_h("Manage"))
    ->addUtilLibrary()
    ->addDataTablesLibrary()
    ->addScriptInclude("manage.js")
    ->assign("can_edit_settings", User::hasPermission(AccessControl::PERM_EDIT_SETTINGS))
    ->assign("can_edit_roles", User::hasPermission(AccessControl::PERM_EDIT_PERMISSIONS));
$tpl_data = ["status" => "", "body" => ""];

// right panel
$tpl_data["body"] = Util::ob_get_require_once(ROOT_PATH . "manage-panel.php");

// output the view
$tpl->assign("manage", $tpl_data);
echo $tpl;
