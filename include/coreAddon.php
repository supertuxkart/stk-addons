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
			mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `Description` = '".$newDesc."' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
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
			echo $_POST['fileType'];
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
			mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `STKVersion` = '".$version."' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
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
	function viewInformations($config=True)
	{
		global $dirDownload, $dirUpload;
		echo '<div id="accordion">';
		echo '<div>';
		//write image
		echo '<img class="preview" src="image.php?type=big&amp;pic='.$dirUpload.'image/'.$this->addonCurrent['image'].'" />';
		echo '<table><tr><td><b>'._("Name :").' </b></td><td>';
		//write name
		echo $this->addonCurrent['name'];

		echo '</td></tr><tr><td><b>'._("Description :").' </b></td><td>';
		// write description
		echo $this->addonCurrent['Description'];

		echo '</td></tr><tr><td><b>'._("Version :").' </b></td><td>';

		echo $this->addonCurrent['version'];
		echo '</td></tr><tr><td><b>'._("Version of STK :").' </b></td><td>';
		echo $this->addonCurrent['STKVersion'];
		$user = new coreUser();
		$user->selectById($this->addonCurrent['user']);
		echo '</td></tr><tr><td><b>'._("Author :").' </b></td><td>'.$user->userCurrent['login'].'</td></tr>';
		echo '</table></div>';
		echo '<a href="'.$dirDownload.'file/'.$this->addonCurrent['file'].'"><img src="image/download.png" alt="Download" title="Download" /></a>';
        echo '<br /><br /><b>Permalink :</b> http://'.$_SERVER['SERVER_NAME'].str_replace("addon.php", "addon-view.php", $_SERVER['SCRIPT_NAME']).'?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['name'];

		if(($_SESSION['range']['manageaddons']|| $this->addonCurrent['user'] == $_SESSION['id']) and $config)
		{
		    echo '<hr /><h3>Configuration</h3>';
		    echo '<form action="#" method="GET" >';
		    $propertie_sql = mysql_query("SELECT * FROM properties WHERE `properties`.`type` = '".$this->addonType."' AND `properties`.`lock` != 1;");
		    $file_str = "";
		    while($propertie = mysql_fetch_array($propertie_sql))
		    {
		        if($propertie['typefield'] == "textarea")
		        {
		            echo "<br />".$propertie['name']." :<br />";
                    echo '<textarea id="'.str_replace(" ", "", $propertie['name']).'">'.$this->addonCurrent[str_replace(" ", "", $propertie['name'])].'</textarea><br />';
                    echo '<input onclick="addonRequest(\'addon.php?type='.$this->addonType.'&amp;action='.str_replace(" ", "", $propertie['name']).'&amp;value=\'+document.getElementById(\''.str_replace(" ", "", $propertie['name']).'\').value, '.$this->addonCurrent['id'].')" value="Change '.$propertie['name'].'" type="button" />';
		        }
		        elseif($propertie['typefield'] == "enum")
		        {
		            echo "<br />".$propertie['name']." :<br />";
                    echo '<select onchange="addonRequest(\'addon.php?type='.$this->addonType.'&amp;action='.str_replace(" ", "", $propertie['name']).'&value=\'+this.value, '.$this->addonCurrent['id'].')">';
                    
                    $values =explode("\n", $propertie['default']);
                    foreach($values as $value)
                    {
                        echo '<option value="'.$value.'"';
                        if($this->addonCurrent[str_replace(" ", "", $propertie['name'])]==$value) echo 'selected="selected" ';
                        echo '>'.$value.'</option>';
                    }
                    echo '</select>';
			    }
		        elseif($propertie['typefield'] == "file")
		        {
		            $file_str .='<option value="'.strtolower(str_replace(" ", "", $propertie['name'])).'">'.$propertie['name'].'</option>';
		        }
		    }
		    echo '</form>';
		    echo '<form id="formKart" enctype="multipart/form-data" action="addon.php?action=file&amp;type='.$this->addonType.'&amp;id='.$this->addonCurrent['id'].'" method="POST">
			<select name="fileType">';
			echo $file_str;
			echo '</select>
			<input type="file" name="fileSend"/>
			<input type="submit"/>
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
		}
	}
	
	
	
	function addAddon($kartName, $kartDescription)
	{   
		global $base, $dirUpload;
		echo '<div id="content">';
		$existSql= mysql_query("SELECT * FROM `".$this->addonType."` WHERE `".$this->addonType."`.`name` = '".$kartName."'");
		$exist =true;
		$sql =  mysql_fetch_array($existSql) or $exist = false;
		if($exist == false && $kartName != null)
		{
			mysql_query("INSERT INTO `".$base."`.`".$this->addonType."` (`user` ,`name` ,`Description` ,`file` ,`image` ,`icon` ,`date` ,`available` ,`version`, `STKVersion`) 
			VALUES ('".$_SESSION["id"]."', '".$kartName."', '".$kartDescription."', '".$kartName.".zip"."', '".$kartName.".png"."', '".$kartName.".png"."', '".date("Y-m-d")."', '0', '1', '0.7');") or die(mysql_error());
			if (isset($_FILES['icon']) && $_FILES['icon']['type'] == "image/png") {
				$chemin_destination = $dirUpload.'icon/';
				move_uploaded_file($_FILES['icon']['tmp_name'], $chemin_destination.$kartName.".png");

			}
			elseif($this->addonType=="karts")
			{
				echo _("Please re-upload your icon. It must be a png.")."<br />";
			}
			if (isset($_FILES['image']) && $_FILES['image']['type'] == "image/png") {
				$chemin_destination = $dirUpload.'image/';
				move_uploaded_file($_FILES['image']['tmp_name'], $chemin_destination.$kartName.".png");
			}
			else
			{
				echo _("Please re-upload your image. It must be a png.")."<br />";
			}
			if (isset($_FILES['file_addon']) and $_FILES['file_addon']['type'] == "application/zip") {
				$chemin_destination = $dirUpload.'file/';
				move_uploaded_file($_FILES['file_addon']['tmp_name'], $chemin_destination.$kartName.".zip");
			}
			else
			{
			    echo _("Please re-upload your file. It must be a zip.")."<br />";
			}
			echo _("Successful, your kart will appear when a moderator will valid it.")."<br />";
			
			$this->reqSql = mysql_query("SELECT * FROM ".$this->addonType." WHERE `".$this->addonType."`.`name` ='".$kartName."' LIMIT 1 ;");
		}
		echo '</div>';
		$this->addonCurrent = mysql_fetch_array($this->reqSql);
	}
}

?>
