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
class coreUser
{
	var $reqSql;
	var $userCurrent;
	function selectById($id)
	{
		$this->reqSql = mysql_query("SELECT * FROM users WHERE `users`.`id` =".$id." LIMIT 1 ;");
		$this->userCurrent = mysql_fetch_array($this->reqSql);
	}
	function loadAll()
	{
		$this->reqSql = mysql_query("SELECT * FROM users") or die(mysql_error());
	}
	function next()
	{
		$succes = true;
		$this->userCurrent = mysql_fetch_array($this->reqSql) or $succes = false;
		return $succes;
	}
	function setAvailable()
	{
	
		global $base;
		if($_SESSION['range']['manage'.$this->userCurrent['range'].'s'] == true)
		{
			if($this->userCurrent['available'] == 0) mysql_query("UPDATE `".$base."`.`users` SET `available` = '1' WHERE `users`.`id` =".$this->userCurrent['id']." LIMIT 1 ;") or die(mysql_error());
			if($this->userCurrent['available'] == 1) mysql_query("UPDATE `".$base."`.`users` SET `available` = '0' WHERE `users`.`id` =".$this->userCurrent['id']." LIMIT 1 ;")  or die(mysql_error());
		}
	}
	function setRange($range)
	{
	
		global $base;
		if($_SESSION['range']['manage'.$this->userCurrent['range'].'s'] == true && $_SESSION['range']['manage'.$range.'s'] == true)
		{
			mysql_query("UPDATE `".$base."`.`users` SET `range` = '".$range."' WHERE `users`.`id` =".$this->userCurrent['id']." LIMIT 1 ;");
		}
	}
	
	function setPass()
	{
	
		global $base;
		$newPass = mysql_real_escape_string($_POST['newPass']);
		$succes =false;
		if($newPass == $_POST['newPass2'])
		{
			if(md5($_POST['oldPass']) == $this->userCurrent['pass'])
			{
				
				if($_SESSION['id'] == $this->userCurrent['id'])
				{
					mysql_query("UPDATE `".$base."`.`users` SET `pass` = '".md5($_POST['newPass'])."' WHERE `users`.`id` =".$this->userCurrent['id']." LIMIT 1 ;");
					echo '<div id="content">
					Your password is changed.
					</div>';
					$succes=true;
				}
			}
			else
			{
				echo "Wrong old password";
			}
		}
		else
		{
			echo "Your passwords aren't same";
		}
		
		return $succes;
	}
	
	function setHomepage($page)
	{
	
		global $base;
		if($_SESSION['id'] == $this->userCurrent['id'])
		{
			mysql_query("UPDATE `".$base."`.`users` SET `homepage` = '".$page."' WHERE `users`.`id` =".$this->userCurrent['id']." LIMIT 1 ;");
		}
	}
	
	function viewInformations()
	{
		global $base;
		echo '<table><tr><td>Login : </td><td>'.$this->userCurrent['login'].'</td></tr>';
		echo '<tr><td>Date Registered:</td><td>'.$this->userCurrent['date'].'</td></tr>';
		echo '<tr><td>Homepage :</td><td>'.$this->userCurrent['homepage'].'</td></tr>';
		if($_SESSION['range']['manage'.$this->userCurrent['range'].'s'] == true || $_SESSION['id'] == $this->userCurrent['id'])
		{
			echo 'Mail : '.$this->userCurrent['mail'].'';
		}
		echo '</table>';
		if($_SESSION['range']['manage'.$this->userCurrent['range'].'s'] == true)
		{
			echo '<form action="#" method="GET">';
			echo '<select onchange="addonRequest(\'user.php?action=range&value=\'+this.value, '.$this->userCurrent['id'].')" name="range" id="range'.$this->userCurrent['id'].'">';
			echo '<option value="basicUser">Basic User</option>';
			$range = array("moderator","administrator");
			for($i=0;$i<2;$i++)
			{
				if($_SESSION['range']['manage'.$range[$i].'s'] == true)
				{
					echo '<option value="'.$range[$i].'"';
					if($this->userCurrent['range'] == $range[$i]) echo 'selected="selected"';
					echo '>'.$range[$i].'</option>';
				}
			}
			echo '</select>';
			echo '<input  onchange="addonRequest(\'user.php?action=available\', '.$this->userCurrent['id'].')" type="checkbox" name="available" id="available" ';
			if($this->userCurrent['available'] ==1)
			{
				echo 'checked="checked" ';
			}
			echo '/> <label for="available">Available</label><br /></form>';
		}
		echo '<h3>My karts</h3>';
		include("include/coreAddon.php");
		$mykart = new coreAddon("karts");
		$mykart->selectByUser($this->userCurrent['id']);
		echo '<ul>';
		while($mykart->next())
		{
			echo'<li><a href="index.php?title=karts'.$mykart->addonCurrent['name'].'">';
			echo $mykart->addonCurrent['name'];
			echo'</a></li>';
		}
		echo '</ul>';
		
		echo '<h3>My tracks</h3>';
		$mytrack = new coreAddon("tracks");
		$mytrack->selectByUser($this->userCurrent['id']);
		echo '<ul>';
		while($mytrack->next())
		{
			echo'<li><a href="index.php?title=tracks'.$mytrack->addonCurrent['name'].'">';
			echo $mytrack->addonCurrent['name'];
			echo'</a></li>';
		}
		echo '</ul>';
	}
}

?>
