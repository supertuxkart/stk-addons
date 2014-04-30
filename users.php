<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2012-2014 Stephen Just <stephenjust@users.sf.net>
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

define('ROOT', './');
require_once(ROOT . 'config.php');
require_once(INCLUDE_DIR . 'AccessControl.class.php');
require_once(INCLUDE_DIR . 'coreUser.php');
require_once(INCLUDE_DIR . 'StkTemplate.class.php');
AccessControl::setLevel('basicPage');

$_GET['user'] = (isset($_GET['user'])) ? $_GET['user'] : $_SESSION['user'];
$action = (isset($_GET['action'])) ? $_GET['action'] : null;

$tpl = new StkTemplate('two-pane.tpl');
$tpl->assign('title', htmlspecialchars(_('SuperTuxKart Add-ons')) . ' | ' . htmlspecialchars(_('Users')));
$panel = array(
        'left'   => '',
        'status' => '',
        'right'  => ''
);

// handle user actions
ob_start();
switch ($action) {
    case 'password':
        $user = new coreUser();
        $user->selectByUser($_GET['user']);
        if ($_SESSION['user'] !== $_GET['user']
                && !$_SESSION['role']['manage' . $user->userCurrent['role'] . 's']
        ) {
            echo '<span class="error">' . htmlspecialchars(
                            _('You do not have the necessary permissions to perform this action.')
                    ) . '</span><br />';
            break;
        }
        try {
            $user->setPass($_POST['oldPass'], $_POST['newPass'], $_POST['newPass2']);
            echo htmlspecialchars(_('Your password has been changed successfully.'));
        } catch(UserException $e) {
            echo '<span class="error">' . $e->getMessage() . '</span><br />';
        }
        break;
    case 'config':
        $user = new coreUser();
        $user->selectByUser($_GET['user']);
        if ($_SESSION['user'] !== $_GET['user']
                && !$_SESSION['role']['manage' . $user->userCurrent['role'] . 's']
        ) {
            echo '<span class="error">' . htmlspecialchars(
                            _('You do not have the necessary permissions to perform this action.')
                    ) . '</span><br />';
            break;
        }
        $available = (isset($_POST['available'])) ? $_POST['available'] : null;
        $range = (isset($_POST['range'])) ? $_POST['range'] : null;

        if ($user->setConfig($available, $range)) {
            echo 'Saved configuration.';
        } else {
            echo 'An error occured';
        }
        break;
    default:
        break;
}
$status = ob_get_clean();
$panel['status'] = $status;

// get all users from the database
$templateUsers = array();
$users = new coreUser();
$users = $users->getAll();
$templateUsers[] = array(
        'url'   => "users.php?user={$_SESSION['user']}",
        'label' => '<img class="icon"  src="image/user.png" />' . htmlspecialchars(_('Me')),
        'class' => 'user-list menu-item'
);
foreach ($users as $user) {
    // Make sure that the user is active, or the viewer has permission to
    // manage this type of user
    if ($_SESSION['role']['manage' . $user['role'] . 's']
            || $user['active'] == 1
    ) {
        $class = 'user-list menu-item';
        if ($user["active"] == 0) {
            $class .= ' unavailable';
        }
        $templateUsers[] = array(
                'url'   => "users.php?user={$user['user']}",
                'label' => '<img class="icon"  src="image/user.png" />' . htmlspecialchars($user['user']),
                'class' => $class
        );
    }
}
// left panel
$left_tpl = new StkTemplate('url-list-panel.tpl');
$left_tpl->assign('items', $templateUsers);
$panel['left'] = (string)$left_tpl;

// right panel
if (isset($_GET['user'])) {
    $_GET['id'] = $_GET['user'];
}
ob_start();
include(ROOT . 'users-panel.php');
$panel['right'] = ob_get_clean();

// output the view
$tpl->assign('panel', $panel);
echo $tpl;
