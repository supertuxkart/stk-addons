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
include("include.php");
$_GET['type'] = (isset($_GET['type'])) ? $_GET['type'] : NULL;
switch ($_GET['type']) {
    default:
        $type_label = htmlspecialchars(_('Unknown Type'));
        header("HTTP/1.0 404 Not Found");
        break;
    case 'tracks':
        $type_label = htmlspecialchars(_('Tracks'));
        break;
    case 'karts':
        $type_label = htmlspecialchars(_('Karts'));
        break;
    case 'arenas':
        $type_label = htmlspecialchars(_('Arenas'));
        break;
}
$title = htmlspecialchars(_('STK Add-ons').' | ').$type_label;

// Validate addon-id parameter
$_GET['name'] = (isset($_GET['name'])) ? Addon::cleanId($_GET['name']) : NULL;
$_GET['save'] = (isset($_GET['save'])) ? $_GET['save'] : NULL;
$_GET['rev'] = (isset($_GET['rev'])) ? (int)$_GET['rev'] : NULL;

$addonName = Addon::getName($_GET['name']);
if ($addonName !== false)
    $title .= ' | '.$addonName;

include("include/view.php");
include("include/top.php");

?>
</head>
<body>
<?php 
include("include/menu.php");

$panels = new PanelInterface();

if (!Addon::isAllowedType($_GET['type'])) {
    echo '<span class="error">'.htmlspecialchars(_('Invalid addon type.')).'</span><br />';
    exit;
}

$js = "";

ob_start();
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
            echo htmlspecialchars(_('Saved properties.')).'<br />';
        break;
    case 'rev':
        try {
            parseUpload($_FILES['file_addon'],true);
        }
        catch (UploadException $e)
        {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
        break;
    case 'status':
        if (!isset($_GET['name']) || !isset($_POST['fields']))
            break;
        try {
            $addon = new Addon($_GET['name']);
            $addon->setStatus($_POST['fields']);
            echo htmlspecialchars(_('Saved status.')).'<br />';
        }
        catch (AddonException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
        break;
    case 'notes':
        if (!isset($_GET['name']) || !isset($_POST['fields']))
            break;
        try {
            $mAddon = new Addon($_GET['name']);
            $mAddon->setNotes($_POST['fields']);
            echo htmlspecialchars(_('Saved notes.')).'<br />';
        }
        catch (AddonException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
        break;
    case 'delete':
        try {
            $delAddon = new Addon($_GET['name']);
            $delAddon->delete();
            unset($delAddon);
            echo htmlspecialchars(_('Deleted addon.')).'<br />';
        }
        catch (AddonException $e)
        {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
        break;
    case 'approve':
    case 'unapprove':
        try {
            $approve = ($_GET['save'] == 'approve') ? true : false;
            File::approve((int)$_GET['id'], $approve);
            echo htmlspecialchars(_('File updated.')).'<br />';
        }
        catch (FileException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
        break;
    case 'setimage':
        $edit_addon = new coreAddon($_GET['type']);
        $edit_addon->selectById($_GET['name']);
        if ($edit_addon->set_image((int)$_GET['id']))
            echo htmlspecialchars(_('Set image.')).'<br />';
        break;
    case 'seticon':
        if ($_GET['type'] != 'karts')
            break;
        $edit_addon = new coreAddon($_GET['type']);
        $edit_addon->selectById($_GET['name']);
        if ($edit_addon->set_image((int)$_GET['id'],'icon'))
            echo htmlspecialchars(_('Set icon.')).'<br />';
        break;
    case 'deletefile':
        try {
            $mAddon = new Addon($_GET['name']);
            $mAddon->deleteFile((int)$_GET['id']);
            echo htmlspecialchars(_('Deleted file.')).'<br />';
        }
        catch (AddonException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
        break;
}
$status = ob_get_clean();
$panels->setStatusContent($status);

$addons = array();
$addonLoader = new coreAddon($_GET['type']);
$addonLoader->loadAll();
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
    if ($_GET['type'] == 'karts')
        $icon = 'image.php?type=small&amp;pic='.$icon_path;
    else
        $icon = 'image/track-icon.png';

    // Approved?
    if(($addonLoader->addonCurrent['status'] & F_APPROVED) == F_APPROVED)
        $class = 'addon-list menu-item';
    elseif(User::$logged_in && ($_SESSION['role']['manageaddons'] == true || $_SESSION['userid'] == $addonLoader->addonCurrent['uploader']))
        $class = 'addon-list menu-item unavailable';
    else
        continue;
    $addons[] = array(
        'class' => $class,
        'url'   => "addons.php?type={$_GET['type']}&amp;name={$addonLoader->addonCurrent['id']}",
        'label' => '<img class="icon"  src="'.$icon.'" />'.htmlspecialchars($addonLoader->addonCurrent['name'])
    );
}
$panels->setMenuItems($addons);

if (isset($_GET['name'])) {
    $_GET['id'] = $_GET['name'];
    ob_start();
    include(ROOT.'addons-panel.php');
    $content = ob_get_clean();
    $panels->setContent($content);
}

echo $panels;
include("include/footer.php");
?>
