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

File: manageAccount.php
Version: 1
Licence: GPLv3
Description: people

***************************************************************************/
$security = "basicPage";
include("include/security.php");

$title = "SuperTuxKart Add-ons | Users";
include("include/top.php");
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
        loadUsers();
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
function loadUsers()
{
    global $style, $js;
    $addonLoader = new coreUser('users');
    $addonLoader->loadAll();
    echo '<ul id="list-addons">';
    ?>
    <li>
        <a class="menu-addons" href="javascript:loadAddon(<?php echo $_SESSION['id']; ?>,'user.php')">
            <img class="icon"  src="image/<?php echo $style; ?>/user.png" />
            Me
        </a>
    </li>
    <?php
    while($addonLoader->next())
    {
        echo '<li><a class="menu-addons';
        if($addonLoader->addonCurrent['available'] == 0) echo ' unavailable';
        echo '" href="javascript:loadAddon('.$addonLoader->addonCurrent['id'].',\'user.php\')">';
        echo '<img class="icon"  src="image/'.$style.'/user.png" />';
        echo $addonLoader->addonCurrent['login']."</a></li>";
        if($addonLoader->addonCurrent['login'] == $_GET['title']) $js.= 'loadAddon('.$addonLoader->addonCurrent['id'].',\'user.php\')';
    }
    echo "</ul>";

}
?>
