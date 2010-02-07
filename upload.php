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

File: upload.php
Version: 2
Licence: GPLv3
Description: page where people can add karts or tracks

***************************************************************************/
$security = "addAddon";
include("include/security.php");
include("include/top.php");

// define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;

?>
	</head>
	<body>
		<?php
			include("menu.php");
		?>
		<?php
			if($_GET['action'] == "submit")
			{
				$kartName = mysql_real_escape_string($_POST['name']);
				$kartDescription = mysql_real_escape_string($_POST['description']);
				$new = new coreAddon($_POST['addons_type']);
				if($kartName != "")
				{
				    
				    $new->addAddon($kartName, $kartDescription);
				    echo '<div id="content">';
				    if($kartDescription=="")
				    {
				    echo _("Please add a description of your add-ons")."<br />";
				    }
				    $new->viewInformations(False);
				}
				echo '</div>';
			}
			else
			{
			?>
	    <div id="content">
	        <form id="formKart" enctype="multipart/form-data" action="upload.php?action=submit" method="POST">
	            <label><input  onclick="document.getElementById('kart_icon').disabled = false" type="radio" name="addons_type" value="karts" checked="checked"/>Kart</label>
	            <label><input onclick="document.getElementById('kart_icon').disabled = true" type="radio" name="addons_type" value="tracks" />Tracks<br /></label>
	            <label><?php echo _("Name :"); ?><br /><input type="text" name="name"/><br /></label>
	            <label><?php echo _("Description, it must be in english :"); ?><br /><textarea name="description"></textarea><br /></label>
	            <label><?php echo _("Image, it must be a .png :"); ?><br /><input type="file" name="image"/><br /></label>
	            <label><?php echo _("Icon (kart only), it must be a .png :"); ?><br /><input type="file" id="icon" name="icon"/><br /></label>
	            <label><?php echo _("Addon's file, it must be a .zip :"); ?><br /><input type="file" name="file_addon"/><br /></label>
	            <input type="submit" />
	        </form>
	    </div>
	<?php } include("include/footer.php"); ?>
	</body>
</html>
