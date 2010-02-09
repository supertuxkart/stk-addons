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

File: addon.php
Version: 1
Licence: GPLv3
Description: page who is called in ajax and who give kart and track informations

***************************************************************************/
$security ="";
include("include/security.php");

if(!isset($_COOKIE['lang']))
{
    $timestamp_expire = time() + 365*24*3600;
    setcookie('lang', 'en_EN', $timestamp_expire);
}
setlocale(LC_ALL, $_COOKIE['lang'].'.UTF-8');

bindtextdomain('translations', 'locale');
textdomain('translations');
bind_textdomain_codeset('translations', 'UTF-8');

$type= mysql_real_escape_string($_GET['type']);
$action = mysql_real_escape_string($_GET['action']);

if($action=="file")
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
if($action == "available")
{
	$addon->setAvailable();
	$addon->selectById($id);
}
elseif($action != "" && $action!="file")
{
	$addon->setInformation($action, $value);
	$addon->selectById($id);
}
if($action == "remove")
{
	$addon->remove();
	exit();
}
if($action == "file")
{
	?>
	<html>
	<head>
	<meta http-equiv="refresh" content="0;URL=addon-view.php?addons=<?php echo $type.'&amp;title='.$addon->addonCurrent['name'];?>">
	</head>
	</html>
	<?php
	$addon->setFile();
	exit();
}
$addon->viewInformations();
?>
