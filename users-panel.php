<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2012-2014 Stephen Just <stephenjust@users.sf.net>
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
include_once('include.php');
AccessControl::setLevel('basicPage');
if(isset($_GET['id']))
	$id = mysql_real_escape_string($_GET['id']);
else
	$_GET['id'] = 0;
	

$addon = new coreUser;
if ($_GET['id'] !== 0)
    $addon->selectByUser(mysql_real_escape_string($id));

$addon->viewInformation();
?>
