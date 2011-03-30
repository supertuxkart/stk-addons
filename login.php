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

File: login.php
Version: 1
Licence: GPLv3
Description: login page

***************************************************************************/
define('ROOT','./');
$security = "";
// Include basic files
include(ROOT.'include.php');

// define possibly undefined variables
$_POST['user'] = (isset($_POST['user'])) ? $_POST['user'] : NULL;
$_POST['pass'] = (isset($_POST['pass'])) ? $_POST['pass'] : NULL;
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : NULL;

if ($_GET['action'] == 'logout')
{
    $user->logout();
    include(ROOT.'include/menu.php');
    if ($user->logged_in === true)
    {
        include(ROOT.'include/top.php');
        echo '</head><body><div id="content">';
        echo '<span class="error">'._('Failed to logout.').'</span><br />';
    }
    else
    {
        include(ROOT.'include/top.php');
        echo '<meta http-equiv="refresh" content="3;URL=index.php"></head><body>';
        echo '<div id="content">';
        echo _('You have been logged out.').'<br />';
        echo _('Click <a href="index.php">here</a> if you do not automatically redirect.').'<br />';
    }
    include(ROOT.'include/footer.php');
    exit;
}

if ($user->logged_in === true)
{
    include(ROOT.'include/top.php');
    echo '<meta http-equiv="refresh" content="3;URL=index.php"></head><body>';
    include(ROOT.'include/menu.php');
    echo '<div id="content">';
    echo _('You are already logged in.').' ';
    echo _('Click <a href="index.php">here</a> if you do not automatically redirect.').'<br />';
    include('include/footer.php');
    exit;
}

if ($_GET['action'] == 'submit')
{
    // Variable validation is done by the function below
    if ($user->login($_POST['user'],$_POST['pass']))
    {
        include(ROOT.'include/top.php');
        echo '<meta http-equiv="refresh" content="3;URL=index.php">';
    }
    else
    {
        include(ROOT.'include/top.php');
    }
    echo '</head><body>';
    include(ROOT.'include/menu.php');
    echo '<div id="content">';
    if ($user->logged_in === true)
    {
        echo _('Welcome').' '.$_SESSION['real_name'].'. ';
        echo _('Click <a href="index.php">here</a> if you do not automatically redirect.').'<br />';
        include('include/footer.php');
        exit;
    }
    echo '<span class="error">'._('Authentication failed.').'</span><br />';
}
else
{
    include(ROOT.'include/top.php');
    echo '</head><body>';
    include(ROOT.'include/menu.php');
    echo '<div id="content">';
}
?>
<form action="login.php?action=submit" method="POST">
    Username:<br />
    <input type="text" name="user" /><br />
    Password:<br />
    <input type="password" name="pass" /><br />
    <input type="submit" value="<?php echo _('Log In'); ?>" />
</form>
<a href="createAccount.php"><?php echo _('Create an account.'); ?></a>
</div>
<?php include("include/footer.php"); ?>
