<?php
/**
 * copyright 2009        Lucas Baudin <xapantu@gmail.com>
 *           2012 - 2014 Stephen Just <stephenjust@gmail.com>
 *           2013        Glenn De Jonghe
 *           2014 - 2016 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons. If not, see <http://www.gnu.org/licenses/>.
 */
require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");
Util::validateCaptchaKeysSet();

$tpl = StkTemplate::get('password-reset.tpl')
    ->assignTitle(_h('Reset Password'))
    ->addScriptIncludeWeb('https://www.google.com/recaptcha/api.js');

// Fill out various templates
$pw_res = [
    'reset_form' => [
        'display' => true,
        'captcha_site_key' => CAPTCHA_SITE_KEY,
    ],
    'pass_form'  => [
        'display'           => false,
        'user_id'           => "",
        'verification_code' => ""
    ]
];


// define possibly undefined variables
$_GET['action'] = isset($_GET['action']) ? $_GET['action'] : null;

switch ($_GET['action'])
{
    case 'reset': // user sent reset activation link
        $pw_res['reset_form']['display'] = false;

        // Look up username and try to reset
        try
        {
            if (Validate::ensureNotEmpty($_POST, ['g-recaptcha-response']))
                throw new UserException(_h('You did not complete the reCAPTCHA field'));

            // Check CAPTCHA
            $captcha = new \ReCaptcha\ReCaptcha(CAPTCHA_SECRET);
            $response = $captcha->verify($_POST['g-recaptcha-response'], Util::getClientIp());
            if (!$response->isSuccess())
            {
                // codes reference https://developers.google.com/recaptcha/docs/verify#error-code-reference
                throw new UserException(_h("The reCAPTCHA wasn't entered correctly. Go back and try it again."));
            }

            User::recover($_POST['user'], $_POST['mail']);
            $tpl->assign("success", _h("Password reset link sent. Please reset your password using the link emailed to you."));
        }
        catch(UserException $e)
        {
            $tpl->assign("errors", $e->getMessage());
        }
        break;

    case 'valid': // user comes from activation link
        try
        {
            $user_id = isset($_GET['user']) ? $_GET['user'] : 0;
            $verification_code = isset($_GET['num']) ? $_GET['num'] : "";

            Verification::verify($user_id, $verification_code);

            $pw_res['reset_form']['display'] = false;
            $pw_res['pass_form'] = [
                'display'           => true,
                'user_id'           => $user_id,
                'verification_code' => $verification_code
            ];
        }
        catch(UserException $e)
        {
            $tpl->assign("errors", $e->getMessage() . ". " . _h('Could not reset your password. The link you followed is not valid.'));
        }
        break;

    case 'change': // change password clicked in the 'valid' page
        $user_id = isset($_POST['user']) ? $_POST['user'] : 0;
        $verification_code = isset($_POST['verify']) ? $_POST['verify'] : "";
        $pass1 = isset($_POST['pass1']) ? $_POST['pass1'] : "";
        $pass2 = isset($_POST['pass2']) ? $_POST['pass2'] : "";

        try
        {
            // validate
            Verification::verify($user_id, $verification_code);
            User::validateNewPassword($pass1, $pass2);

            // change password and clean up
            User::changePassword($user_id, $pass1);
            Verification::delete($user_id);

            $pw_res['reset_form']['display'] = false;
            $tpl->assign("success", _h('Changed password was successful.') . '<a href="login.php"> ' . _h('Click here to login') . '</a>');
        }
        catch(UserException $e)
        {
            $tpl->assign("errors", $e->getMessage());
            $pw_res['reset_form']['display'] = false;
            $pw_res['pass_form'] = [
                'display'           => true,
                'user_id'           => $user_id,
                'verification_code' => $verification_code
            ];
        }
        break;

    default:
        break;
}

$tpl->assign('pass_reset', $pw_res);
echo $tpl;
