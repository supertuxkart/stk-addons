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

function get_self()
{
    $list = get_included_files();
    return $list[0];
}
?>
<div id="global">
<div id="top-menu">
    <div id="top-menu-content">
        <div class="left">
    <?php
    if(User::$logged_in)
    {
        printf(htmlspecialchars(_('Welcome, %s')),$_SESSION['real_name']);
        echo '&nbsp;&nbsp;&nbsp;';
    }
    echo '<a href="index.php">';
    echo htmlspecialchars(_("Home"));
    echo '</a>';

    if (basename(get_self()) == 'addons.php')
    {
        if ($_GET['type'] == 'karts')
        {
            echo '<a href="addons.php?type=tracks">'.htmlspecialchars(_('Tracks')).'</a>';
            echo '<a href="addons.php?type=arenas">'.htmlspecialchars(_('Arenas')).'</a>';
        }
        elseif ($_GET['type'] == 'tracks')
        {
            echo '<a href="addons.php?type=karts">'.htmlspecialchars(_('Karts')).'</a>';
            echo '<a href="addons.php?type=arenas">'.htmlspecialchars(_('Arenas')).'</a>';
        }
        else
        {
            echo '<a href="addons.php?type=karts">'.htmlspecialchars(_('Karts')).'</a>';
            echo '<a href="addons.php?type=tracks">'.htmlspecialchars(_('Tracks')).'</a>';
        }
    }

    if(User::$logged_in)
    {
        echo'<a href="login.php?action=logout">'.htmlspecialchars(_("Log out")).'</a>';
        echo'<a href="users.php">'.htmlspecialchars(_("Users")).'</a>';
        echo'<a href="upload.php">'.htmlspecialchars(_("Upload")).'</a>';
        if ($_SESSION['role']['managesettings'])
            echo '<a href="manage.php">'.htmlspecialchars(_('Manage')).'</a>';
    }
    else
    {
        echo'<a href="login.php">';
        echo htmlspecialchars(_('Login'));
        echo '</a>';
    }
    echo'<a href="about.php">';
    echo htmlspecialchars(_('About'));
    echo '</a>';
     ?>
        </div>
        <div class="right">
            <div id="lang-menu">
                <a class="menu_head" href="#"><?php echo htmlspecialchars(_("Languages"));?></a>
                <ul class="menu_body">
                    <li><a href="<?php echo $page_url.'&amp;lang=en_US'; ?>"><img src="image/flag/en.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=de_DE'; ?>"><img src="image/flag/de.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=fr_FR'; ?>"><img src="image/flag/fr.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=ga_IE'; ?>"><img src="image/flag/ga.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=gl_ES'; ?>"><img src="image/flag/gl.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=nl_NL'; ?>"><img src="image/flag/nl.png" /></a></li>
                    <li><a href="<?php echo $page_url.'&amp;lang=ru_RU'; ?>"><img src="image/flag/ru.png" /></a></li>
                    <li><a href="https://translations.launchpad.net/stk/stkaddons">Translate<br />STK-Addons</a></li>
                </ul>
            </div>
        <a href="http://supertuxkart.sourceforge.net"> <?php echo htmlspecialchars(_("STK Homepage"));?></a>
        </div>
    </div>
</div>
