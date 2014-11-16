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

$tpl = StkTemplate::get("stats/index.tpl")
    ->addDataTablesLibrary()
    ->addFlotLibrary()
    ->addUtilLibrary()
    ->addScriptInclude("stats.js");
$tpl_data = [];

if (isset($_GET["addons"]))
{
    $tpl->assignTitle(_h("Addon stats"));
    $tpl_data["body"] = Util::ob_get_require_once(STATS_PATH . "addons.php");
}
elseif (isset($_GET["files"]))
{
    $tpl->assignTitle(_h("File stats"));
    $tpl_data["body"] = Util::ob_get_require_once(STATS_PATH . "files.php");
}
elseif (isset($_GET["clients"]))
{
    $tpl->assignTitle(_h("Client stats"));
    $tpl_data["body"] = Util::ob_get_require_once(STATS_PATH . "clients.php");
}
elseif (isset($_GET["servers"]))
{
    $tpl->assignTitle(_h("Server stats"));
    $tpl_data["body"] = Util::ob_get_require_once(STATS_PATH . "servers.php");
}
else // display overview
{
    $tpl->assignTitle(_h("Stats Overview"));
    $tpl_data["body"] = Util::ob_get_require_once(STATS_PATH . "overview.php");
}

$tpl->assign("stats", $tpl_data);
echo $tpl;
