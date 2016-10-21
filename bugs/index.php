<?php
/**
 * Copyright 2014 Daniel Butum <danibutum at gmail dot com>
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

$tpl = StkTemplate::get('bugs/index.tpl')
    ->addDataTablesLibrary()
    ->addWYSIWYGLibrary()
    ->addTypeHeadLibrary()
    ->addBootstrapValidatorLibrary()
    ->addUtilLibrary()
    ->addScriptInclude("bugs.js");
$tpl_data = ["show_btn_file" => true];

if (isset($_GET["bug_id"]))
{
    $tpl->assignTitle(_h("View Bug"));
    $tpl_data["show_btn_file"] = false;
    $tpl_data["content"] = Util::ob_get_require_once(BUGS_PATH . "view.php");
}
elseif (isset($_GET["add"]))
{
    $tpl->assignTitle(_h("Add Bug"));
    $tpl_data["show_btn_file"] = false;
    $tpl_data["content"] = Util::ob_get_require_once(BUGS_PATH . "add.php");
}
else
{
    $tpl->assignTitle(_h("All bugs"));
    $tpl_data["content"] = Util::ob_get_require_once(BUGS_PATH . "all.php");
}

$tpl->assign("bugs", $tpl_data);
echo $tpl;
