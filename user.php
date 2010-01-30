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

File: user.php
Version: 1
Licence: GPLv3
Description: page who is called in ajax and who modify user information

***************************************************************************/
$security = "";
include("include/security.php");
include("include/connectMysql.php");
include_once("include/coreUser.php");
$action= mysql_real_escape_string($_GET['action']);
$id = mysql_real_escape_string($_GET['id']);
$value = mysql_real_escape_string($_GET['value']);
$user = new coreUser($type);
$user->selectById($id);
if($action=="available")$user->setAvailable();
if($action=="range")$user->setRange($value);
if($action=="homepage")
{
echo '<form method="POST"  action="user.php?action=homepageSend&amp;id='.$user->userCurrent['id'].'">
<input type="text" name="homepage" value="'.$user->userCurrent['homepage'].'" />
<input type="submit"/>
</form>
';
exit();
}
if($action=="homepageSend")
{
$user->setHomepage(mysql_real_escape_string($_POST['homepage']));
	?>
	<html>
	<head>
	<meta http-equiv="refresh" content="0;URL=manageAccount.php?title=profileHomepage">
	</head>
	</html>
	<?php
exit();
}
if($action == "password")
{
	include("include/top.php");
	echo '</head><body>';
	include("menu.php");
	echo '<div id="content">';
	$user->setPass();
	echo '</div>';
	include("footer.php");
	exit();
}
$user->selectById($id);
$user->viewInformations();

?>
