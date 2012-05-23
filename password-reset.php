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
$security ="";
include('include.php');
$title = htmlspecialchars(_('STK Add-ons').' | '._('Reset Password'));
include(ROOT.'include/top.php');
echo '</head><body>';
include(ROOT.'include/menu.php');
echo '<div id="content">';
echo '<h1>'.htmlspecialchars(_('Reset Password')).'</h1>';

function display_reset_form() {
    $form = '<form id="reset_pw" action="password-reset.php?action=reset" method="POST">
    <table><tbody>
    <tr>
        <td colspan="2">'.htmlspecialchars(_('In order to reset your password, please enter your username and your email address. A password reset link will be emailed to you. Your old password will become inactive until your password is reset.')).'</td>
    </tr>
    <tr>
        <td><label for="reg_user">'.htmlspecialchars(_('Username:')).'<br />
        <span style="font-size: x-small; color: #666666; font-weight: normal;">('.htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'4')).')</span></label></td>
        <td><input type="text" name="user" id="reg_user" /></td>
    </tr>
    <tr>
        <td><label for="reg_email">'.htmlspecialchars(_('Email Address:')).'</label></td>
        <td><input type="text" name="mail" id="reg_email" /></td>
    </tr>
    <tr>
        <td></td><td>';
    
    // CAPTCHA
    require_once(ROOT.'include/recaptchalib.php');
    $publickey = CAPTCHA_PUB; // you got this from the signup page
    $form .= recaptcha_get_html($publickey);
    
    $form .='</td>
    </tr>
    <tr>
        <td></td><td><input type="submit" value="'.htmlspecialchars(_('Send Reset Link')).'" /></td>
    </tr>
    </tbody></table>
    </form>';
    return $form;
}

function display_password_prompt($username,$verification_code) {
    $form = '<form id="change_pw" action="password-reset.php?action=change" method="POST">
    <table><tbody>
    <tr>
        <td colspan="2">'.htmlspecialchars(_('Please enter a new password for your account.')).'</td>
    </tr>
    <tr>
        <td><label for="reg_pass">'.htmlspecialchars(_('New Password:')).'<br />
        <span style="font-size: x-small; color: #666666; font-weight: normal;">('.htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'6')).')</span></label></td>
        <td><input type="password" name="pass1" id="reg_pass" /></td>
    </tr>
    <tr>
        <td><label for="reg_pass2">'.htmlspecialchars(_('New Password (confirm):')).'</label></td>
        <td><input type="password" name="pass2" id="reg_pass2" /></td>
    </tr>
    <tr>
        <td></td><td><input type="submit" value="'.htmlspecialchars(_('Change Password')).'" /></td>
    </tr>
    </tbody></table>
    <input type="hidden" name="user" value="'.$username.'" />
    <input type="hidden" name="verify" value="'.$verification_code.'" />
    </form>';
    return $form;
}

// define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($_GET['action']) {
    default:
        echo display_reset_form();
        break;

    case 'reset':
        // Look up username and try to reset
        try
        {
            // Check CAPTCHA
            require_once(ROOT.'include/recaptchalib.php');
            $privatekey = CAPTCHA_PRIV;
            $resp = recaptcha_check_answer ($privatekey,
                            $_SERVER["REMOTE_ADDR"],
                            $_POST["recaptcha_challenge_field"],
                            $_POST["recaptcha_response_field"]);

            if (!$resp->is_valid) {
            // What happens when the CAPTCHA was entered incorrectly
            throw new UserException("The reCAPTCHA wasn't entered correctly. Go back and try it again." .
            "(reCAPTCHA said: " . $resp->error . ")");
            }

            // Check all form input
            $username = Validate::username($_POST['user']);
            $email = Validate::email($_POST['mail']);

            // Make sure requested username is not taken
            $check_name_query = "SELECT `user` FROM `".DB_PREFIX."users`
                    WHERE `user` = '$username'
                    AND `email` = '$email'";
            $check_name_handle = sql_query($check_name_query);
            if (!$check_name_handle)
                throw new UserException(htmlspecialchars(
                        _('An error occurred trying to validate your username.')
                        .' '._('Please contact a website administrator.')));
            if (mysql_num_rows($check_name_handle) !== 1)
                throw new UserException(htmlspecialchars(_('Username and email address combination not found.')));

            // Username and email combo found - set the account to be requiring a reset

            // Generate verification code

            $verification_code = cryptUrl(12);
            $reset_query = 'UPDATE `'.DB_PREFIX.'users`
                SET `active` = 0, `verify` = \'reset-'.$verification_code.'\'
                WHERE `user` = \''.$username.'\'';
            $reset_handle = sql_query($reset_query);
            if (!$reset_handle)
                throw new UserException(htmlspecialchars(
                        _('An error occurred while resetting your password.')
                        .' '._('Please contact a website administrator.')));

            // Send verification email
	    try {
		Mail::passwordResetNotification($email, $username, 'reset-'.$verification_code, $_SERVER['PHP_SELF']);
	    }
	    catch (Exception $e) {
		echo '<span class="error">'.$e->getMessage().'</span><br /><br />';
		Log::newEvent('Password reset email for \''.$username.'\' could not be sent.');
	    }
            echo htmlspecialchars(_("Password reset link sent. Please reset your password using the link emailed to you."));
            Log::newEvent("Password reset request for user '$username'");
        }
        catch (UserException $e)
        {
            echo '<span class="error">'.$e->getMessage().'</span><br /><br />';
        }
        break;

    case 'valid':
        try {
            $_GET['user'] = (isset($_GET['user'])) ? $_GET['user'] : NULL;
            $_GET['num'] = (isset($_GET['num'])) ? $_GET['num'] : NULL;
            $username = mysql_real_escape_string($_GET['user']);
            $verification_code = mysql_real_escape_string($_GET['num']);
            $lookup_query = 'SELECT `user` FROM `'.DB_PREFIX.'users`
                WHERE `user` = \''.$username.'\'
                AND `verify` = \''.$verification_code.'\'
                AND `active` = 0';
            $lookup_handle = sql_query($lookup_query);
            if (!$lookup_handle)
                throw new UserException(htmlspecialchars(
                        _('An error occurred while resetting your password.')
                        .' '._('Please contact a website administrator.')));
            if (mysql_num_rows($lookup_handle) !== 1)
                throw new UserException(htmlspecialchars(_('Invalid verification code.')));
            
            echo display_password_prompt($username,$verification_code);
        }
        catch (UserException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br /><br />';
            echo htmlspecialchars(_('Could not reset your password. The link you followed is not valid.'));
        }
        break;
        
    case 'change':
        try {
            $_POST['user'] = (isset($_POST['user'])) ? $_POST['user'] : NULL;
            $_POST['verify'] = (isset($_POST['verify'])) ? $_POST['verify'] : NULL;
            $_POST['pass1'] = (isset($_POST['pass1'])) ? $_POST['pass1'] : NULL;
            $_POST['pass2'] = (isset($_POST['pass2'])) ? $_POST['pass2'] : NULL;

            $username = mysql_real_escape_string(strip_tags($_POST['user']));
            $verification_code = mysql_real_escape_string(strip_tags($_POST['verify']));
            $pass1 = mysql_real_escape_string($_POST['pass1']);
            $pass2 = mysql_real_escape_string($_POST['pass2']);

            $lookup_query = 'SELECT `user` FROM `'.DB_PREFIX.'users`
                WHERE `user` = \''.$username.'\'
                AND `verify` = \''.$verification_code.'\'
                AND `active` = 0';
            $lookup_handle = sql_query($lookup_query);
            if (!$lookup_handle)
                throw new UserException(htmlspecialchars(
                        _('An error occurred while resetting your password.')
                        .' '._('Please contact a website administrator.')));
            if (mysql_num_rows($lookup_handle) !== 1) {
                echo 'Could not reset password.';
                break;
            }

            $pass = Validate::password($pass1, $pass2);
            $query = 'UPDATE `'.DB_PREFIX."users`
                SET `pass` = '$pass',
                    `active` = 1,
                    `verify` = ''
                WHERE `user` = '$username'";
            $handle = sql_query($query);
            if (!$handle)
                throw new UserException(htmlspecialchars(_('Failed to change your password.')));

            echo htmlspecialchars(_('Changed password.')).'<br />';
            echo '<a href="login.php">'.htmlspecialchars(_('Login')).'</a>';
        }
        catch (UserException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br /><br />';
            echo display_password_prompt($username, $verification_code);
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
