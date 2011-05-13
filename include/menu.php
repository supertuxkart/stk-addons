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


/*
if($user->logged_in)
{
echo '<div id="advanced">
<a href="#" id="advance_button"><img src="image/'.$style.'/plus.png" /></a>
<div id="content_advanced">';
if($_SESSION['role']['manageaddons'] || $_SERVER['PHP_SELF'] != "index.php")
{
    if(preg_match("/index\.php/i", $_SERVER['PHP_SELF']))
        $reqSql = mysql_query("SELECT * FROM ".DB_PREFIX."history
            WHERE `action` != 'add' LIMIT 7;") or die(mysql_error());
    else $reqSql = mysql_query("SELECT * FROM ".DB_PREFIX."history LIMIT 7") or die(mysql_error());
	while($history = mysql_fetch_array($reqSql))
	{
	    if($_SESSION['role']['manageaddons'] || $history['action'] == "add")
	    {
            echo '<div class="news_home">';
            $action = explode(" ",$history['action']);
            switch($action[0])
            {
                case "add":
                    $option = explode("\n",$history['option']);
                    $user = new coreUser('users');
                    $user->selectById($history['user']);
                    $addon = new coreAddon($option[0]);
                    $addon->selectById($option[1]);
                    echo '<span class="newaddon">';
                    echo _("New ");
                    switch ($option[0])
                    {
                        case 'karts':
                        echo _("kart");
                        break;
                        case 'tracks':
                        echo _("track");
                        break;
                    }
                    echo '</span>';
                    echo ' <a href="'.$addon->permalink().'" >';
                    echo $addon->addonCurrent['name'];
                    echo '</a> ';
                    echo _('by');
                    echo ' <a href="'.$user->permalink().'">';
                    echo $user->addonCurrent['login'];
                    echo '</a>';
                    break;
                case "change":
                    $option = explode("\n",$history['option']);
                    $user = new coreUser('users');
                    $user->selectById($history['user']);
                    $addon = new coreAddon($option[0]);
                    $addon->selectById($option[1]);
                    echo ' <a href="'.$user->permalink().'">';
                    echo $user->addonCurrent['login'];
                    echo '</a>';
                    echo " "._("modified ");
                    echo ' <a href="'.$addon->permalink().'" >';
                    echo $addon->addonCurrent['name'];
	                    echo '</a> ';
            }
            echo '</div>';
        }
    }
} */
?>
<!--
<br />
<br />
<br />
<div class="news_home">
<a href="addons.php?addons=blender">Blender files</a>
</div>
<div class="news_home">
<form method="POST" action="mail.php?action=bug">
<textarea name="bug">
</textarea>
<input type="submit" />
</form>
</div> -->
<?php /*
echo '</div>';
echo '</div>';
} */
?>
<div id="global">
<div id="top-menu">
    <div id="top-menu-content">
        <div class="left">
    <?php
    if($user->logged_in)
    {
        echo _('Welcome').' '.$_SESSION['real_name'].'&nbsp;&nbsp;&nbsp;';
    }
    echo '<a href="index.php">';
    echo _("Home");
    echo '</a>';
    if($user->logged_in)
    {
        echo'<a href="login.php?action=logout">'._("Log out").'</a>';
        echo'<a href="users.php">'._("Users").'</a>';
        echo'<a href="upload.php">'._("Upload").'</a>';
        if ($_SESSION['role']['managesettings'])
            echo '<a href="manage.php">'._('Manage').'</a>';
    }
    else
    {
        echo'<a href="login.php">';
        echo _('Login');
        echo '</a>';
    }
    echo'<a href="about.php">';
    echo _('About');
    echo '</a>';
     ?>
        </div>
        <div class="right">
            <div id="lang-menu">
                <a class="menu_head" href="#"><?php echo _("Languages");?></a>
                <ul class="menu_body">
                    <li><a href="<?php echo $page_url.'&amp;lang=nl_NL'; ?>"><img src="image/flag/nl.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=fr_FR'; ?>"><img src="image/flag/fr.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=en_US'; ?>"><img src="image/flag/en.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=ga_IE'; ?>"><img src="image/flag/ga.png" /></a></li>
                    <!-- <li><a href="<?php echo $page_url.'&amp;lang=de_DE'; ?>"><img src="image/flag/de.png" /></a></li> -->
                </ul>
            </div>
        <a href="http://supertuxkart.sourceforge.net"> <?php echo _("STK Homepage");?></a>
        </div>
    </div>
</div>
