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
    '._('Username:').' ('._('Must be at least 4 characters long.').')<br />
    <input type="text" name="user" /><br />
    '._('Password:').' ('._('Must be at least 6 characters long.').')<br />
    <input type="password" id="pass1" name="pass1" /><br />
    '._('Password (confirm):').' <br />
    <input type="password" id="pass2" name="pass2" /><br />
    '._('Name:').' <br />
    <input type="text" id="name" name="name" /><br />
    '._('Email Address:').' <br />
    <input type="text" name="mail" /><br /><br />
    '._('Terms:').'<br />
    <textarea rows="10" cols="80">'._('
=== STK Addons Terms and Conditions ===

You must agree to these terms in order to upload content to the STK Addons site.

The STK Addons service is designed to be a repository exclusively for Super Tux
Kart addon content. As such, all uploaded content must be intended for this
purpose. When you upload your content, it will be available publicly on the
internet, and may be available in-game for download.

Because Super Tux Kart aims to comply with the Debian Free Software Guidelines,
and because the host for this web service requires it, you may not upload
content which is locked down with a restrictive license. Licenses such as
CC-NC-SA 3.0, or other DFSG-compliant licenses are required. As well, all
content taken from third-party sources must be attributed properly, and must
also be available under an open license. Licenses and attribution should be
included in a "license.txt" file in each uploaded archive. Uploads without
proper licenses or attribution may be deleted without warning.

As well, even with valid licenses and attribution, content may not contain any
of the following:
    1. Profanity
    2. Explicit images
    3. Hateful messages and/or images
    4. Any other content that may be unsuitable for children
If any of your uploads are found to contain any of the above, your upload may
be deleted, as well as your account, and any other content you may have
uploaded.

By checking the box below, you are signifying that you understand the contents
of these terms. If you have any questions or comments regarding these terms, one
of the members of the development team would gladly assist you.
').'</textarea><br />
    <input type="checkbox" name="terms" /> '._('I agree to the above terms').'<br />
    <input type="submit" value="Submit" />
    </form>';

?>

</head>
<body>
<?php include(ROOT.'include/menu.php');
echo '
<div id="content">';

if (!isset($_POST['terms'])) $_POST['terms'] = NULL;

if ($_GET['action'] == 'submit' && $_POST['pass1'] != $_POST['pass2'])
{
    echo '<span class="error">'._('Your passwords do not match.').'</span><br /><br />';
    echo $login_form;
}
elseif ($_GET['action'] == 'submit' && strlen($_POST['user']) < 4)
{
    echo '<span class="error">'._('Your username must be at least 4 characters long.').'</span><br /><br />';
    echo $login_form;
}
elseif ($_GET['action'] == 'submit' && strlen($_POST['pass1']) < 6)
{
    echo '<span class="error">'._('Your password must be at least 6 characters long.').'</span><br /><br />';
    echo $login_form;
}
elseif ($_GET['action'] == 'submit' && $_POST['terms'] != 'on')
{
    echo '<span class="error">'._('You must agree to the terms to register.').'</span><br />';
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
$str = cryptUrl(12);
?>
