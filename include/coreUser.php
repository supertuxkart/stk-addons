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



class coreUser extends coreAddon
{
    function writeInformations()
    {
        global $dirDownload, $dirUpload;
		echo '<table><tr><td>'._("Login :").' </td><td>'.$this->addonCurrent['login'].'</td></tr>';
		echo '<tr><td>'._("Date Registered :").'</td><td>'.$this->addonCurrent['date'].'</td></tr>';
		if(file_exists($dirUpload.'/avatar/'.$this->addonCurrent['avatar']))
		{
		echo '<tr><td>'._("Avatar :").'</td><td><img class="avatar" src="'.$dirDownload.'/avatar/'.$this->addonCurrent['avatar'].'" /></td></tr>';
		}
		echo '<tr><td>'._("Range :").'</td><td>'._($this->addonCurrent['range']).'</td></tr>';
		echo '<tr><td>'._("Homepage :").'</td><td><a href="'.$this->addonCurrent['homepage'].'" >'.$this->addonCurrent['homepage'].'</a></td></tr></table>';
		
		
		echo '<h3>My karts</h3>';
		$mykart = new coreAddon("karts");
		$mykart->selectByUser($this->addonCurrent['id']);
		echo '<ul>';
		while($mykart->next())
		{
		    if($mykart->addonCurrent['available'] == 1)
		    {
			echo'<li><a href="addon-view.php?addons=karts&amp;title='.$mykart->addonCurrent['name'].'">';
			echo $mykart->addonCurrent['name'];
			echo'</a></li>';
			}
		}
		echo '</ul>';
		
		echo '<h3>My tracks</h3>';
		$mytrack = new coreAddon("tracks");
		$mytrack->selectByUser($this->addonCurrent['id']);
		echo '<ul>';
		while($mytrack->next())
		{
		    if($mytrack->addonCurrent['available'] == 1)
		    {
			echo'<li><a href="addon-view.php?addons=tracks&amp;title='.$mytrack->addonCurrent['name'].'">';
			echo $mytrack->addonCurrent['name'];
			echo'</a></li>';
			}
		}
		echo '</ul>';
    }
    function writeConfig()
    {
        global $dirDownload, $dirUpload;
        echo '
        <hr />
        <h3>Configuration</h3>
        <form action="#" method="GET" >';
        $propertie_sql = mysql_query("SELECT * FROM properties WHERE `properties`.`type` = '".$this->addonType."' AND `properties`.`lock` != 1;");
        $file_str = "";
        while($propertie = mysql_fetch_array($propertie_sql))
        {
            $cible = 'addonRequest(\'user.php?type='.$this->addonType.'&amp;action='.str_replace(" ", "", $propertie['name']).'\', '.$this->addonCurrent['id'].',document.getElementById(\''.str_replace(" ", "", $propertie['name']).'\').value)';
            if($propertie['typefield'] == "textarea")
            {
                echo "<br />".$propertie['name']." :<br />";
                echo '<textarea cols="75" rows="8" id="'.str_replace(" ", "", $propertie['name']).'">'.$this->addonCurrent[str_replace(" ", "", $propertie['name'])].'</textarea><br />';
                echo '<input onclick="'.$cible.'" value="Change '.$propertie['name'].'" type="button" />';
            }
            elseif($propertie['typefield'] == "text")
            {
                echo "</form><br />".$propertie['name']." :<br />";
                echo '<form action="javascript:'.$cible.'" method="GET" >';
                echo '<input type="text" id="'.str_replace(" ", "", $propertie['name']).'" value="'.$this->addonCurrent[str_replace(" ", "", $propertie['name'])].'" ><br />';
                echo '<input onclick="'.$cible.'" value="Change '.$propertie['name'].'" type="button" />';
                echo "</form>";
                echo '<form action="#" method="GET" >';
            }
            elseif($propertie['typefield'] == "enum")
            {
                echo "<br />".$propertie['name']." :<br />";
                echo '<select onchange="addonRequest(\'user.php?type='.$this->addonType.'&amp;action='.str_replace(" ", "", $propertie['name']).'\', '.$this->addonCurrent['id'].', this.value)">';
                
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
        echo '<form id="formKart" enctype="multipart/form-data" action="user.php?action=file&amp;type='.$this->addonType.'&amp;id='.$this->addonCurrent['id'].'" method="POST">
        <select name="fileType">';
        echo $file_str;
        echo '</select>
        <input type="file" name="fileSend"/>
        <input type="submit"/>
        </form>';
		if($_SESSION['range']['manage'.$this->addonCurrent['range'].'s'] == true)
		{
			echo '<form action="#" method="GET">';
			echo '<select onchange="addonRequest(\'user.php?action=range&value=\', '.$this->addonCurrent['id'].', this.value)" name="range" id="range'.$this->addonCurrent['id'].'">';
			echo '<option value="basicUser">Basic User</option>';
			$range = array("moderator","administrator");
			for($i=0;$i<2;$i++)
			{
				if($_SESSION['range']['manage'.$range[$i].'s'] == true)
				{
					echo '<option value="'.$range[$i].'"';
					if($this->addonCurrent['range'] == $range[$i]) echo 'selected="selected"';
					echo '>'.$range[$i].'</option>';
				}
			}
			echo '</select>';
			echo '<input  onchange="addonRequest(\'user.php?action=available\', '.$this->addonCurrent['id'].')" type="checkbox" name="available" id="available" ';
			if($this->addonCurrent['available'] ==1)
			{
				echo 'checked="checked" ';
			}
			echo '/> <label for="available">Available</label><br /></form>';
		}
		if($this->addonCurrent['id'] == $_SESSION['id'])
		{
		echo '<h3>Change password</h3>
		<form action="user.php?id='.$this->addonCurrent['id'].'&amp;action=password" method="POST">
		Old password :<br />
		<input type="password" name="oldPass" /><br />
		New password :<br />
		<input type="password" name="newPass" /><br />
		Please enter a second time your password : <br />
		<input type="password" name="newPass2" /><br />
		<input type="submit" value="Submit" />
		</form>';
		}
	}
	function setPass()
	{
	
		global $base;
		$newPass = mysql_real_escape_string($_POST['newPass']);
		$succes =false;
		if($newPass == $_POST['newPass2'])
		{
			if(md5($_POST['oldPass']) == $this->addonCurrent['pass'])
			{
				
				if($_SESSION['id'] == $this->addonCurrent['id'])
				{
					mysql_query("UPDATE `".$base."`.`users` SET `pass` = '".md5($_POST['newPass'])."' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
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
	function permalink()
	{
	    return 'account.php?title='.$this->addonCurrent['login'];
	}
}




/*
class coreUser
{
	var $reqSql;
	var $addonCurrent;
	function selectById($id)
	{
		$this->reqSql = mysql_query("SELECT * FROM users WHERE `users`.`id` =".$id." LIMIT 1 ;");
		$this->addonCurrent = mysql_fetch_array($this->reqSql);
	}
	function loadAll()
	{
		$this->reqSql = mysql_query("SELECT * FROM users") or die(mysql_error());
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
		if($_SESSION['range']['manage'.$this->addonCurrent['range'].'s'] == true)
		{
			if($this->addonCurrent['available'] == 0) mysql_query("UPDATE `".$base."`.`users` SET `available` = '1' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;") or die(mysql_error());
			if($this->addonCurrent['available'] == 1) mysql_query("UPDATE `".$base."`.`users` SET `available` = '0' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;")  or die(mysql_error());
		}
	}
	function setRange($range)
	{
	
		global $base;
		if($_SESSION['range']['manage'.$this->addonCurrent['range'].'s'] == true && $_SESSION['range']['manage'.$range.'s'] == true)
		{
			mysql_query("UPDATE `".$base."`.`users` SET `range` = '".$range."' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
		}
	}
	
	function setPass()
	{
	
		global $base;
		$newPass = mysql_real_escape_string($_POST['newPass']);
		$succes =false;
		if($newPass == $_POST['newPass2'])
		{
			if(md5($_POST['oldPass']) == $this->addonCurrent['pass'])
			{
				
				if($_SESSION['id'] == $this->addonCurrent['id'])
				{
					mysql_query("UPDATE `".$base."`.`users` SET `pass` = '".md5($_POST['newPass'])."' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
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
		if($_SESSION['id'] == $this->addonCurrent['id'])
		{
			mysql_query("UPDATE `".$base."`.`users` SET `homepage` = '".$page."' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
		}
	}
	
	function viewInformations()
	{
		global $base;
		echo '<table><tr><td>Login : </td><td>'.$this->addonCurrent['login'].'</td></tr>';
		echo '<tr><td>Date Registered:</td><td>'.$this->addonCurrent['date'].'</td></tr>';
		echo '<tr><td>Homepage :</td><td>'.$this->addonCurrent['homepage'].'</td></tr>';
		if($_SESSION['range']['manage'.$this->addonCurrent['range'].'s'] == true || $_SESSION['id'] == $this->addonCurrent['id'])
		{
			echo 'Mail : '.$this->addonCurrent['mail'].'';
		}
		echo '</table>';
		if($_SESSION['range']['manage'.$this->addonCurrent['range'].'s'] == true)
		{
			echo '<form action="#" method="GET">';
			echo '<select onchange="addonRequest(\'user.php?action=range&value=\'+this.value, '.$this->addonCurrent['id'].')" name="range" id="range'.$this->addonCurrent['id'].'">';
			echo '<option value="basicUser">Basic User</option>';
			$range = array("moderator","administrator");
			for($i=0;$i<2;$i++)
			{
				if($_SESSION['range']['manage'.$range[$i].'s'] == true)
				{
					echo '<option value="'.$range[$i].'"';
					if($this->addonCurrent['range'] == $range[$i]) echo 'selected="selected"';
					echo '>'.$range[$i].'</option>';
				}
			}
			echo '</select>';
			echo '<input  onchange="addonRequest(\'user.php?action=available\', '.$this->addonCurrent['id'].')" type="checkbox" name="available" id="available" ';
			if($this->addonCurrent['available'] ==1)
			{
				echo 'checked="checked" ';
			}
			echo '/> <label for="available">Available</label><br /></form>';
		}
		echo '<h3>My karts</h3>';
		include("include/coreAddon.php");
		$mykart = new coreAddon("karts");
		$mykart->selectByUser($this->addonCurrent['id']);
		echo '<ul>';
		while($mykart->next())
		{
			echo'<li><a href="index.php?addons=karts&amp;title='.$mykart->addonCurrent['name'].'">';
			echo $mykart->addonCurrent['name'];
			echo'</a></li>';
		}
		echo '</ul>';
		
		echo '<h3>My tracks</h3>';
		$mytrack = new coreAddon("tracks");
		$mytrack->selectByUser($this->addonCurrent['id']);
		echo '<ul>';
		while($mytrack->next())
		{
			echo'<li><a href="addon-view.php?addons=tracks&amp;title='.$mytrack->addonCurrent['name'].'">';
			echo $mytrack->addonCurrent['name'];
			echo'</a></li>';
		}
		echo '</ul>';
	}
}
*/
?>
