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

    function viewInformation()
    {
        global $user;
        if (!User::$logged_in)
            return false;

        $this->writeInformation();

        // Allow current user to change own profile, and administrators
        // to change all profiles
        if($_SESSION['role']['manage'.$this->userCurrent['role'].'s']
                || $this->userCurrent['id'] == $_SESSION['userid'])
        {
            $this->writeConfig();
        }
    }

    function writeInformation()
    {
        echo '<h1>'.$this->userCurrent['user'].'</h1><br />';
        echo '<table><tr><td>'.htmlspecialchars(_('Username:')).'</td><td>'.$this->userCurrent['user'].'</td></tr>';
        echo '<tr><td>'.htmlspecialchars(_('Registration Date:')).'</td><td>'.$this->userCurrent['reg_date'].'</td></tr>';
        echo '<tr><td>'.htmlspecialchars(_('Real Name:')).'</td><td>'.$this->userCurrent['name'].'</td></tr>';
        echo '<tr><td>'.htmlspecialchars(_('Role:')).'</td><td>'.htmlspecialchars(_($this->userCurrent['role'])).'</td></tr>';
        if (strlen($this->userCurrent['homepage'] > 0))
        {
            echo '<tr><td>'.htmlspecialchars(_('Homepage:')).'</td><td><a href="'.$this->userCurrent['homepage'].'" >'.$this->userCurrent['homepage'].'</a></td></tr>';
        }
        echo '</table><br />';

        // List of karts created by the current user
        echo '<h3>'.htmlspecialchars(_('User\'s Karts')).'</h3><br />';
        $kartSql = 'SELECT `a`.*, `r`.`status`
            FROM `'.DB_PREFIX.'addons` `a`
            LEFT JOIN `'.DB_PREFIX.'karts_revs` `r`
            ON `a`.`id` = `r`.`addon_id`
            WHERE `a`.`uploader` = \''.$this->userCurrent['id'].'\'
            AND `a`.`type` = \'karts\'';
        $kartHandle = sql_query($kartSql);
        if (mysql_num_rows($kartHandle) == 0)
        {
            echo htmlspecialchars(_('This user has not uploaded any karts.')).'<br />';
        }
        else
        {
            // Print kart list
            echo '<ul>';
            for ($i = 0; $i < mysql_num_rows($kartHandle); $i++)
            {
                $kartResult = mysql_fetch_assoc($kartHandle);
                if ($kartResult['status'] & F_APPROVED)
                {
                    echo '<li><a href="addons.php?type=karts&amp;name='.$kartResult['id'].'">'
                        .$kartResult['name'].'</a></li>';
                }
                else
                {
                    if ($_SESSION['role']['manageaddons'] == false
                            && $kartResult['uploader'] != $_SESSION['userid'])
                        continue;
                    echo '<li class="unavailable"><a href="addons.php?type=karts&amp;name='.$kartResult['id'].'">'
                        .$kartResult['name'].'</a></li>';
                }
            }
            echo '</ul><br />';
        }

        echo '<h3>'.htmlspecialchars(_('User\'s Tracks')).'</h3><br />';
        $trackSql = 'SELECT `a`.*, `r`.`status`
            FROM `'.DB_PREFIX.'addons` `a`
            LEFT JOIN `'.DB_PREFIX.'tracks_revs` `r`
            ON `a`.`id` = `r`.`addon_id`
            WHERE `a`.`uploader` = \''.$this->userCurrent['id'].'\'
            AND `a`.`type` = \'tracks\'';
        $trackHandle = sql_query($trackSql);
        if (mysql_num_rows($trackHandle) == 0)
        {
            echo htmlspecialchars(_('This user has not uploaded any tracks.')).'<br />';
        }
        else
        {
            // Print track list
            echo '<ul>';
            for ($i = 0; $i < mysql_num_rows($trackHandle); $i++)
            {
                $trackResult = mysql_fetch_assoc($trackHandle);
                // Only list the latest revision of the track
                if (!($trackResult['status'] & F_LATEST))
                    continue;
                if ($trackResult['status'] & F_APPROVED)
                {
                    echo '<li><a href="addons.php?type=tracks&amp;name='.$trackResult['id'].'">'
                        .$trackResult['name'].'</a></li>';
                }
                else
                {
                    if ($_SESSION['role']['manageaddons'] == false
                            && $trackResult['uploader'] != $_SESSION['userid'])
                        continue;
                    echo '<li class="unavailable"><a href="addons.php?type=tracks&amp;name='.$trackResult['id'].'">'
                        .$trackResult['name'].'</a></li>';
                }
            }
            echo '</ul><br />';
        }
    }

    function writeConfig()
    {
        global $dirDownload, $dirUpload;
        echo '
        <hr />
        <h3>Configuration</h3><br />
        <form enctype="multipart/form-data" action="?user='.$this->userCurrent['user'].'&amp;action=config" method="POST" >
        <table>';
        echo '<tr><td>'.htmlspecialchars(_('Homepage:')).'</td><td><input type="text" name="homepage" value="'.$this->userCurrent['homepage'].'" disabled /></td></tr>';
        // Edit role if allowed
        if($_SESSION['role']['manage'.$this->userCurrent['role'].'s'] == true || $_SESSION['userid'] == $this->userCurrent['id'])
        {
            echo '<tr><td>'.htmlspecialchars(_('Role:')).'</td><td>';
            $role_disabled = NULL;
            if ($_SESSION['userid'] == $this->userCurrent['id'])
                    $role_disabled = 'disabled';
            echo '<select name="range" '.$role_disabled.'>';
            echo '<option value="basicUser">Basic User</option>';
            $range = array("moderator","administrator","supAdministrator","root");
            for($i=0;$i<count($range);$i++)
            {
                if($_SESSION['role']['manage'.$range[$i].'s'] == true || $this->userCurrent['role'] == $range[$i])
                {
                    echo '<option value="'.$range[$i].'"';
                    if($this->userCurrent['role'] == $range[$i]) echo ' selected="selected"';
                    echo '>'.$range[$i].'</option>';
                }
            }
            echo '</select>';
            echo '</td></tr><tr><td>'.htmlspecialchars(_('User Activated:')).'</td><td>';
            echo '<input type="checkbox" name="available" ';
            if($this->userCurrent['active'] == 1)
            {
                echo 'checked="checked" ';
            }
            echo '/></td></tr>';
        }
        echo '<tr><td></td><td><input type="submit" value="'.htmlspecialchars(_('Save Configuration')).'" /></td></tr>';
        echo '</table></form><br />';
        if($this->userCurrent['id'] == $_SESSION['userid'])
        {
            echo '<h3>'.htmlspecialchars(_('Change Password')).'</h3><br />
            <form action="users.php?user='.$this->userCurrent['user'].'&amp;action=password" method="POST">
            '.htmlspecialchars(_('Old Password:')).'<br />
            <input type="password" name="oldPass" /><br />
            '.htmlspecialchars(_('New Password:')).' ('.htmlspecialchars(_('Must be at least 6 characters long.')).')<br />
            <input type="password" name="newPass" /><br />
            '.htmlspecialchars(_('New Password (Confirm):')).'<br />
            <input type="password" name="newPass2" /><br />
            <input type="submit" value="'.htmlspecialchars(_('Change Password')).'" />
            </form>';
            }
	}
 
    function setPass()
    {
        $newPass = mysql_real_escape_string($_POST['newPass']);
        $succes =false;
        if($newPass != $_POST['newPass2'])
        {
            echo '<span class="error">'.htmlspecialchars(_('Your passwords do not match.')).'</span><br />';
            return false;
        }
        if(hash('sha256',$_POST['oldPass']) != $this->userCurrent['pass'])
        {
            echo '<span class="error">'.htmlspecialchars(_('Your old password is not correct.')).'</span><br />';
            return false;
        }
        if (strlen($_POST['newPass']) < 6)
        {
            echo '<span class="error">'.htmlspecialchars(_('Your password must be at least 6 characters long.')).'</span><br />';
            return false;
        }
        if($_SESSION['userid'] == $this->userCurrent['id'])
        {
                mysql_query("UPDATE `".DB_PREFIX."users` SET `pass` = '".hash('sha256',$_POST['newPass'])."' WHERE `id` =".$this->userCurrent['id']." LIMIT 1 ;");
                echo htmlspecialchars(_('Your password is changed.')).'<br />';
                $_SESSION['pass'] = hash('sha256',$_POST['newPass']);
                $succes=true;
        }

        return $succes;
    }
    
    function setConfig()
    {
        if ($_SESSION['role']['manage'.$this->userCurrent['role'].'s'])
        {
            // Set availability status
            if (!isset($_POST['available'])) $_POST['available'] = NULL;
            if ($_POST['available'] == 'on')
            {
                $available = 1;
                $verify = NULL;
            }
            else
            {
                // FIXME: Do we want to reset a verification code at all?
                $available = 0;
                $verify = cryptUrl(12);
            }
            $availableQuery = 'UPDATE `'.DB_PREFIX.'users`
                SET `active` = '.$available.',
                    `verify` = \''.$verify.'\'
                WHERE `id` = '.$this->userCurrent['id'];
            $availableSql = sql_query($availableQuery);
            if (!$availableSql)
                return false;
            
            // Set permission level
            if (isset($_POST['range']))
            {
                if ($_SESSION['role']['manage'.$_POST['range'].'s'])
                {
                    $rangeQuery = 'UPDATE `'.DB_PREFIX.'users`
                        SET `role` = \''.mysql_real_escape_string($_POST['range']).'\'
                        WHERE `id` = '.$this->userCurrent['id'];
                    $rangeSql = sql_query($rangeQuery);
                    if (!$rangeSql)
                        return false;
                }
            }
            
        }
    }
    
    function permalink()
    {
        return 'users.php?user='.$this->userCurrent['user'];
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
			echo'<li><a href="addons.php?addons=tracks&amp;title='.$mytrack->addonCurrent['name'].'">';
			echo $mytrack->addonCurrent['name'];
			echo'</a></li>';
		}
		echo '</ul>';
	}
}
*/
?>
