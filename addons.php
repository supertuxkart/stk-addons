<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2011-2014 Stephen Just <stephenjust@gmail.com>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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

// Validate addon-id parameter
$_GET['type'] = (isset($_GET['type'])) ? $_GET['type'] : null;
$_GET['name'] = (isset($_GET['name'])) ? Addon::cleanId($_GET['name']) : null; // name is actually the id
$_GET['rev'] = (isset($_GET['rev'])) ? (int)$_GET['rev'] : null;
$addon_exists = Addon::exists($_GET["name"]);
$status = "";

// addon type is optional
if (!$_GET['type'])
{
    $_GET['type'] = Addon::getTypeByID($_GET['name']);
}

// check type
switch (Addon::stringToType($_GET['type']))
{
    case Addon::TRACK:
        $type_label = _h('Tracks');
        break;

    case Addon::KART:
        $type_label = _h('Karts');
        break;

    case Addon::ARENA:
        $type_label = _h('Arenas');
        break;

    default:
        exit(_h('Invalid addon type.')); // TODO redirect with error
        break;
}

// build title
$title = $type_label . ' - ' . _h('SuperTuxKart Add-ons');
if ($addon_exists)
{
    $addonName = Addon::getNameByID($_GET['name']);
    if ($addonName)
    {
        $title = $addonName . ' - ' . $title;
    }
}

// build template
$tpl = StkTemplate::get("addons/index.tpl")
    ->assign("title", $title)
    ->assign("is_name", $_GET['name'])
    ->addUtilLibrary()
    ->addBootstrapMultiSelectLibrary()
    ->addScriptInclude("addon.js");
$tpl_data = [
    'menu'    => Util::ob_get_require_once(ROOT_PATH . "addons-menu.php"),
    'body'    => '',
    'type'    => $_GET['type'],
    'status'  => $status
];

// right panel
if ($addon_exists)
{
    $tpl_data['body'] = Util::ob_get_require_once(ROOT_PATH . 'addons-panel.php');
}

// output the view
$tpl->assign('addon', $tpl_data);
echo $tpl;
