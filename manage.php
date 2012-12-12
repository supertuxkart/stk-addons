<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
 *
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

define('ROOT','./');
require('include.php');
AccessControl::setLevel('manageaddons');

$title = htmlspecialchars(_('STK Add-ons').' | '._('Manage'));

require('include/top.php');
echo '</head><body>';
require('include/menu.php');
if (!isset($_GET['action'])) $_GET['action'] = NULL;

$panels = new PanelInterface();

$menu_items = 
        array(
            array(
                'url'   => 'manage.php?view=overview',
                'label' => htmlspecialchars(_('Overview')),
                'class' => 'manage-list menu-item'
            )
	);
if ($_SESSION['role']['managesettings']) {
    $menu_items[] = array(
                'url'   => 'manage.php?view=general',
                'label' => htmlspecialchars(_('General Settings')),
                'class' => 'manage-list menu-item'
            );
    $menu_items[] = 
            array(
                'url'   => 'manage.php?view=news',
                'label' => htmlspecialchars(_('News Messages')),
                'class' => 'manage-list menu-item'
            );
    $menu_items[] =
            array(
                'url'   => 'manage.php?view=clients',
                'label' => htmlspecialchars(_('Client Versions')),
                'class' => 'manage-list menu-item'
            );
    $menu_items[] =
            array(
                'url'   => 'manage.php?view=cache',
                'label' => htmlspecialchars(_('Cache Files')),
                'class' => 'manage-list menu-item'
            );
}
$menu_items[] = 
            array(
                'url'   => 'manage.php?view=files',
                'label' => htmlspecialchars(_('Uploaded Files')),
                'class' => 'manage-list menu-item'
            );
$menu_items[] =
            array(
                'url'   => 'manage.php?view=logs',
                'label' => htmlspecialchars(_('Event Logs')),
                'class' => 'manage-list menu-item'
            );
$panels->setMenuItems($menu_items);

ob_start();
try {
    switch ($_GET['action']) {
	default:
	    break;
	case 'save_config':
	    if (!isset($_POST['xml_frequency']) ||
		    !isset($_POST['allowed_addon_exts']) ||
		    !isset($_POST['allowed_source_exts'])) {
		throw new Exception(htmlspecialchars(_('One or more fields has been left blank. Settings were not saved.')));
	    }
	    if (!is_numeric($_POST['xml_frequency'])) {
		throw new Exception(htmlspecialchars(_('XML Download Frequency value is invalid.')));
	    }

	    ConfigManager::set_config('xml_frequency',	    (int)$_POST['xml_frequency']);
	    ConfigManager::set_config('allowed_addon_exts', $_POST['allowed_addon_exts']);
	    ConfigManager::set_config('allowed_source_exts',$_POST['allowed_source_exts']);
	    ConfigManager::set_config('admin_email',	    $_POST['admin_email']);
	    ConfigManager::set_config('list_email',	    $_POST['list_email']);
	    ConfigManager::set_config('list_invisible',	    (int)$_POST['list_invisible']);
	    ConfigManager::set_config('blog_feed',	    $_POST['blog_feed']);
	    ConfigManager::set_config('max_image_dimension',(int)$_POST['max_image_dimension']);
	    ConfigManager::set_config('apache_rewrites',    $_POST['apache_rewrites']);

	    echo htmlspecialchars(_('Saved settings.')).'<br />';
	    break;
	case 'new_news':
	    if (!isset($_POST['message']) || !isset($_POST['condition']))
		throw new Exception('Missing response for message and condition fields.');
	    if (strlen($_POST['message']) == 0)
		throw new Exception('Cannot create message with no content.');

	    if (!isset($_POST['web_display'])) $_POST['web_display'] = 0;
	    elseif ($_POST['web_display'] == 'on') $_POST['web_display'] = 1;
	    else $_POST['web_display'] = 1;

	    $new_message = mysql_real_escape_string($_POST['message']);
	    $condition = mysql_real_escape_string($_POST['condition']);
	    
	    // Make sure no invalid version number sneaks in
	    if (stristr($condition, 'stkversion') !== false) {
		$cond_check = explode(' ', $condition);
		if (count($cond_check) !== 3)
		    throw new Exception('Version comparison should contain three tokens, only found: '.count($cond_check));
		// Validate version string
		Validate::versionString($cond_check[2]);
	    }
	    
	    $web_display = (int)$_POST['web_display'];
	    $reqSql = 'INSERT INTO `'.DB_PREFIX."news`
		(`author_id`,`content`,`condition`,`web_display`,`active`)
		VALUES
		({$_SESSION['userid']},'$new_message','$condition',$web_display,1)";
	    $handle = sql_query($reqSql);
	    if ($handle) {
		echo htmlspecialchars(_('Created message.')).'<br />';
	    } else {
		throw new Exception(htmlspecialchars(_('Failed to create message.')));
	    }
	    // Regenerate xml
	    writeNewsXML();
	    break;
    }
}
catch (Exception $e) {
    echo '<span class="error">'.$e->getMessage().'</span><br />';
}
$status_content = ob_get_clean();
$panels->setStatusContent($status_content);

if (!isset($_GET['view']))
    $_GET['view'] = 'overview';
$_POST['id'] = $_GET['view'];

ob_start();
include(ROOT.'manage-panel.php');
$content = ob_get_clean();
$panels->setContent($content);

echo $panels;

require('include/footer.php');
?>
