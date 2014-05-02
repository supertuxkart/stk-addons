<?php
/**
 * Copyright        2009 Lucas Baudin <xapantu@gmail.com>
 *           2011 - 2014 Stephen Just <stephenjust@gmail.com>
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

if (!defined('ROOT'))
    define('ROOT','./');
require_once('include.php');
require_once(INCLUDE_DIR.'StkTemplate.class.php');

$a_tpl = new StkTemplate('addons-panel.tpl');

// POST used with javascript navigation
// GET used with everything else
if (!isset($_GET['id']))
    $_GET['id'] = NULL;
if (!isset($_POST['id']))
    $_POST['id'] = NULL;

$type = (isset($_GET['type']))? $_GET['type'] : NULL;
if (!Addon::isAllowedType($type))
    die(htmlspecialchars(_('This page cannot be loaded because an invalid add-on type was provided.')));

if(isset($_GET['id']))
    $id = $_GET['id'];
else
    $id = $_POST['id'];

try {
    $viewer = new AddonViewer($id);
    $viewer->fillTemplate($a_tpl);
    echo $a_tpl;
    echo $viewer;
}
catch (Exception $e) {
    echo '<span class="error">'.$e->getMessage().'</span><br />';
}
