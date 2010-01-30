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
include("include/connectMysql.php");
include("include/coreAddon.php");
include("include/coreHelp.php");


$type= mysql_real_escape_string($_GET['type']);
$id = mysql_real_escape_string($_GET['id']);
$action = mysql_real_escape_string($_GET['action']);
if($type=="help")
{
$addon = new coreHelp($type);
}
else
{
$addon = new coreAddon($type);
}
$addon->selectById($id);

if($action == "available")
{
	$addon->setAvailable();
	$addon->selectById($id);
}
if($action == "description")
{
$value = mysql_real_escape_string($_GET['value']);
	$addon->setDescription($value);
	$addon->selectById($id);
}
if($action == "remove")
{
	$addon->remove();
	exit();
}

if($action == "stkVersion")
{
	$addon->setStkVersion(mysql_real_escape_string($_GET['value']));
}
if($action == "file")
{
	?>
	<html>
	<head>
	<meta http-equiv="refresh" content="0;URL=index.php?title=<?php echo $type.$addon->addonCurrent['name'];?>">
	</head>
	</html>
	<?php
	$addon->setFile($action);
	exit();
}
$addon->viewInformations();
?>
