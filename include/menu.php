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
    echo '<a href="'.SITE_ROOT.'index.php">';
    echo htmlspecialchars(_("Home"));
    echo '</a>';

    if (basename(get_self()) == 'addons.php')
    {
        if ($_GET['type'] == 'karts')
        {
            echo '<a href="'.File::rewrite('addons.php?type=tracks').'">'.htmlspecialchars(_('Tracks')).'</a>';
            echo '<a href="'.File::rewrite('addons.php?type=arenas').'">'.htmlspecialchars(_('Arenas')).'</a>';
        }
        elseif ($_GET['type'] == 'tracks')
        {
            echo '<a href="'.File::rewrite('addons.php?type=karts').'">'.htmlspecialchars(_('Karts')).'</a>';
            echo '<a href="'.File::rewrite('addons.php?type=arenas').'">'.htmlspecialchars(_('Arenas')).'</a>';
        }
        else
        {
            echo '<a href="'.File::rewrite('addons.php?type=karts').'">'.htmlspecialchars(_('Karts')).'</a>';
            echo '<a href="'.File::rewrite('addons.php?type=tracks').'">'.htmlspecialchars(_('Tracks')).'</a>';
        }
    }

    if(User::$logged_in)
    {
        echo'<a href="'.SITE_ROOT.'login.php?action=logout">'.htmlspecialchars(_("Log out")).'</a>';
        echo'<a href="'.SITE_ROOT.'users.php">'.htmlspecialchars(_("Users")).'</a>';
        echo'<a href="'.SITE_ROOT.'upload.php">'.htmlspecialchars(_("Upload")).'</a>';
        if ($_SESSION['role']['manageaddons'])
            echo '<a href="'.SITE_ROOT.'manage.php">'.htmlspecialchars(_('Manage')).'</a>';
    }
    else
    {
        echo'<a href="'.SITE_ROOT.'login.php">';
        echo htmlspecialchars(_('Login'));
        echo '</a>';
    }
    echo'<a href="'.SITE_ROOT.'about.php">';
    echo htmlspecialchars(_('About'));
    echo '</a>';
     ?>
        </div>
        <div class="right">
            <div id="lang-menu">
                <a class="menu_head" href="#"><?php echo htmlspecialchars(_("Languages"));?></a>
                <ul class="menu_body">
		    <?php
		    // Generate language menu entries
		    // Format is: language code, image x-offset, y-offset, label
		    $langs = array(
			array('en_US',0,0,'EN'),
			array('ca_ES',-96,-99,'CA'),
			array('de_DE',0,-33,'DE'),
			array('es_ES',-96,-66,'ES'),
			array('fr_FR',0,-66,'FR'),
			array('ga_IE',0,-99,'GA'),
			array('gl_ES',-48,0,'GL'),
			array('id_ID',-48,-33,'ID'),
			array('it_IT',-96,-33,'IT'),
			array('nl_NL',-48,-66,'NL'),
			array('pt_BR',-144,0,'PT'),
			array('ru_RU',-48,-99,'RU'),
			array('zh_TW',-96,0,'ZH (T)')
		    );
		    for ($i = 0; $i < count($langs); $i++) {
			$url = $_SERVER['REQUEST_URI'];
			// Generate the url to change the language
			if (strstr($url,'?') === false)
			    $url .= '?lang='.$langs[$i][0];
			else {
			    // Make sure any existing instances of lang are removed
			    $url = preg_replace('/(&(amp;)?)*lang=[a-z_]+/i',NULL,$url);
			    $url .= '&amp;lang='.$langs[$i][0];
			    $url = str_replace('?&amp;','?',$url);
			}
			printf("\t\t\t<li class=\"flag\"><a href=\"%s\" style=\"background-position: %dpx %dpx;\">%s</a></li>\n",$url,$langs[$i][1],$langs[$i][2],$langs[$i][3]);
		    }
		    ?>
                    <li class="label"><a href="https://translations.launchpad.net/stk/stkaddons">Translate<br />STK-Addons</a></li>
                </ul>
            </div>
        <a href="http://supertuxkart.sourceforge.net"> <?php echo htmlspecialchars(_("STK Homepage"));?></a>
        </div>
    </div>
</div>
