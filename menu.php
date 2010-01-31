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
		echo _("Welcome")." ".$_SESSION["login"];
	}
	echo '<a href="index.php">';
	echo _("Home");
	echo '</a>';
	if(isset($_SESSION["login"]))
	{
		echo'<a href="unlogin.php">'._("Log out").'</a>';
		echo'<a href="manageAccount.php">'._("Users").'</a>';
		echo'<a href="upload.php">'._("Upload").'</a>';
	}
	else
	{
		
	echo'<a href="login.php">';
	echo _('Login');
	echo '</a>';
	}
	//echo '<a href="upload.php">'._("Upload")'.</a>';
	 ?>
	     <div class="container">
<a class="menu_head"><?php echo _("Languages");?></a>
<ul class="menu_body">
<li><a href="<?php echo $nom_page.'&amp;lang=nl'; ?>"><img src="image/flag/nl.png" /></a></li>
<li><a href="<?php echo $nom_page.'&amp;lang=fr'; ?>"><img src="image/flag/fr.png" /></a></li>
<li><a href="<?php echo $nom_page.'&amp;lang=en'; ?>"><img src="image/flag/en.png" /></a></li>
</ul>
</div>
</div>
