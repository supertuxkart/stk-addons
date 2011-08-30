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

$title = htmlspecialchars(_('SuperTuxKart Add-ons')).' | '.htmlspecialchars(_('Users'));

include('include/top.php');
echo '</head><body>';
include(ROOT.'include/menu.php');

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
        break;
}
$status = ob_get_clean();
$panels->setStatusContent($status);

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
        if($userLoader->userCurrent['user'] == $_GET['user']) $js = 'loadFrame('.$userLoader->userCurrent['id'].',\'users-panel.php\')';
    }
}
$panels->setMenuItems($users);

ob_start();
include(ROOT.'users-panel.php');
$content = ob_get_clean();
$panels->setContent($content);

echo $panels;

include("include/footer.php"); ?>
</body>
</html>
