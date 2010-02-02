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
$title = "Supertuxkart Addon Manager";
include("include/security.php");
include("include/view.php");
include("include/top.php");
include("include/coreAddon.php");
include("include/config.php");
?>
    </head>
    <body>
        <?php 
        include("menu.php");
        ?>
		<div id="select-addons">
		<div id="select-addons_top">
		</div>
		<div id="select-addons_center">
		<?php
        $js = "";
        loadAddons();
		?>
		</div>
		<div id="select-addons_bottom">
		</div></div>
        <div id="disAddon_content">
        <div id="disAddon"></div></div>
        <?php
        echo '<script type="text/javascript">';
        echo $js;
        echo '</script>';
        include("include/footer.php"); ?>
    </body>
</html>
<?php
function loadAddons()
{
    global $addon, $dirDownload, $dirUpload, $js;
    if($_GET['addons'] == "karts" or $_GET['addons'] == "tracks"  or $_GET['addons'] == "file"  or $_GET['addons'] == "help")
    {
        $addonLoader = new coreAddon($_GET['addons']);
        $addonLoader->loadAll();
        echo '<ul id="list-addons">';
        while($addonLoader->next())
        {
    		if($addonLoader->addonCurrent['available'] == 1)
		    {
		        echo '<li><a class="menu-addons" href="javascript:loadAddon('.$addonLoader->addonCurrent['id'].',\'addon.php?type='.$_GET['addons'].'\')">';
		        if($_GET['addons'] != "tracks") echo '<img class="icon"  src="image.php?type=small&amp;pic='.$dirUpload.'icon/'.$addonLoader->addonCurrent['icon'].'" />';
		        else echo '<img class="icon"  src="'.$dirDownload.'/icon/icon.png" />';
		        echo $addonLoader->addonCurrent['name']."</a></li>";
		    }
	        elseif($_SESSION['range']['manageaddons'] == true||$_SESSION['id']==$addonLoader->addonCurrent['user'])
		    {
		        echo '<li><a class="menu-addons unavailable" href="javascript:loadAddon('.$addonLoader->addonCurrent['id'].',\'addon.php?type='.$_GET['addons'].'\')">';
		        if($_GET['addons'] != "tracks") echo '<img class="icon"  src="image.php?type=small&amp;pic='.$dirUpload.'icon/'.$addonLoader->addonCurrent['icon'].'" />';
		        else echo '<img class="icon"  src="'.$dirDownload.'/icon/icon.png" />';
		        echo $addonLoader->addonCurrent['name']."</a></li>";
            }
	        if($addonLoader->addonCurrent['name'] == $_GET['title']) $js.= 'loadAddon('.$addonLoader->addonCurrent['id'].',\'addon.php?type='.$_GET['addons'].'\')';
        }
        echo "</ul>";
    }

}
?>
