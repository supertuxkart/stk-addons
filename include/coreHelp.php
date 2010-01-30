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

File: coreAddon.php
Version: 1
Licence: GPLv3
Description: file where all fonctions are

***************************************************************************/
include("config.php");
include_once("coreUser.php");
include_once("coreAddon.php");
class coreHelp extends coreAddon
{
	
	function viewInformations()
	{
		global $dirDownload, $dirUpload;
		echo '<div id="accordion">';
		echo $this->addonCurrent['name'];
		// write description
		echo "<br />";
		echo $this->addonCurrent['description'];
		if($_SESSION['range']['manageaddons']|| $this->addonCurrent['user'] == $_SESSION['id'])
		{
			echo '<hr /><h3>Configuration</h3><div>';
			echo '<form action="#" method="GET" >';
			echo '<textarea id="kartDescription">'.$this->addonCurrent['description'].'</textarea><br />';
			echo '<input onclick="addonRequest(\'addon.php?type='.$this->addonType.'&amp;action=description&amp;value=\'+document.getElementById(\'kartDescription\').value, '.$this->addonCurrent['id'].')" value="Change description" type="button" /><br /><br />';
			
			echo 'STK version : ';
			echo '<select onchange="addonRequest(\'addon.php?type='.$this->addonType.'&amp;action=stkVersion&value=\'+this.value, '.$this->addonCurrent['id'].')">
			<option value="0.6">0.6 or 0.6.2</option>
			';
			echo '<option value="0.7"';
			if($this->addonCurrent['versionStk']=="0.7") echo 'selected="selected" ';
			echo '>0.7 (irrlicht)</option>
			</select>';
			echo '</form>';
			echo '<form id="formKart" enctype="multipart/form-data" action="addon.php?action=file&amp;type='.$this->addonType.'&amp;id='.$this->addonCurrent['id'].'" method="POST">
			<select name="fileType">
				<option value="file">Add-ons</option>';
				if($this->addonType != "tracks")echo '				<option value="icon">Icon</option>';
			echo '	<option value="image">Image</option>
			</select>
			<input type="file" name="fileSend"/>
			<input type="submit" value="Submit" />
			</form>';
			if($_SESSION['range']['manageaddons'])
			{
				echo '<form action="#"><input  onchange="addonRequest(\'addon.php?type='.$this->addonType.'&amp;action=available\', '.$this->addonCurrent['id'].')" type="checkbox" name="available" id="available"';
				if($this->addonCurrent['available'] ==1)
				{
					echo 'checked="checked" ';
				}
				echo '/><label for="available">Available</label><br />';
				echo '<input type="button" onclick="verify(\'addonRequest(\\\'addon.php?type='.$this->addonType.'&amp;action=remove\\\', '.$this->addonCurrent['id'].')\')" value="Remove" /><br /></form>';
			}
			echo '</div>';
		}
	}
	
	
}

?>
