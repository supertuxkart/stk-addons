<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
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
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: users-panel.php
Version: 1
Licence: GPLv3
Description: page who is called in ajax and who give user informations

***************************************************************************/
if (!isset($security))
    $security ="";
if (!defined('ROOT'))
    define('ROOT','./');
include_once('include.php');

if (!isset($_GET['id']))
    $_GET['id'] = NULL;
$_GET['id'] = (int)$_GET['id'];
if (!isset($_POST['id']))
    $_POST['id'] = NULL;
if (!is_numeric($_POST['id']) && !isset($_GET['user'])) {
    $_GET['user'] = $_POST['id'];
    $_POST['id'] = 0;
}
$_POST['id'] = (int)$_POST['id'];

$id = mysql_real_escape_string($_POST['id']);

$addon = new coreUser;
if ($_POST['id'] !== 0)
    $addon->selectById($id);
else
    $addon->selectByUser(mysql_real_escape_string($_GET['user']));

$addon->viewInformation();
?>
