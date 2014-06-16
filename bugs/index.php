<?php
/**
 * Copyright 2014 Daniel Butum <danibutum at gmail dot com>
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

$tpl = new StkTemplate('bugs-index.tpl');
$tpl->assign('title', h(_('STK Add-ons') . ' | ' . _('Bugs')));
$tpl->addScriptInclude("bugs.js");
$tplData = array(
    "show_btn_file" => true
);

if(isset($_GET["bug_id"]))
{
    $tplData["show_btn_file"] = false;
    $tplData["content"] = Util::ob_get_require_once(BUGS_PATH . "view.php");
}
elseif(isset($_GET["add"]))
{
    $tplData["show_btn_file"] = false;
    $tplData["content"] = Util::ob_get_require_once(BUGS_PATH . "add.php");
}
else
{
    $tplData["content"] = Util::ob_get_require_once(BUGS_PATH . "all.php");
}

$tpl->assign("bugs", $tplData);
echo $tpl;

