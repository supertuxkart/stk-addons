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

File: index.php
Version: 1
Licence: GPLv3
Description: index page

***************************************************************************/
define('ROOT','./');
$security ="";
require('include.php');
include("include/top.php");
?>
	</head>
	<body>
		<?php 
		include(ROOT.'include/menu.php');
		?>
                <div style="text-align: center; width: 100%;"><img id="logo_center" src="image/logo_large.png" alt="SuperTuxKart Logo" /></div>

		<div id="select-addon-panel">
		    <div class="icon-container">
                        <a href="addons.php?type=karts">
                            <img src="image/karts.png" alt="<?php echo htmlspecialchars(_('Karts')); ?>" /><br />
                            <h2 class="menu">
                                <div class="left"></div>
                                <div class="center"><?php echo htmlspecialchars(_("Karts")); ?></div>
                                <div class="right"></div>
                            </h2>
                        </a>
		    </div>
                    <div class="icon-container">
                        <a href="addons.php?type=tracks">
                            <img src="image/tracks.png" alt="<?php echo htmlspecialchars(_('Tracks')); ?>" /><br />
                            <h2 class="menu" >
                                <div class="left"></div>
                                <div class="center"><?php echo htmlspecialchars(_("Tracks")); ?></div>
                                <div class="right"></div>
                            </h2>
                        </a>
		    </div>
                    <div class="icon-container">
                        <a href="http://supertuxkart.sourceforge.net/Category:Stkaddons">
                            <img src="image/help.png" alt="<?php echo htmlspecialchars(_('Help')); ?>" /><br />
                            <h2 class="menu">
                                <div class="left"></div>
                                <div class="center"><?php echo htmlspecialchars(_("Help")); ?></div>
                                <div class="right"></div>
                            </h2>
                        </a>
		    </div>
		</div>
                <div id="news-panel">
                    <ul id="news-messages">
                        <?php
                        // Note most downloaded track and kart
                        $pop_kart = stat_most_downloaded('karts');
                        $pop_track = stat_most_downloaded('tracks');
                        printf('<li>'.htmlspecialchars(_('The most downloaded kart is %s.')).'</li>'."\n",addon_name($pop_kart));
                        printf('<li>'.htmlspecialchars(_('The most downloaded track is %s.')).'</li>'."\n",addon_name($pop_track));
                        
                        $newsSql = 'SELECT * FROM `'.DB_PREFIX.'news`
                            WHERE `active` = 1
                            AND `web_display` = 1
                            ORDER BY `date` DESC';
                        $handle = sql_query($newsSql);
                        for ($result = sql_next($handle); $result; $result = sql_next($handle))
                        {
                            printf("<li>%s</li>\n",htmlentities($result['content']));
                        }
                        ?>
                    </ul>
                </div>
		<?php
		include("include/footer.php"); ?>
	</body>
</html>
