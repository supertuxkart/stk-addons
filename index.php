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
$security ="";
include("include/security.php");
include("include/top.php");
include("include/config.php");
?>
	</head>
	<body>
		<?php 
		include("menu.php");
		?>
		<div id="old_site">
		<a href="/0.6">
		<?php
		echo "Old site : 0.6";
		?></a></div>
		<img id="logo_center" src="image/logo_large.png" />
		
		<div id="news_div">
		<div id="news_top">
		</div>
		<div id="news_center">
		STK development blog : 
		<?php
		
        if(!@fopen("rss", 'r'))
        {
            include("rss.php");
        }
        $fichier = fopen("rss", "r");
        echo fgets($fichier);
        fclose($fichier);
        echo '<hr />';
		$reqSql = mysql_query("SELECT * FROM history LIMIT 7") or die(mysql_error());
		while($history = mysql_fetch_array($reqSql))
		{
		    if($_SESSION['range']['manageaddons'] || $history['action'] == "add")
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
		?>
		</div>
		<div id="news_bottom">
		</div></div>
		<div id="add-ons-for"><h1><div class="left"></div><div class="center"><?php echo _("Add-ons for Supertuxkart 0.7"); ?></div><div class="right"></div></h1></div>
		<div id="add-ons-type">
		    <div class="addons">
		        <a href="addon-view.php?addons=karts" />
		        <img src="image/karts.png" />
		        <h2 class="menu"><div class="left"></div><div class="center"><?php echo _("Karts"); ?></div><div class="right"></div></h2>
		    </div><div class="addons">
		        <a href="addon-view.php?addons=tracks" />
		        <img src="image/tracks.png" />
		        <h2 class="menu" ><div class="left"></div><div class="center"><?php echo _("Tracks"); ?></div><div class="right"></div></h2>
		    </div><div class="addons">
		        <a href="http://supertuxkart.sourceforge.net/Category:Stkaddons" />
		        <img src="image/help.png" />
		        <h2 class="menu"><div class="left"></div><div class="center"><?php echo _("Help"); ?></div><div class="right"></div></h2>
		    </div>
		</div>
		<?php
		include("include/footer.php"); ?>
	</body>
</html>
