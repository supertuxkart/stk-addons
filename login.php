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
$title = htmlspecialchars(_('STK Add-ons').' | '._('Login'));

// define possibly undefined variables
$_POST['user'] = (isset($_POST['user'])) ? $_POST['user'] : NULL;
$_POST['pass'] = (isset($_POST['pass'])) ? $_POST['pass'] : NULL;
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : NULL;

if ($_GET['action'] == 'logout')
{
    User::logout();
    include(ROOT.'include/menu.php');
    if (User::$logged_in === true)
    {
        include(ROOT.'include/top.php');
        echo '</head><body><div id="content">';
        echo '<span class="error">'.htmlspecialchars(_('Failed to logout.')).'</span><br />';
    }
    else
    {
        include(ROOT.'include/top.php');
        echo '<meta http-equiv="refresh" content="3;URL=index.php"></head><body>';
        echo '<div id="content">';
        echo htmlspecialchars(_('You have been logged out.')).'<br />';
        printf(htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),'<a href="index.php">','</a>').'<br />';
    }
    include(ROOT.'include/footer.php');
    exit;
}

if (User::$logged_in === true)
{
    include(ROOT.'include/top.php');
    echo '<meta http-equiv="refresh" content="3;URL=index.php"></head><body>';
    include(ROOT.'include/menu.php');
    echo '<div id="content">';
    echo htmlspecialchars(_('You are already logged in.')).' ';
    printf(htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),'<a href="index.php">','</a>').'<br />';
    include('include/footer.php');
    exit;
}

if ($_GET['action'] == 'submit')
{
    $error = false;
    try
    {
        // Variable validation is done by the function below
        User::login($_POST['user'],$_POST['pass']);
    }
    catch (UserException $e)
    {
        $error = $e->getMessage();
    }
    include(ROOT.'include/top.php');
    if ($error === false)
        echo '<meta http-equiv="refresh" content="3;URL=index.php">';
    echo '</head><body>';
    include(ROOT.'include/menu.php');
    echo '<div id="content">';
    if (User::$logged_in === true)
    {
        printf(htmlspecialchars(_('Welcome, %s!')).'<br />',$_SESSION['real_name']);
        printf(htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),'<a href="index.php">','</a>').'<br />';
        include('include/footer.php');
        exit;
    }
    else
    {
        echo '<span class="error">'.$error.'</span><br />';
    }
    
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
    <?php echo htmlspecialchars(_('Username:')); ?><br />
    <input type="text" name="user" /><br />
    <?php echo htmlspecialchars(_('Password:')); ?><br />
    <input type="password" name="pass" /><br />
    <input type="submit" value="<?php echo htmlspecialchars(_('Log In')); ?>" />
</form>
<a href="register.php"><?php echo htmlspecialchars(_('Create an account.')); ?></a><br />
<a href="password-reset.php"><?php echo htmlspecialchars(_('Forgot password?')); ?></a>
</div>
<?php include("include/footer.php"); ?>
