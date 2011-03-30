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

File: index.php
Version: 1
Licence: GPLv3
Description: index page

***************************************************************************/
define('ROOT','./');
$security ="";
include('include.php');
include('include/top.php');

// define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : NULL;

// Login form
$login_form =
    '<form id="form" action="createAccount.php?action=submit" method="POST">
    '._('Username:').' <br />
    <input type="text" name="user" /><br />
    '._('Password:').' <br />
    <input type="password" id="pass1" name="pass1" /><br />
    '._('Password (confirm):').' <br />
    <input type="password" id="pass2" name="pass2" /><br />
    '._('Name:').' <br />
    <input type="text" id="name" name="name" /><br />
    '._('Email Address:').' <br />
    <input type="text" name="mail" /><br /><br />
    <input type="submit" value="Submit" />
    </form>';

?>

</head>
<body>
<?php include(ROOT.'include/menu.php');
echo '
<div id="content">';
//exit();
if ($_GET['action'] == 'submit' && $_POST['pass1'] != $_POST['pass2'])
{
    echo '<span class="error>'._('Your passwords do not match.').'</span><br /><br />';
    echo $login_form;
}
elseif($_GET['action'] == "submit" && $_POST['pass1'] == $_POST['pass2'])
{
    $user = mysql_real_escape_string($_POST['user']);
    $existSql= mysql_query("SELECT * FROM `".DB_PREFIX."users` WHERE `user` = '$user'");
    $exist = mysql_num_rows($existSql);
    if($exist === 0 && $user != null)
    {
        $crypt = cryptUrl(12);
        $createSql = mysql_query('
            INSERT INTO `'.DB_PREFIX."users`
                (`user`, `pass`, `name`, `role`, `email`,
                `active`, `verify`, `reg_date`)
            VALUES
                ('".mysql_real_escape_string($_POST['user'])."',
                '".hash('sha256',$_POST['pass1'])."',
                '".mysql_real_escape_string($_POST['name'])."',
                'basicUser','".mysql_real_escape_string($_POST['mail'])."',
                '0','$crypt','".date('Y-m-d')."')");
        if ($createSql)
        {
            include("include/mail.php");
            sendMail(mysql_real_escape_string($_POST['mail']), "newAccount", array($crypt, $_SERVER["PHP_SELF"], $user));
            echo _("Account creation was successful. Please activate your account using the link emailed to you.");
        }
        else
        {
            echo '<span class="error">'._('An error occurred while creating your account.').'</span><br />';
        }
    }
    else
    {
        echo '<span class="error">'._('Your username has already been used.')."</span><br /><br />";
        echo $login_form;
    }
}
elseif($_GET['action'] == "valid")
{
    $reqSql = "UPDATE `".DB_PREFIX."users`
        SET `active` = '1', `verify` = ''
        WHERE `verify` ='".mysql_real_escape_string($_GET['num'])."'";
    $handle = sql_query($reqSql);
    if (!$handle)
        die (mysql_error());
    echo _('Your account has been activated.').'<br />';
}
else
{
    echo $login_form;
}
    echo '
</div>';
     include("include/footer.php"); ?>
</body>
</html>
<?php
function cryptUrl($nbr)
{
    $str = "";
    $chaine = "abcdefghijklmnpqrstuvwxy";
    srand((double)microtime()*1000000);
    for($i=0; $i<$nbr; $i++)
    {
        $str .= $chaine[rand()%strlen($chaine)];
    }
    return $str;
}

$str = cryptUrl(12);
?>
