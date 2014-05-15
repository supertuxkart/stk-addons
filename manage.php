<?php

/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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
define('ROOT', './');
require('include.php');
AccessControl::setLevel('manageaddons');

$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;
if (!isset($_GET['view']))
{
    $_GET['view'] = 'overview';
}
$_GET['id'] = $_GET['view'];

$tpl = new StkTemplate("two-pane.tpl");
$tpl->assign("title", htmlspecialchars(_('STK Add-ons') . ' | ' . _('Manage')));
$panel = array(
    'left'   => '',
    'status' => '',
    'right'  => ''
);

// create left links
$menu_items = array(
    array(
        'url'   => 'manage.php?view=overview',
        'label' => htmlspecialchars(_('Overview')),
        'class' => 'manage-list menu-item'
    )
);
if ($_SESSION['role']['managesettings'])
{
    $menu_items[] = array(
        'url'   => 'manage.php?view=general',
        'label' => htmlspecialchars(_('General Settings')),
        'class' => 'manage-list menu-item'
    );
    $menu_items[] = array(
        'url'   => 'manage.php?view=news',
        'label' => htmlspecialchars(_('News Messages')),
        'class' => 'manage-list menu-item'
    );
    $menu_items[] = array(
        'url'   => 'manage.php?view=clients',
        'label' => htmlspecialchars(_('Client Versions')),
        'class' => 'manage-list menu-item'
    );
    $menu_items[] = array(
        'url'   => 'manage.php?view=cache',
        'label' => htmlspecialchars(_('Cache Files')),
        'class' => 'manage-list menu-item'
    );
}
$menu_items[] = array(
    'url'   => 'manage.php?view=files',
    'label' => htmlspecialchars(_('Uploaded Files')),
    'class' => 'manage-list menu-item'
);
$menu_items[] = array(
    'url'   => 'manage.php?view=logs',
    'label' => htmlspecialchars(_('Event Logs')),
    'class' => 'manage-list menu-item'
);

// left panel
$left_tpl = new StkTemplate('url-list-panel.tpl');
$left_tpl->assign('items', $menu_items);
$panel['left'] = (string)$left_tpl;

// status message
$status_content = "";
try
{
    switch ($_GET['action'])
    {
        case 'save_config':
            if (!isset($_POST['xml_frequency']) ||
                !isset($_POST['allowed_addon_exts']) ||
                !isset($_POST['allowed_source_exts'])
            )
            {
                throw new Exception(htmlspecialchars(
                    _('One or more fields has been left blank. Settings were not saved.')
                ));
            }
            if (!is_numeric($_POST['xml_frequency']))
            {
                throw new Exception(htmlspecialchars(_('XML Download Frequency value is invalid.')));
            }

            ConfigManager::setConfig('xml_frequency', (int)$_POST['xml_frequency']);
            ConfigManager::setConfig('allowed_addon_exts', $_POST['allowed_addon_exts']);
            ConfigManager::setConfig('allowed_source_exts', $_POST['allowed_source_exts']);
            ConfigManager::setConfig('admin_email', $_POST['admin_email']);
            ConfigManager::setConfig('list_email', $_POST['list_email']);
            ConfigManager::setConfig('list_invisible', (int)$_POST['list_invisible']);
            ConfigManager::setConfig('blog_feed', $_POST['blog_feed']);
            ConfigManager::setConfig('max_image_dimension', (int)$_POST['max_image_dimension']);
            ConfigManager::setConfig('apache_rewrites', $_POST['apache_rewrites']);

            $status_content = htmlspecialchars(_('Saved settings.')) . '<br />';
            break;
        case 'new_news':
            if (empty($_POST['message']) || !isset($_POST['condition']))
            {
                throw new Exception('Missing response for message and condition fields.');
            }

            $web_display = (empty($_POST['web_display'])) ? false : (($_POST['web_display'] == 'on') ? true : false);
            $important = (empty($_POST['important'])) ? false : (($_POST['important'] == 'on') ? true : false);
            $condition = $_POST['condition'];

            // Make sure no invalid version number sneaks in
            if (stristr($condition, 'stkversion') !== false)
            {
                $cond_check = explode(' ', $condition);
                if (count($cond_check) !== 3)
                {
                    throw new Exception('Version comparison should contain three tokens, only found: ' .
                        count($cond_check)
                    );
                }
                // Validate version string
                Validate::versionString($cond_check[2]);
            }

            News::create($_POST['message'], $condition, $important, $web_display);
            $status_content = htmlspecialchars(_('Created message.')) . '<br />';
            break;
        default:
            break;
    }
}
catch(Exception $e)
{
    $status_content = '<span class="error">' . $e->getMessage() . '</span><br />';
}
$panel["status"] = $status_content;

// right panel
ob_start();
include(ROOT . 'manage-panel.php');
$panel['right'] = ob_get_clean();

// output the view
$tpl->assign('panel', $panel);
echo $tpl;
