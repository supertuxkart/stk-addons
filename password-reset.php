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

Template::setFile('password-reset.tpl');
$tpl = array();
$tpl['title'] = htmlspecialchars(_('Reset Password').' - '._('STK Add-ons'));

// CAPTCHA
require_once(ROOT.'include/recaptchalib.php');
$publickey = CAPTCHA_PUB; // you got this from the signup page
$captcha = recaptcha_get_html($publickey);

// Fill out various templates
$pw_res = array(
    'title' => htmlspecialchars(_('Reset Password')),
    'info' => NULL,
    'reset_form' => array(
	'display' => true,
	'form' => array(
	    'start' => '<form id="reset_pw" action="password-reset.php?action=reset" method="POST">',
	    'end' => '</form>'
	),
	'info' => htmlspecialchars(_('In order to reset your password, please enter your username and your email address. A password reset link will be emailed to you. Your old password will become inactive until your password is reset.')),
	'username' => array(
	    'label' => '<label for="reg_user">'.htmlspecialchars(_('Username:')).'</label>',
	    'field' => '<input type="text" name="user" id="reg_user" />'
	),
	'email' => array(
	    'label' => '<label for="reg_email">'.htmlspecialchars(_('Email Address:')).'</label>',
	    'field' => '<input type="text" name="mail" id="reg_email" />'
	),
	'captcha' => array(
	    'label' => NULL,
	    'field' => $captcha
	),
	'submit' => array(
	    'field' => '<input type="submit" value="'.htmlspecialchars(_('Send Reset Link')).'" />'
	)
    ),
    'pass_form' => array(
	'display' => false
    )
);


// define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($_GET['action']) {
    default:
        break;

    case 'reset':
	$pw_res['reset_form']['display'] = false;
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
		$pw_res['info'] .= '<span class="error">'.$e->getMessage().'</span><br /><br />';
		Log::newEvent('Password reset email for \''.$username.'\' could not be sent.');
	    }
            $pw_res['info'] .= htmlspecialchars(_("Password reset link sent. Please reset your password using the link emailed to you."));
            Log::newEvent("Password reset request for user '$username'");
        }
        catch (UserException $e)
        {
            $pw_res['info'] .= '<span class="error">'.$e->getMessage().'</span><br /><br />';
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
            
	    $pw_res['reset_form']['display'] = false;
	    $pw_res['pass_form'] = array(
		'display' => true,
		'form' => array(
		    'start' => '<form id="change_pw" action="password-reset.php?action=change" method="POST">',
		    'end' => '<input type="hidden" name="user" value="'.$username.'" />'.
			'<input type="hidden" name="verify" value="'.$verification_code.'" />'.
			'</form>'
		),
		'info' => htmlspecialchars(_('Please enter a new password for your account.')),
		'new_pass' => array(
		    'label' => '<label for="reg_pass">'.htmlspecialchars(_('New Password:')).'<br />'.
			'<span style="font-size: x-small; color: #666666; font-weight: normal;">('.htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'6')).')</span></label>',
		    'field' => '<input type="password" name="pass1" id="reg_pass" />'
		),
		'new_pass2' => array(
		    'label' => '<label for="reg_pass2">'.htmlspecialchars(_('New Password (confirm):')).'</label>',
		    'field' => '<input type="password" name="pass2" id="reg_pass2" />'
		),
		'submit' => array(
		    'field' => '<input type="submit" value="'.htmlspecialchars(_('Change Password')).'" />'
		)
	    );
        }
        catch (UserException $e) {
            $pw_res['info'] .= '<span class="error">'.$e->getMessage().'</span><br /><br />';
            $pw_res['info'] .= htmlspecialchars(_('Could not reset your password. The link you followed is not valid.'));
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

	    $pw_res['reset_form']['display'] = false;
            $pw_res['info'] .= htmlspecialchars(_('Changed password.')).'<br />';
            $pw_res['info'] .= '<a href="login.php">'.htmlspecialchars(_('Login')).'</a>';
        }
        catch (UserException $e) {
            $pw_res['info'] .= '<span class="error">'.$e->getMessage().'</span><br /><br />';
	    $pw_res['reset_form']['display'] = false;
	    $pw_res['pass_form'] = array(
		'display' => true,
		'form' => array(
		    'start' => '<form id="change_pw" action="password-reset.php?action=change" method="POST">',
		    'end' => '<input type="hidden" name="user" value="'.$username.'" />'.
			'<input type="hidden" name="verify" value="'.$verification_code.'" />'.
			'</form>'
		),
		'info' => htmlspecialchars(_('Please enter a new password for your account.')),
		'new_pass' => array(
		    'label' => '<label for="reg_pass">'.htmlspecialchars(_('New Password:')).'<br />'.
			'<span style="font-size: x-small; color: #666666; font-weight: normal;">('.htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'6')).')</span></label>',
		    'field' => '<input type="password" name="pass1" id="reg_pass" />'
		),
		'new_pass2' => array(
		    'label' => '<label for="reg_pass2">'.htmlspecialchars(_('New Password (confirm):')).'</label>',
		    'field' => '<input type="password" name="pass2" id="reg_pass2" />'
		),
		'submit' => array(
		    'field' => '<input type="submit" value="'.htmlspecialchars(_('Change Password')).'" />'
		)
	    );
        }
        
        break;
}

$tpl['pass_reset'] = $pw_res;
Template::assignments($tpl);
Template::display();

?>