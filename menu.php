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

File: menu.php
Version: 1
Licence: GPLv3
Description: menu

***************************************************************************/

?>

<div id="global">
<div id="top">
	<?php
	if(isset($_SESSION["login"]))
	{
		echo "Welcome ".$_SESSION["login"];
	}
	echo '<a href="index.php">';
	echo _("Home");
	echo '</a>';
	if(isset($_SESSION["login"]))
	{
		echo'<a href="unlogin.php">Log out</a>';
		echo'<a href="manageAccount.php">User</a>';
		echo'<a href="upload.php">Upload</a>';
	}
	else
	{
		
	echo'<a href="login.php">';
	echo _('Login');
	echo '</a>';
	}
	//echo '<a href="upload.php">'._("Upload")'.</a>';
	 ?>
	 <div id="languages">
	     <a href="<?php echo $nom_page.'&amp;lang=fr'; ?>">French</a>
	     <a href="<?php echo $nom_page.'&amp;lang=en'; ?>">English</a>
	 </div>
</div>
