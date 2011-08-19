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
    '.htmlspecialchars(_('Username:')).' ('.htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'4')).')<br />
    <input type="text" name="user" /><br />
    '.htmlspecialchars(_('Password:')).' ('.htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'6')).')<br />
    <input type="password" id="pass1" name="pass1" /><br />
    '.htmlspecialchars(_('Password (confirm):')).' <br />
    <input type="password" id="pass2" name="pass2" /><br />
    '.htmlspecialchars(_('Name:')).' <br />
    <input type="text" id="name" name="name" /><br />
    '.htmlspecialchars(_('Email Address:')).' <br />
    <input type="text" name="mail" /><br /><br />
    '.htmlspecialchars(_('Terms:')).'<br />
    <textarea rows="10" cols="80" readonly>
=== '.htmlspecialchars(_('STK Addons Terms and Conditions'))." ===\n\n".
htmlspecialchars(_('You must agree to these terms in order to upload content to the STK Addons site.'))."\n\n".
_('The STK Addons service is designed to be a repository exclusively for Super
Tux Kart addon content. All uploaded content must be intended for this
purpose. When you upload your content, it will be available publicly on the
internet, and will be made available in-game for download.')."\n\n".
htmlspecialchars(_('Super Tux Kart aims to comply with the Debian Free Software Guidelines (DFSG).
TuxFamily.org also requires that content they host comply with open licenses.
You may not upload content which is locked down with a restrictive license.
Licenses such as CC-BY-SA 3.0, or other DFSG-compliant licenses are required.
All content taken from third-party sources must be attributed properly, and must
also be available under an open license. Licenses and attribution should be
included in a "license.txt" file in each uploaded archive. Uploads without
proper licenses or attribution may be deleted without warning.'))."\n\n".
htmlspecialchars(_('Even with valid licenses and attribution, content may not contain any
of the following:'))."\n".
'    1. '.htmlspecialchars(_('Profanity'))."\n".
'    2. '.htmlspecialchars(_('Explicit images'))."\n".
'    3. '.htmlspecialchars(_('Hateful messages and/or images'))."\n".
'    4. '.htmlspecialchars(_('Any other content that may be unsuitable for children'))."\n".
htmlspecialchars(_('If any of your uploads are found to contain any of the above, your upload
will be removed, your account may be removed, and any other content you uploaded
may be removed.'))."\n\n".
htmlspecialchars(_('By checking the box below, you are confirming that you understand these
terms. If you have any questions or comments regarding these terms, one of the
members of the development team would gladly assist you.')).
'</textarea><br />
    <input type="checkbox" name="terms" /> '.htmlspecialchars(_('I agree to the above terms')).'<br />
    <input type="submit" value="Submit" />
    </form>';

?>

</head>
<body>
<?php include(ROOT.'include/menu.php');
echo '
<div id="content">';

if (!isset($_POST['terms'])) $_POST['terms'] = NULL;

if ($_GET['action'] == 'submit')
{
    // Register new account
    try
    {
        // Check all form input
        $username = Validate::username($_POST['user']);
        $password = Validate::password($_POST['pass1'], $_POST['pass2']);
        $email = Validate::email($_POST['mail']);
        $name = Validate::realname($_POST['name']);
        $terms = Validate::checkbox($_POST['terms'],htmlspecialchars(_('You must agree to the terms to register.')));
        
        // Make sure requested username is not taken
        $check_name_query = "SELECT * FROM `".DB_PREFIX."users` WHERE `user` = '$username'";
        $check_name_handle = sql_query($check_name_query);
        if (!$check_name_handle)
            throw new UserException(htmlspecialchars(
                    _('An error occurred trying to validate your username.')
                    .' '._('Please contact a website administrator.')));
        if (mysql_num_rows($check_name_handle) !== 0)
            throw new UserException(htmlspecialchars(_('Your username has already been used.')));

        // No exception occurred - continue with registration

        // Generate verification code
        $verification_code = cryptUrl(12);
        $creation_date = date('Y-m-d');
        $create_query = 'INSERT INTO `'.DB_PREFIX."users`
                (`user`, `pass`, `name`,
                `role`, `email`, `active`,
                `verify`, `reg_date`)
            VALUES
                ('$username', '$password', '$name',
                'basicUser', '$email', '0',
                '$verification_code', '$creation_date')";
        $create_handle = sql_query($create_query);
        if (!$create_handle)
            throw new UserException(htmlspecialchars(
                    _('An error occurred while creating your account.')
                    .' '._('Please contact a website administrator.')));
        
        // Send verification email
        sendMail($email, "newAccount", array($verification_code, $_SERVER["PHP_SELF"], $username));
        echo htmlspecialchars(_("Account creation was successful. Please activate your account using the link emailed to you."));
    }
    catch (UserException $e)
    {
        echo '<span class="error">'.$e->getMessage().'</span><br /><br />';
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
    echo htmlspecialchars(_('Your account has been activated.')).'<br />';
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
