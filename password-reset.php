<?php
/**
 * copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2012-2014 Stephen Just <stephenjust@gmail.com>
 *           2013      Glenn De Jonghe
 *           2014      Daniel Butum <danibutum at gmail dot com>
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

require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
use Captcha\Captcha;

$tpl = new StkTemplate('password-reset.tpl');
$tpl->assign('title', h(_('Reset Password') . ' - ' . _('STK Add-ons')));

// CAPTCHA
$captcha = new Captcha();
$captcha->setPublicKey(CAPTCHA_PUB)->setPrivateKey(CAPTCHA_PRIV);

// Fill out various templates
$pw_res = array(
    'info'       => null,
    'reset_form' => array(
        'display' => true,
        'captcha' => array(
            'field' => $captcha->html()
        ),
    ),
    'pass_form'  => array(
        'display' => false
    )
);


// define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;

switch ($_GET['action'])
{
    case 'reset':
        $pw_res['reset_form']['display'] = false;
        // Look up username and try to reset
        try
        {
            // Check CAPTCHA
            $response = $captcha->check();
            if (!$response->isValid())
            {
                // What happens when the CAPTCHA was entered incorrectly
                throw new UserException(
                    "The reCAPTCHA wasn't entered correctly. Go back and try it again." . "(reCAPTCHA said: " . $response->getError() . ")");
            }

            User::recover($_POST['user'], $_POST['mail']);
            $pw_res['info'] .= _h(
                "Password reset link sent. Please reset your password using the link emailed to you."
            );
        }
        catch(UserException $e)
        {
            $pw_res['info'] .= '<span class="error">' . $e->getMessage() . '</span><br /><br />';
        }
        break;

    case 'valid':
        try
        {
            $userid = (isset($_GET['user'])) ? $_GET['user'] : 0;
            $verification_code = (isset($_GET['num'])) ? $_GET['num'] : "";
            Verification::verify($userid, $verification_code);
            $pw_res['reset_form']['display'] = false;
            $pw_res['pass_form'] = array(
                'display'   => true,
                'form'      => array(
                    'start' => '<form id="change_pw" action="password-reset.php?action=change" method="POST">',
                    'end'   => '<input type="hidden" name="user" value="' . $userid . '" />' .
                        '<input type="hidden" name="verify" value="' . $verification_code . '" />' .
                        '</form>'
                ),
                'info'      => _h('Please enter a new password for your account.'),
                'new_pass'  => array(
                    'label' => '<label for="reg_pass">' . _h('New Password:') . '<br />' .
                        '<span style="font-size: x-small; color: #666666; font-weight: normal;">(' . h(
                            sprintf(_('Must be at least %d characters long.'), '8')
                        ) . ')</span></label>',
                    'field' => '<input type="password" name="pass1" id="reg_pass" />'
                ),
                'new_pass2' => array(
                    'label' => '<label for="reg_pass2">' . _h('New Password (confirm):') . '</label>',
                    'field' => '<input type="password" name="pass2" id="reg_pass2" />'
                ),
                'submit'    => array(
                    'field' => '<input type="submit" value="' . _h('Change Password') . '" />'
                )
            );
        }
        catch(UserException $e)
        {
            $pw_res['info'] .= '<span class="error">' . $e->getMessage() . '</span><br /><br />';
            $pw_res['info'] .= _h('Could not reset your password. The link you followed is not valid.');
        }
        break;

    case 'change':
        try
        {
            $userid = (isset($_POST['user'])) ? $_POST['user'] : 0;
            $verification_code = (isset($_POST['verify'])) ? $_POST['verify'] : "";
            $pass1 = (isset($_POST['pass1'])) ? $_POST['pass1'] : "";
            $pass2 = (isset($_POST['pass2'])) ? $_POST['pass2'] : "p";
            Verification::verify($userid, $verification_code);

            $pass = Validate::password($pass1, $pass2);
            User::changePassword($pass, $userid);
            Verification::delete($userid);
            $pw_res['reset_form']['display'] = false;
            $pw_res['info'] .= _h('Changed password.') . '<br />';
            $pw_res['info'] .= '<a href="login.php">' . _h('Login') . '</a>';
        }
        catch(UserException $e)
        {
            $pw_res['info'] .= '<span class="error">' . $e->getMessage() . '</span><br /><br />';
            $pw_res['reset_form']['display'] = false;
            $pw_res['pass_form'] = array(
                'display'   => true,
                'form'      => array(
                    'start' => '<form id="change_pw" action="password-reset.php?action=change" method="POST">',
                    'end'   => '<input type="hidden" name="user" value="' . $username . '" />' .
                        '<input type="hidden" name="verify" value="' . $verification_code . '" />' .
                        '</form>'
                ),
                'info'      => _h('Please enter a new password for your account.'),
                'new_pass'  => array(
                    'label' => '<label for="reg_pass">' . _h('New Password:') . '<br />' .
                        '<span style="font-size: x-small; color: #666666; font-weight: normal;">(' . h(
                            sprintf(_('Must be at least %d characters long.'), '8')
                        ) . ')</span></label>',
                    'field' => '<input type="password" name="pass1" id="reg_pass" />'
                ),
                'new_pass2' => array(
                    'label' => '<label for="reg_pass2">' . _h('New Password (confirm):') . '</label>',
                    'field' => '<input type="password" name="pass2" id="reg_pass2" />'
                ),
                'submit'    => array(
                    'field' => '<input type="submit" value="' . _h('Change Password') . '" />'
                )
            );
        }
        break;

    default:
        break;
}

$tpl->assign('pass_reset', $pw_res);
echo $tpl;
