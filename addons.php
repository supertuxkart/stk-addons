<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2011-2014 Stephen Just <stephenjust@gmail.com>
 *           2014      Daniel Butum <danibutum at gmail dot com>
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */

require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
$_GET['type'] = (isset($_GET['type'])) ? $_GET['type'] : null;
switch ($_GET['type'])
{
    default:
        $type_label = _h('Unknown Type');
        header("HTTP/1.0 404 Not Found");
        break;
    case 'tracks':
        $type_label = _h('Tracks');
        break;
    case 'karts':
        $type_label = _h('Karts');
        break;
    case 'arenas':
        $type_label = _h('Arenas');
        break;
}
$title = $type_label . ' - ' . _h('SuperTuxKart Add-ons');

// Validate addon-id parameter
$_GET['name'] = (isset($_GET['name'])) ? Addon::cleanId($_GET['name']) : null;
$_GET['save'] = (isset($_GET['save'])) ? $_GET['save'] : null;
$_GET['rev'] = (isset($_GET['rev'])) ? (int)$_GET['rev'] : null;

// Throw a 404 if the requested addon wasn't found
if ($_GET['name'] != null && !Addon::exists($_GET['name']))
{
    header("HTTP/1.0 404 Not Found");
}

$addonName = Addon::getName($_GET['name']);
if ($addonName !== false)
{
    $title = $addonName . ' - ' . $title;
}

include("include/top.php");

?>
    <script type="text/javascript" src="<?php echo SITE_ROOT; ?>js/rating.js"></script>
    </head>
    <body>
<?php
include("include/menu.php");

$panels = new PanelInterface();

if (!Addon::isAllowedType($_GET['type']))
{
    echo '<span class="error">' . _h('Invalid addon type.') . '</span><br />';
    exit;
}

$js = "";

$status = "";
// Execute actions
try
{
    switch ($_GET['save'])
    {
        default:
            break;
        case 'props':
            if (!isset($_POST['description']))
            {
                break;
            }
            if (!isset($_POST['designer']))
            {
                break;
            }

            $edit_addon = new Addon(Addon::cleanId($_GET['name']));
            $edit_addon->setDescription($_POST['description']);
            $edit_addon->setDesigner($_POST['designer']);
            $status = _h('Saved properties.') . '<br>';
            break;
        case 'rev':
            parseUpload($_FILES['file_addon'], true);
            break;
        case 'status':
            if (!isset($_GET['name']) || !isset($_POST['fields']))
            {
                break;
            }
            $addon = new Addon($_GET['name']);
            $addon->setStatus($_POST['fields']);
            $status = _h('Saved status.') . '<br>';
            break;
        case 'notes':
            if (!isset($_GET['name']) || !isset($_POST['fields']))
            {
                break;
            }
            $mAddon = new Addon($_GET['name']);
            $mAddon->setNotes($_POST['fields']);
            $status = _h('Saved notes.') . '<br>';
            break;
        case 'delete':
            $delAddon = new Addon($_GET['name']);
            $delAddon->delete();
            unset($delAddon);
            $status = _h('Deleted addon.') . '<br>';
            break;
        case 'del_rev':
            $delRev = new Addon($_GET['name']);
            $delRev->deleteRevision($_GET['rev']);
            unset($delRev);
            $status = _h('Deleted add-on revision.') . '<br>';
            break;
        case 'approve':
        case 'unapprove':
            $approve = ($_GET['save'] == 'approve') ? true : false;
            File::approve((int)$_GET['id'], $approve);
            $status = _h('File updated.') . '<br>';
            break;
        case 'setimage':
            $edit_addon = new Addon(Addon::cleanId($_GET['name']));
            $edit_addon->setImage((int)$_GET['id']);
            $status = _h('Set image.') . '<br>';
            break;
        case 'seticon':
            if ($_GET['type'] !== 'karts')
            {
                break;
            }
            $edit_addon = new Addon(Addon::cleanId($_GET['name']));
            $edit_addon->setImage((int)$_GET['id'], 'icon');
            $status = _h('Set icon.') . '<br>';
            break;
        case 'deletefile':
            $mAddon = new Addon($_GET['name']);
            $mAddon->deleteFile((int)$_GET['id']);
            $status =  _h('Deleted file.') . '<br>';
            break;
        case 'include':
            $mAddon = new Addon($_GET['name']);
            $mAddon->setIncludeVersions($_POST['incl_start'], $_POST['incl_end']);
            $status = _h('Marked game versions in which this add-on is included.');
            break;
    }
}
catch(Exception $e)
{
    $status = '<span class="error">' . $e->getMessage() . '</span><br />';
}
$panels->setStatusContent($status);

$addons = array();
$addons_list = Addon::getAddonList($_GET['type'], true);
foreach ($addons_list AS $ad)
{
    try
    {
        $adc = new Addon($ad);

        // Get link icon
        if ($adc->getType() === 'karts')
        {
            // Make sure an icon file is set for kart
            if ($adc->getImage(true) != 0)
            {
                $im = Cache::getImage($adc->getImage(true), array('size' => 'small'));
                if ($im['exists'] && $im['approved'])
                {
                    $icon = $im['url'];
                }
                else
                {
                    $icon = IMG_LOCATION . 'kart-icon.png';
                }
            }
            else
            {
                $icon = IMG_LOCATION . 'kart-icon.png';
            }
        }
        else
        {
            $icon = IMG_LOCATION . 'track-icon.png';
        }

        // Approved?
        if ($adc->hasApprovedRevision())
        {
            $class = 'addon-list menu-item';
        }
        elseif (User::isLoggedIn() &&
            (User::hasPermission(AccessControl::PERM_EDIT_ADDONS) || User::getId() == $adc->getUploader())
        )
        {
            $class = 'addon-list menu-item unavailable';
        }
        else
        {
            continue;
        }
        $icon_html = '<img class="icon" src="' . $icon . '" height="25" width="25" />';
        if (($adc->getStatus() & F_FEATURED) == F_FEATURED)
        {
            $icon_html = '<div class="icon-featured"></div>' . $icon_html;
        }
        $addons[] = array(
            'class' => $class,
            'url'   => "addons.php?type={$_GET['type']}&amp;name={$adc->getId()}",
            'label' => '<div class="icon">' . $icon_html . '</div>' . htmlspecialchars($adc->getName($adc->getId())),
            'disp'  => File::rewrite("addons.php?type={$_GET['type']}&amp;name={$adc->getId()}")
        );
    }
    catch(AddonException $e)
    {
        echo '<span class="error">' . $e->getMessage() . '</span><br />';
    }
}
$panels->setMenuItems($addons);

if (isset($_GET['name']))
{
    $_GET['id'] = $_GET['name'];
    ob_start();
    include(ROOT_PATH . 'addons-panel.php');
    $content = ob_get_clean();
    $panels->setContent($content);
}

echo $panels;
include("include/footer.php");
