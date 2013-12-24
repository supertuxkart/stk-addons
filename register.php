<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
 *           2012 Stephen Just <stephenjust@users.sf.net>
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
require_once(ROOT . 'include.php');
$title = htmlspecialchars(_('STK Add-ons').' | '._('Register'));
include(ROOT.'include/top.php');
echo '</head><body>';
include(ROOT.'include/menu.php');
echo '<div id="content">';
echo '<h1>'.htmlspecialchars(_('Account Registration')).'</h1>';

function display_reg_form($user = NULL, $name = NULL, $email = NULL) {
    $form = '<form id="register" action="register.php?action=reg" method="POST">
    <table><tbody>
    <tr>
        <td><label for="reg_user">'.htmlspecialchars(_('Username:')).'<br />
        <span style="font-size: x-small; color: #666666; font-weight: normal;">('.htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'4')).')</span></label></td>
        <td><input type="text" name="user" id="reg_user" value="'.htmlspecialchars($user).'" /></td>
    </tr>
    <tr>
        <td><label for="reg_pass">'.htmlspecialchars(_('Password:')).'<br />
        <span style="font-size: x-small; color: #666666; font-weight: normal;">('.htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'8')).')</span></label></td>
        <td><input type="password" name="pass1" id="reg_pass" /></td>
    </tr>
    <tr>
        <td><label for="reg_pass2">'.htmlspecialchars(_('Password (confirm):')).'</label></td>
        <td><input type="password" name="pass2" id="reg_pass2" /></td>
    </tr>
    <tr>
        <td><label for="reg_name">'.htmlspecialchars(_('Name:')).'</label></td>
        <td><input type="text" name="name" id="reg_name" value="'.htmlspecialchars($name).'" /></td>
    </tr>
    <tr>
        <td><label for="reg_email">'.htmlspecialchars(_('Email Address:')).'</label></td>
        <td><input type="text" name="mail" id="reg_email" value="'.htmlspecialchars($email).'" /></td>
    </tr>
    <tr>
        <td colspan="2"><label for="reg_terms">'.htmlspecialchars(_('Terms:')).'</label><br />
<textarea rows="10" cols="80" readonly id="reg_terms">
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
'</textarea>
        </td>
    </tr>
    <tr>
        <td><label for="reg_check">'.htmlspecialchars(_('I agree to the above terms')).'</label></td>
        <td><input type="checkbox" name="terms" id="reg_check" /></td>
    </tr>
    <tr>
        <td></td><td><input type="submit" value="'.htmlspecialchars(_('Register!')).'" /></td>
    </tr>
    </tbody></table>
    </form>';
    return $form;
}

// define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($_GET['action']) {
    default:
        echo display_reg_form();
        break;

    case 'reg':
        // Register new account
        try
        {
            if (!isset($_POST['terms'])) $_POST['terms'] = NULL;
	    User::register($_POST['user'],
		    $_POST['pass1'],
		    $_POST['pass2'],
		    $_POST['mail'],
		    $_POST['name'],
		    $_POST['terms']);
            echo htmlspecialchars(_("Account creation was successful. Please activate your account using the link emailed to you."));
        }
        catch (UserException $e)
        {
            echo '<span class="error">'.$e->getMessage().'</span><br /><br />';
            echo display_reg_form($_POST['user'],$_POST['name'],$_POST['mail']);
        }
        break;

    case 'valid':
        try {
            $username = strip_tags($_GET['user']);
            $verification_code = strip_tags($_GET['num']);
            User::activate($username,$verification_code);
            echo htmlspecialchars(_('Your account has been activated.')).'<br />';
        }
        catch (UserException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br /><br />';
            echo htmlspecialchars(_('Could not validate your account. The link you followed is not valid.'));
        }
        break;
}
    echo '
</div>';
     include("include/footer.php"); ?>
</body>
</html>
<?php
$str = cryptUrl(12);

?>
