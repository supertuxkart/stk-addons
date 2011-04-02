<?php
/* copyright 2009 Lucas Baudin <xapantu@gmail.com>                 
 *
 * This file is part of stkaddons.
 * stkaddons is free software: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: coreAddon.php
Version: 1
Licence: GPLv3
Description: file where all fonctions are

***************************************************************************/

class coreUser
{
    var $reqSql;
    var $userCurrent;

    function selectById($id)
    {
        $querySql = 'SELECT *
            FROM '.DB_PREFIX.'users
            WHERE id = \''.$id.'\'';
        $this->reqSql = sql_query($querySql);
        $this->userCurrent = sql_next($this->reqSql);
    }

    function selectByUser($id)
    {
        $this->reqSql = sql_get_all_where('users', "user", $id);
        $this->userCurrent = sql_next($this->reqSql);
    }

    function loadAll()
    {
        $querySql = 'SELECT *
            FROM '.DB_PREFIX.'users
            ORDER BY `user` ASC, `id` ASC';
        $this->reqSql = sql_query($querySql);
        return $this->reqSql;
    }

    function next()
    {
        $this->userCurrent = sql_next($this->reqSql);
        if(!$this->userCurrent)
            return false;
        return true;
    }

    function viewInformation($config=True)
    {
        global $user;
        if (!$user->logged_in)
            return false;

        $this->writeInformation();
        //write configuration for the submiter and administrator
        if(($_SESSION['role']['manage'.$this->userCurrent['role'].'s'] || $this->userCurrent['id'] == $_SESSION['userid']) && $config)
        {
            $this->writeConfig();
        }
    }

    function writeInformation()
    {
        global $dirDownload, $dirUpload;
		echo '<table><tr><td>'._('Username:').'</td><td>'.$this->userCurrent['user'].'</td></tr>';
		echo '<tr><td>'._('Registration Date:').'</td><td>'.$this->userCurrent['reg_date'].'</td></tr>';
                echo '<tr><td>'._('Real Name:').'</td><td>'.$this->userCurrent['name'].'</td></tr>';
		if(file_exists(UP_LOCATION.'avatar/'.$this->userCurrent['avatar']) && $this->userCurrent['avatar'] != NULL)
		{
		echo '<tr><td>'._('Avatar:').'</td><td><img class="avatar" src="'.DOWN_LOCATION.'avatar/'.$this->userCurrent['avatar'].'" /></td></tr>';
		}
		echo '<tr><td>'._('Role:').'</td><td>'._($this->userCurrent['role']).'</td></tr>';
		echo '<tr><td>'._('Homepage:').'</td><td><a href="'.$this->userCurrent['homepage'].'" >'.$this->userCurrent['homepage'].'</a></td></tr></table>';
		
		
		echo '<h3>'._('My Karts').'</h3>';
		$mykart = new coreAddon("karts");
		$mykart->selectByUser($this->userCurrent['id']);
		echo '<ul>';
		while($mykart->addonCurrent)
		{
                    if ($mykart->addonCurrent['status'] & F_APPROVED || $mykart->addonCurrent['uploader'] == $this->userCurrent['id']) {
                        echo'<li><a href="addon-view.php?addons=karts&amp;title='.$mykart->addonCurrent['id'].'">';
                        echo $mykart->addonCurrent['name'];
                        if (($mykart->addonCurrent['status'] & F_APPROVED) != F_APPROVED)
                            echo ' ('._('Not approved').')';
                        echo'</a></li>';
                    }
                    $mykart->next();
		}
		echo '</ul>';
		
		echo '<h3>'._('My Tracks').'</h3>';
		$mytrack = new coreAddon("tracks");
		$mytrack->selectByUser($this->userCurrent['id']);
		echo '<ul>';
		while($mytrack->addonCurrent)
		{
		    if($mytrack->addonCurrent['status'] & F_APPROVED || $mytrack->addonCurrent['uploader'] == $this->userCurrent['id'])
		    {
			echo'<li><a href="addon-view.php?addons=tracks&amp;title='.$mytrack->addonCurrent['id'].'">';
			echo $mytrack->addonCurrent['name'];
                        if (($mytrack->addonCurrent['status'] & F_APPROVED) != F_APPROVED)
                            echo ' ('._('Not approved').')';
			echo'</a></li>';
                    }
                    $mytrack->next();
		}
		echo '</ul>';
    }

    function writeConfig()
    {
        global $dirDownload, $dirUpload;
        echo '
        <hr />
        <h3>Configuration</h3>
        <form enctype="multipart/form-data" action="?title='.$this->userCurrent['id'].'&amp;action=config" method="GET" >
        <table>';
        echo '<tr><td>'._('Homepage:').'</td><td><input type="text" name="homepage" value="'.$this->userCurrent['homepage'].'" /></td></tr>';
        echo '<tr><td>'._('Avatar:').'</td><td><input type="file" name="avatar" /></td></tr>';
        // Edit role if allowed
        if($_SESSION['role']['manage'.$this->userCurrent['role'].'s'] == true || $_SESSION['userid'] == $this->userCurrent['id'])
        {
            echo '<tr><td>'._('Role:').'</td><td>';
            echo '<select name="range">';
            echo '<option value="basicUser">Basic User</option>';
            $range = array("moderator","administrator");
            for($i=0;$i<2;$i++)
            {
                if($_SESSION['role']['manage'.$range[$i].'s'] == true)
                {
                    echo '<option value="'.$range[$i].'"';
                    if($this->userCurrent['role'] == $range[$i]) echo 'selected="selected"';
                    echo '>'.$range[$i].'</option>';
                }
            }
            echo '</select>';
            echo '</td></tr><tr><td>'._('User Activated:').'</td><td>';
            echo '<input type="checkbox" name="available" ';
            if($this->userCurrent['active'] == 1)
            {
                echo 'checked="checked" ';
            }
            echo '/></td></tr>';
        }
        echo '<tr><td></td><td><input type="submit" value="'._('Save Configuration').'" disabled /></td></tr>';
        echo '</table></form>';
        if($this->userCurrent['id'] == $_SESSION['userid'])
        {
            echo '<h3>'._('Change Password').'</h3>
            <form action="users.php?user='.$this->userCurrent['user'].'&amp;action=password" method="POST">
            '._('Old Password:').'<br />
            <input type="password" name="oldPass" /><br />
            '._('New Password:').'<br />
            <input type="password" name="newPass" /><br />
            '._('New Password (Confirm):').'<br />
            <input type="password" name="newPass2" /><br />
            <input type="submit" value="'._('Change Password').'" />
            </form>';
            }
	}
 
    function setPass()
    {
        $newPass = mysql_real_escape_string($_POST['newPass']);
        $succes =false;
        if($newPass != $_POST['newPass2'])
        {
            echo '<span class="error">'._('Your passwords do not match.').'</span><br />';
            return false;
        }
        if(hash('sha256',$_POST['oldPass']) != $this->userCurrent['pass'])
        {
            echo '<span class="error">'._('Your old password is not correct.').'</span><br />';
            return false;
        }

        if($_SESSION['userid'] == $this->userCurrent['id'])
        {
                mysql_query("UPDATE `".DB_PREFIX."users` SET `pass` = '".hash('sha256',$_POST['newPass'])."' WHERE `id` =".$this->userCurrent['id']." LIMIT 1 ;");
                echo _('Your password is changed.').'<br />';
                $_SESSION['pass'] = hash('sha256',$_POST['newPass']);
                $succes=true;
        }

        return $succes;
    }
    function permalink()
    {
        return 'users.php?user='.$this->userCurrent['user'];
    }
    function setAvailable()
    {
        global $base;
        if($_SESSION['role']['manageaddons'] == true)
        {
            if($this->userCurrent['available'] == 0)
            {
                mysql_query("UPDATE `".DB_PREFIX.$this->addonType."` SET `available` = '1' WHERE `id` =".$this->addonCurrent['id']." LIMIT 1 ;");
            }
            else mysql_query("UPDATE .`".DB_PREFIX.$this->addonType."` SET `available` = '0' WHERE `id` =".$this->addonCurrent['id']." LIMIT 1 ;");
        }
    }
	function setRange($range)
	{
	
		global $base;
		if($_SESSION['role']['manage'.$this->userCurrent['range'].'s'] == true && $_SESSION['role']['manage'.$range.'s'] == true)
		{
			mysql_query("UPDATE `".$base."`.`users` SET `range` = '".$range."' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
		}
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
		if($_SESSION['role']['manage'.$this->addonCurrent['range'].'s'] == true)
		{
			if($this->addonCurrent['available'] == 0) mysql_query("UPDATE `".$base."`.`users` SET `available` = '1' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;") or die(mysql_error());
			if($this->addonCurrent['available'] == 1) mysql_query("UPDATE `".$base."`.`users` SET `available` = '0' WHERE `users`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;")  or die(mysql_error());
		}
	}
	function setRange($range)
	{
	
		global $base;
		if($_SESSION['role']['manage'.$this->addonCurrent['range'].'s'] == true && $_SESSION['role']['manage'.$range.'s'] == true)
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
		if($_SESSION['role']['manage'.$this->addonCurrent['range'].'s'] == true || $_SESSION['id'] == $this->addonCurrent['id'])
		{
			echo 'Mail : '.$this->addonCurrent['mail'].'';
		}
		echo '</table>';
		if($_SESSION['role']['manage'.$this->addonCurrent['range'].'s'] == true)
		{
			echo '<form action="#" method="GET">';
			echo '<select onchange="addonRequest(\'users-panel.php?action=range&value=\'+this.value, '.$this->addonCurrent['id'].')" name="range" id="range'.$this->addonCurrent['id'].'">';
			echo '<option value="basicUser">Basic User</option>';
			$range = array("moderator","administrator");
			for($i=0;$i<2;$i++)
			{
				if($_SESSION['role']['manage'.$range[$i].'s'] == true)
				{
					echo '<option value="'.$range[$i].'"';
					if($this->addonCurrent['range'] == $range[$i]) echo 'selected="selected"';
					echo '>'.$range[$i].'</option>';
				}
			}
			echo '</select>';
			echo '<input  onchange="addonRequest(\'users-panel.php?action=available\', '.$this->addonCurrent['id'].')" type="checkbox" name="available" id="available" ';
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
