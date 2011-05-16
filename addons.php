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
define('ROOT','./');
$title = "Supertuxkart Addon Manager";
include("include.php");
include("include/view.php");
include("include/top.php");

// Validate addon-id parameter
$_GET['name'] = (isset($_GET['name'])) ? addon_id_clean($_GET['name']) : NULL;
$_GET['save'] = (isset($_GET['save'])) ? $_GET['save'] : NULL;
$_GET['rev'] = (isset($_GET['rev'])) ? (int)$_GET['rev'] : NULL;

?>
</head>
<body>
<?php 
include("include/menu.php");
?>
<div id="left-menu">
    <div id="left-menu_top"></div>
    <div id="left-menu_body">
<?php
$js = "";

loadAddons();
?>
    </div>
    <div id="left-menu_bottom"></div>
</div>
<div id="right-content">
    <div id="right-content_top"></div>
    <div id="right-content_status">
<?php
// Execute actions
switch ($_GET['save'])
{
    default: break;
    case 'props':
        if (!isset($_POST['description']))
            break;
        if (!isset($_POST['designer']))
            break;
        $edit_addon = new coreAddon($_GET['type']);
        $edit_addon->selectById($_GET['name']);
        if ($edit_addon->setInformation('description',$_POST['description'])
                && $edit_addon->setInformation('designer',$_POST['designer']))
            echo _('Saved properties.').'<br />';
        break;
    case 'rev':
        parseUpload($_FILES['file_addon'],true);
        break;
    case 'status':
        if (!isset($_GET['type']) || !isset($_GET['name']) || !isset($_POST['fields']))
            break;
        if (update_status($_GET['type'],$_GET['name'],$_POST['fields']))
            echo _('Saved status.').'<br />';
        break;
    case 'notes':
        if (!isset($_GET['type']) || !isset($_GET['name']) || !isset($_POST['fields']))
            break;
        if (update_addon_notes($_GET['type'],$_GET['name'],$_POST['fields']))
            echo _('Saved notes.').'<br />';
        break;
    case 'delete':
        $edit_addon = new coreAddon($_GET['type']);
        $edit_addon->selectById($_GET['name']);
        if ($edit_addon->remove())
            echo _('Deleted addon.').'<br />';
        break;
    case 'approve':
    case 'unapprove':
        if (approve_file((int)$_GET['id'],$_GET['save']))
            echo _('File updated.').'<br />';
        break;
    case 'setimage':
        $edit_addon = new coreAddon($_GET['type']);
        $edit_addon->selectById($_GET['name']);
        if ($edit_addon->set_image((int)$_GET['id']))
            echo _('Set image.').'<br />';
        break;
    case 'seticon':
        if ($_GET['type'] != 'karts')
            break;
        $edit_addon = new coreAddon($_GET['type']);
        $edit_addon->selectById($_GET['name']);
        if ($edit_addon->set_image((int)$_GET['id'],'icon'))
            echo _('Set icon.').'<br />';
        break;
    case 'deletefile':
        $edit_addon = new coreAddon($_GET['type']);
        $edit_addon->selectById($_GET['name']);
        if ($edit_addon->delete_file((int)$_GET['id']))
            echo _('Deleted file.').'<br />';
        break;
}
?>
    </div>
    <div id="right-content_body"></div>
    <div id="right-content_bottom"></div>
</div>
</div>
<script type="text/javascript">
<?php echo $js; ?>
</script>
<?php
include("include/footer.php");
function loadAddons()
{
    global $addon, $dirDownload, $dirUpload, $js, $user;
    if($_GET['type'] != "karts" &&
        $_GET['type'] != "tracks"  &&
        $_GET['type'] != "file"  &&
        $_GET['type'] != "blender")
    {
        return;
    }
    $addonLoader = new coreAddon($_GET['type']);
    $addonLoader->loadAll();
    echo '<ul>';
    while($addonLoader->next())
    {
        if ($addonLoader->addonType == 'karts')
        {
            if ($addonLoader->addonCurrent['icon'] != 0)
            {
                $get_image_query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
                    WHERE `id` = '.(int)$addonLoader->addonCurrent['icon'].'
                    AND `approved` = 1
                    LIMIT 1';
                $get_image_handle = sql_query($get_image_query);
                if (mysql_num_rows($get_image_handle) == 1)
                {
                    $icon = mysql_fetch_assoc($get_image_handle);
                    $icon_path = $icon['file_path'];
                }
                else
                {
                    $icon_path = 'image/kart-icon.png';
                }
            }
            else
            {
                $icon_path = 'image/kart-icon.png';
            }
        }
        // Approved?
        if(($addonLoader->addonCurrent['status'] & F_APPROVED) == F_APPROVED)
        {
            echo '<li><a class="menu-item" href="javascript:loadFrame(\''.$addonLoader->addonCurrent['id'].'\',\'addons-panel.php?type='.$_GET['type'].'\')">';
            if($_GET['type'] != "tracks") echo '<img class="icon"  src="image.php?type=small&amp;pic='.$icon_path.'" />';
            else echo '<img class="icon"  src="image/track-icon.png" />';
            echo $addonLoader->addonCurrent['name']."</a></li>";
        }
        elseif($user->logged_in && ($_SESSION['role']['manageaddons'] == true || $_SESSION['userid'] == $addonLoader->addonCurrent['uploader']))
        {
            echo '<li><a class="menu-item unavailable" href="javascript:loadFrame(\''.$addonLoader->addonCurrent['id'].'\',\'addons-panel.php?type='.$_GET['type'].'\')">';
            if($_GET['type'] != "tracks")
                echo '<img class="icon"  src="image.php?type=small&amp;pic='.$icon_path.'" />';
            else echo '<img class="icon"  src="image/track-icon.png" />';
            echo $addonLoader->addonCurrent['name']."</a></li>";
        }
        if($addonLoader->addonCurrent['id'] == $_GET['name']) $js.= 'loadFrame(\''.$addonLoader->addonCurrent['id'].'\',\'addons-panel.php?type='.$_GET['type'].'\')';
    }
    echo "</ul>";
}
?>
