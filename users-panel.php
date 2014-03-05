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

if (!defined('ROOT')) {
    define('ROOT', './');
}
include_once('include.php');
AccessControl::setLevel('basicPage');

if (!isset($_GET['id'])) {
    $_GET['id'] = null;
}
$_GET['id'] = (int)$_GET['id'];
if (!isset($_POST['id'])) {
    $_POST['id'] = null;
}
if (!is_numeric($_POST['id']) && !isset($_GET['user'])) {
    $_GET['user'] = $_POST['id'];
    $_POST['id'] = 0;
}
$_POST['id'] = (int)$_POST['id'];

$user = new coreUser();
if ($_POST['id'] != 0) {
    $user->selectById($_POST['id']);
} else {
    $user->selectByUser($_GET['user']);
}
echo $user->getViewInformation();