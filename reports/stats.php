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

$tpl = StkTemplate::get("stats-index.tpl")
    ->addScriptInclude("http://www.flotcharts.org/flot/jquery.flot.js", "")
    ->addScriptInclude("http://www.flotcharts.org/flot/jquery.flot.pie.js", "")
    ->addScriptInclude("http://www.flotcharts.org/flot/jquery.flot.time.js", "")
    ->addScriptInclude("stats.js");
$tplData = array();

if (isset($_GET["addons"]))
{
    $tpl->assignTitle("Addon stats");
    $tplData["body"] = Util::ob_get_require_once("stats-addons.php");
}
elseif (isset($_GET["files"]))
{
    $tpl->assignTitle("File stats");
    $tplData["body"] = Util::ob_get_require_once("stats-files.php");
}
elseif (isset($_GET["clients"]))
{
    $tpl->assignTitle("Client stats");
    $tplData["body"] = Util::ob_get_require_once("stats-clients.php");
}
elseif (isset($_GET["servers"]))
{
    $tpl->assignTitle("Server stats");
    $tplData["body"] = Util::ob_get_require_once("stats-servers.php");
}
else // display overview
{
    $tpl->assignTitle("Stats Overview");
    $tplData["body"] = Util::ob_get_require_once("stats-overview.php");
}

$tpl->assign("stats", $tplData);
echo $tpl;
