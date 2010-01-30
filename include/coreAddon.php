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
class coreAddon
{
	
	var $addonType;
	var $reqSql;
	var $addonCurrent;
	function coreAddon($type)
	{
		$this->addonType = $type;
	}
	function selectById($id)
	{
		$this->reqSql = mysql_query("SELECT * FROM ".$this->addonType." WHERE `".$this->addonType."`.`id` =".$id." LIMIT 1 ;");
		$this->addonCurrent = mysql_fetch_array($this->reqSql);
	}
	function selectByUser($id)
	{
		$this->reqSql = mysql_query("SELECT * FROM ".$this->addonType." WHERE `".$this->addonType."`.`user` =".$id." ;");
	}
	function loadAll()
	{
		$this->reqSql = mysql_query("SELECT * FROM ".$this->addonType) or die(mysql_error());
	}
	function next()
	{
		$succes = true;
		$this->addonCurrent = mysql_fetch_array($this->reqSql) or $succes = false;
		return $succes;
	}
	function setAvailable()
	{
		global $base;
		if($_SESSION['range']['manageaddons'] == true)
		{
			if($this->addonCurrent['available'] == 0)  mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `available` = '1' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
			else mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `available` = '0' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
		}
	}
	function setDescription($newDesc)
	{
		global $base;
		if($_SESSION['range']['manageaddons'] == true|| $this->addonCurrent['user'] == $_SESSION['id'])
		{
			mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `description` = '".$newDesc."' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
		}
	}
	
	function setFile()
	{
		global $base, $dirUpload;
		echo $type;
		if($_SESSION['range']['manageaddons'] == true|| $this->addonCurrent['user'] == $_SESSION['id'])
		{
			if($_POST['fileType']!="icon" || $this->addonType!="tracks")
			{
			if (isset($_FILES['fileSend'])) {
				$chemin_destination = $dirUpload.$_POST['fileType'].'/';
				unlink($chemin_destination.$this->addonCurrent[$_POST['fileType']]);
				move_uploaded_file($_FILES['fileSend']['tmp_name'], $chemin_destination.$this->addonCurrent[$_POST['fileType']]);
			}
			}
		}
	}
	
	function setStkVersion($version)
	{
		global $base;
		if($_SESSION['range']['manageaddons'] == true|| $this->addonCurrent['user'] == $_SESSION['id'])
		{
			mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `versionStk` = '".$version."' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
		}
	}
	function remove()
	{
		global $base;
		if($_SESSION['range']['manageaddons'] == true)
		{
			mysql_query("DELETE FROM `".$base."`.`".$this->addonType."` WHERE `".$this->addonType."`.`id` = ".$this->addonCurrent['id']." LIMIT 1");
		}
	}
	function viewInformations()
	{
		global $dirDownload, $dirUpload;
		echo '<div id="accordion">';
		echo '<div>';
		//write image
		echo '<img class="preview" src="image.php?type=big&amp;pic='.$dirUpload.'image/'.$this->addonCurrent['image'].'" />';
		echo '<table><tr><td><b>Name : </b></td><td>';
		//write name
		echo $this->addonCurrent['name'];

		echo '</td></tr><tr><td><b>Description : </b></td><td>';
		// write description
		echo $this->addonCurrent['description'];

		echo '</td></tr><tr><td><b>Version : </b></td><td>';

		echo $this->addonCurrent['version'];
		echo '</td></tr><tr><td><b>Version of STK : </b></td><td>';
		echo $this->addonCurrent['versionStk'];
		$user = new coreUser();
		$user->selectById($this->addonCurrent['user']);
		echo '</td></tr><tr><td><b>Author : </b></td><td>'.$user->userCurrent['login'].'</td></tr>';
		echo '</table></div>';
		echo '<a href="'.$dirDownload.'file/'.$this->addonCurrent['file'].'"><img src="image/download.png" alt="Download" title="Download" /></a>';


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
	
	
	
	function addAddon($kartName, $kartDescription)
	{   
		global $base, $dirUpload;
		echo '<div id="content">';
		$existSql= mysql_query("SELECT * FROM `karts` WHERE `karts`.`name` = '".$kartName."'");
		$exist =true;
		$sql =  mysql_fetch_array($existSql) or $exist = false;
		if($exist == false && $kartName != null)
		{
			mysql_query("INSERT INTO `".$base."`.`".$this->addonType."` (`user` ,`name` ,`description` ,`file` ,`image` ,`icon` ,`date` ,`available` ,`version`, `versionStk`) 
			VALUES ('".$_SESSION["id"]."', '".$kartName."', '".$kartDescription."', '".$kartName.".zip"."', '".$kartName.".png"."', '".$kartName.".png"."', '".date("Y-m-d")."', '0', '1', '0.7');") or die(mysql_error());
			if (isset($_FILES['icon']) && $_FILES['icon']['type'] == "image/png") {
				$chemin_destination = $dirUpload.'icon/';
				move_uploaded_file($_FILES['kartIcon']['tmp_name'], $chemin_destination.$kartName.".png");

			}
			else
			{
				echo "Please re-upload your icon. It must be a png.";
			}
			if (isset($_FILES['image']) && $_FILES['image']['type'] == "image/png") {
				$chemin_destination = $dirUpload.'image/';
				move_uploaded_file($_FILES['kartImage']['tmp_name'], $chemin_destination.$kartName.".png");
			}
			else
			{
				echo "Please re-upload your image. It must be a png.";
			}
			if (isset($_FILES['file_addon'])) {
				$chemin_destination = $dirUpload.'file/';
				move_uploaded_file($_FILES['file_addon']['tmp_name'], $chemin_destination.$kartName.".zip");
			}
			echo "Successful, your kart will appear when a moderator will valid it.";
			
			$this->reqSql = mysql_query("SELECT * FROM ".$this->addonType." WHERE `".$this->addonType."`.`name` ='".$kartName."' LIMIT 1 ;");
		}
		echo '</div>';
		echo $this->reqSql;
		$this->addonCurrent = mysql_fetch_array($this->reqSql);
	}
}

?>
