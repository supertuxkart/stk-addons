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

// I18N: Website meta description
$meta_description = htmlspecialchars(_('This is the official SuperTuxKart add-on repository. It contains extra karts and tracks for the SuperTuxKart game.'));
?>
            <meta name="description" content="<?php echo $meta_description; ?>" />
	</head>
	<body>
		<?php 
		include(ROOT.'include/menu.php');
		?>
                <div style="text-align: center; width: 100%;">
                    <img id="logo_center"
                         src="image/logo_large.png"
                         alt="SuperTuxKart Logo"
                         title="SuperTuxKart Logo"
                         width="424"
                         height="325" />
                </div>

		<div id="index-menu">
		    <div>
                        <a href="<?php echo File::rewrite('addons.php?type=karts'); ?>" style="background-position: -106px 0px;">
                            <span>
				<?php
				    // I18N: Menu link
				    echo htmlspecialchars(_("Karts"));
				?>
			    </span>
                        </a>
		    </div>
                    <div>
                        <a href="<?php echo File::rewrite('addons.php?type=tracks'); ?>" style="background-position: 0px 0px;">
                            <span>
				<?php
				    // I18N: Menu link
				    echo htmlspecialchars(_("Tracks"));
				?>
			    </span>
                        </a>
		    </div>
                    <div>
                        <a href="<?php echo File::rewrite('addons.php?type=arenas'); ?>" style="background-position: -212px 0px;">
                            <span>
				<?php
				    // I18N: Menu link
				    echo htmlspecialchars(_("Arenas"));
				?>
			    </span>
                        </a>
		    </div>
                    <div>
                        <a href="http://trac.stkaddons.net/" style="background-position: -318px 0px;">
                            <span>
				<?php
				    // I18N: Menu link
				    echo htmlspecialchars(_("Help"));
				?>
			    </span>
                        </a>
		    </div>
		</div>
                <div id="news-panel">
                    <ul id="news-messages">
                        <?php
                        // Note most downloaded track and kart
                        $pop_kart = stat_most_downloaded('karts');
                        $pop_track = stat_most_downloaded('tracks');
                        printf('<li>'.htmlspecialchars(_('The most downloaded kart is %s.')).'</li>'."\n",Addon::getName($pop_kart));
                        printf('<li>'.htmlspecialchars(_('The most downloaded track is %s.')).'</li>'."\n",Addon::getName($pop_track));
                        
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
