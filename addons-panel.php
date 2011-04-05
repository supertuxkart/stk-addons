<?php
/* copyright 2009 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
 This file is part of stkaddons.                                 
                                                                              
 stkaddons is free software: you can redistribute it and/or      
 modify it under the terms of the GNU General Public License as published by  
 the Free Software Foundation, either version 3 of the License, or (at your   
 option) any later version.                                                   
                                                                              
 stkaddons is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for    
 more details.                                                                
                                                                              
 You should have received a copy of the GNU General Public License along with 
 stkaddons.  If not, see <http://www.gnu.org/licenses/>.   */
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: addons-panel.php
Version: 1
Licence: GPLv3
Description: page who is called in ajax and who give kart and track informations

***************************************************************************/
$security ="";
define('ROOT','./');
include('include.php');

if(!isset($_COOKIE['lang']))
{
    $timestamp_expire = time() + 365*24*3600;
    setcookie('lang', 'en_EN', $timestamp_expire);
}
setlocale(LC_ALL, $_COOKIE['lang'].'.UTF-8');

bindtextdomain('translations', 'locale');
textdomain('translations');
bind_textdomain_codeset('translations', 'UTF-8');

if (!isset($_GET['value']))
    $_GET['value'] = NULL;
if (!isset($_GET['id']))
    $_GET['id'] = NULL;
$_GET['id'] = (int)$_GET['id'];
if (!isset($_POST['value']))
    $_POST['value'] = NULL;
if (!isset($_POST['id']))
    $_POST['id'] = NULL;
$_POST['id'] = (int)$_POST['id'];

$type = (isset($_GET['type']))? $_GET['type'] : NULL;
if ($type != 'tracks' && $type != 'karts' && $type != 'users')
    die(_('This page cannot be loaded because an invalid add-on type was provided.'));
if (!isset($_GET['action'])) $_GET['action'] = NULL;
$action = $_GET['action'];
if ($action != NULL && $action != 'file' && $action != 'remove' && $action != 'approve')
    die(_('This page cannot be loaded because an invalid action was provided.'));

if($action == "file")
{
    $value = mysql_real_escape_string($_GET['value']);
    $id = mysql_real_escape_string($_GET['id']);
}
else
{
    $value = mysql_real_escape_string($_POST['value']);
    $id = mysql_real_escape_string($_POST['id']);
}

$addon = new coreAddon($type);
$addon->selectById($id);
if($action == "approve")
{
	$addon->approve();
	$addon->selectById($id);
}
elseif($action != "" && $action != "file")
{
	$addon->setInformation($action, $value);
	$addon->selectById($id);
}
if($action == "remove")
{
	$addon->remove();
}
elseif($action == "file")
{
	?>
	<html>
	<head>
	<meta http-equiv="refresh" content="0;URL=addons.php?addons=<?php echo $type.'&amp;title='.$addon->addonCurrent['name'];?>">
	</head>
	</html>
	<?php
	$addon->setFile();
	exit();
}
else
{
    $addon->viewInformation();
}?>
