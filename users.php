<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
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
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: users.php
Version: 1
Licence: GPLv3
Description: people

***************************************************************************/
$security = 'basicPage';
define('ROOT','./');
include('include.php');

$_GET['user'] = (isset($_GET['user'])) ? mysql_real_escape_string($_GET['user']) : NULL;
$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

$title = _('SuperTuxKart Add-ons').' | '._('Users');

include('include/top.php');
echo '</head><body>';
include(ROOT.'include/menu.php');
?>

<div id="left-menu">
    <div id="left-menu_top"></div>
    <div id="left-menu_body">
<?php
$js = "";
loadUsers();
?>
    </div>
    <div id="left-menu_bottom"></div>
</div>

<div id="right-content">
    <div id="right-content_top"></div>
    <div id="right-content_status">
<?php
switch ($action)
{
    default:
        break;
    case 'password':
        $addon = new coreUser;
        $addon->selectByUser($_GET['user']);
        if ($_SESSION['user'] != $_GET['user']
                && !$_SESSION['role']['manage'.$addon->userCurrent['role'].'s'])
        {
            echo '<span class="error">'._('You do not have the necessary permissions to perform this action.').'</span><br />';
            break;
        }
        $addon->setPass();
}
?>
    </div>
    <div id="right-content_body"></div>
    <div id="right-content_bottom"></div>
</div>
<?php
echo '<script type="text/javascript">';
echo $js;
echo '</script>';
include("include/footer.php"); ?>
</body>
</html>
