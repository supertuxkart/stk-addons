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

File: about.php
Version: 1
Licence: GPLv3
Description: credits page

***************************************************************************/

define('ROOT','./');
$security ="";
$title = htmlspecialchars(_('STK Add-ons').' | '._('About'));
include('include.php');
include('include/top.php');
?>
	</head>
	<body>
		<?php 
		include('include/menu.php');
		?>
		<div id="content">
                    <h1>SuperTuxKart</h1>
                    <p><?php echo htmlspecialchars(_('SuperTuxKart is a Free 3D kart racing game, with many tracks, characters and items for you to try.')); ?></p>
                    <p><?php echo htmlspecialchars(_('Since version 0.7.1, SuperTuxKart has had the ability to fetch important messages from the STKAddons website. Since 0.7.2, the game has included a built-in add-on manager.').' ');
                        echo sprintf(htmlspecialchars(_('SuperTuxKart now has over %d karts, %d tracks, and %d arenas available in-game thanks to the add-on service.')),40,30,15); ?></p>
                    <p><?php echo htmlspecialchars(_('Of course, the artists who create all of this content must be thanked too. Without them, the add-on website would not be as great as it is today.')); ?></p>
                    <p><a href="http://supertuxkart.sourceforge.net/"><?php echo htmlspecialchars(_('Website')); ?></a> | <a href="http://sourceforge.net/donate/index.php?group_id=202302"><?php echo htmlspecialchars(_('Donate!')); ?></a></p>
                    
                    <h1>TuxFamily</h1>
                    <p><?php echo htmlspecialchars(_('TuxFamily is a non-profit organization. It provides free services for projects and contents dealing with the free software philosophy (free as in free speech, not as in free beer). They accept any project released under a free license (GPL, BSD, CC-BY-SA, Art Libre...).')); ?></p>
                    <p><?php echo htmlspecialchars(_('TuxFamily operates the servers on which STKAddons runs, for free. Because of them, we can provide the add-on service for SuperTuxKart. Each month, over a million downloads are made by SuperTuxKart players. We thank them very much for their generosity to us and to other open source projects.')); ?></p>
                    <p><a href="http://tuxfamily.org/"><?php echo htmlspecialchars(_('Website')); ?></a> | <a href="http://tuxfamily.org/en/support"><?php echo htmlspecialchars(_('Donate!')); ?></a></p>
                    
                    <h1><?php echo htmlspecialchars(_('Credits')); ?></h1>
		<?php
		$credits = file_get_contents("CREDITS");
                echo '<pre>'.$credits.'</pre>';
		?>
		</div>
		<?php
		include("include/footer.php");
		?>
		</head>
		</html>
