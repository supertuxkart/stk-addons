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

define('ROOT','./');
require_once(ROOT.'config.php');
require_once(INCLUDE_DIR.'AccessControl.class.php');
require_once(INCLUDE_DIR.'PanelInterface.class.php');
require_once(INCLUDE_DIR.'coreUser.php');
require_once(INCLUDE_DIR.'StkTemplate.class.php');
AccessControl::setLevel('basicPage');

$_GET['user'] = (isset($_GET['user'])) ? $_GET['user'] : $_SESSION['user'];
$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;

$tpl = new StkTemplate('two-pane.tpl');
$tpl->assign('title', htmlspecialchars(_('SuperTuxKart Add-ons')).' | '.htmlspecialchars(_('Users')));
$panel = array(
    'left' => '',
    'status' => '',
    'right' => ''
);

$panels = new PanelInterface();
$js = NULL;

ob_start();
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
            echo '<span class="error">'.htmlspecialchars(_('You do not have the necessary permissions to perform this action.')).'</span><br />';
            break;
        }
        try {
            $addon->setPass();
            echo htmlspecialchars(_('Your password has been changed successfully.'));
        }
        catch (UserException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
        break;
    case 'config':
        $addon = new coreUser;
        $addon->selectByUser($_GET['user']);
        if ($_SESSION['user'] != $_GET['user']
                && !$_SESSION['role']['manage'.$addon->userCurrent['role'].'s'])
        {
            echo '<span class="error">'.htmlspecialchars(_('You do not have the necessary permissions to perform this action.')).'</span><br />';
            break;
        }
        $addon->setConfig();
        echo 'Saved configuration.';
        break;
}
$status = ob_get_clean();
$panel['status'] = $status;

$users = array();
$userLoader = new coreUser();
$userLoader->loadAll();
$users[] = array(
    'url'   => "users.php?user={$_SESSION['user']}",
    'label' => '<img class="icon"  src="image/user.png" />'.htmlspecialchars(_('Me')),
    'class' => 'user-list menu-item'
);
while($userLoader->next())
{
    // Make sure that the user is active, or the viewer has permission to
    // manage this type of user
    if ($_SESSION['role']['manage'.$userLoader->userCurrent['role'].'s']
            || $userLoader->userCurrent['active'] == 1)
    {
        $class = 'user-list menu-item';
        if ($userLoader->userCurrent['active'] == 0) $class .= ' unavailable';
        $users[] = array(
            'url'   => "users.php?user={$userLoader->userCurrent['user']}",
            'label' => '<img class="icon"  src="image/user.png" />'.htmlspecialchars($userLoader->userCurrent['user']),
            'class' => $class
        );
    }
}
if (isset($_GET['user'])) {
    $_GET['id'] = $_GET['user'];
    ob_start();
    include(ROOT.'users-panel.php');
    $content = ob_get_clean();
    $panel['right'] = $content;
}
$left_tpl = new StkTemplate('url-list-panel.tpl');
$left_tpl->assign('items', $users);
$panel['left'] = (string) $left_tpl;

ob_start();
include(ROOT.'users-panel.php');
$content = ob_get_clean();
$panel['right'] = $content;

$tpl->assign('panel', $panel);
echo $tpl;
