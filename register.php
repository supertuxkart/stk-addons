<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2012-2014 Stephen Just <stephenjust@users.sf.net>
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

$username = empty($_POST['username']) ? null : $_POST['username'];
$realname = empty($_POST['realname']) ? null : $_POST['realname'];
$email = empty($_POST['email']) ? null : $_POST['email'];
$action = empty($_GET['action']) ? null : $_GET['action'];

$tpl = StkTemplate::get('register.tpl')
    ->assignTitle(_h('Register'))
    ->addBootstrapValidatorLibrary()
    ->setMinify(false)
    ->addScriptInclude('https://www.google.com/recaptcha/api.js', '');

// TODO make template out of this
$has_captcha_keys = !empty(CAPTCHA_SITE_KEY) && !empty(CAPTCHA_SECRET);
if (!$has_captcha_keys)
{
    echo <<<HTML
       <h2>Add your keys</h2>
        <p>If you do not have keys already then visit
        <a href = "https://www.google.com/recaptcha/admin">
                https://www.google.com/recaptcha/admin</a> to generate them.
        Edit this file and set the respective keys in CAPTCHA_SITE_KEY and
CAPTCHA_SECRET inside the config.php file. Reload the page after this.</p>
HTML;
    exit;
}

$register = [
    'captcha_site_key' => CAPTCHA_SITE_KEY,
    'display'          => false,
    'username'         => ['min' => User::MIN_USERNAME, 'max' => User::MAX_USERNAME, 'value' => h($username)],
    'password'         => ['min' => User::MIN_PASSWORD, 'max' => User::MAX_PASSWORD],
    'realname'         => ['min' => User::MIN_REALNAME, 'max' => User::MAX_USERNAME, 'value' => h($realname)],
    'email'            => ['max' => User::MAX_EMAIL, 'value' => h($email)]

];

// define possibly undefined variables

switch ($action)
{
    case 'register': // register new account
        try
        {
            // validate
            $errors = Validate::ensureNotEmpty(
                $_POST,
                ["username", "password", "password_confirm", "email", "terms", "g-recaptcha-response"]
            );
            if ($errors)
            {
                throw new UserException(implode("<br>", $errors));
            }

            $captcha_response = $_POST['g-recaptcha-response'];
            $captcha = new \ReCaptcha\ReCaptcha(CAPTCHA_SECRET);

            // check captcha
            $response = $captcha->verify($captcha_response, Util::getClientIp());
            if (!$response->isSuccess())
            {
                // TODO handle better captcha errors.
                throw new UserException("The reCAPTCHA wasn't entered correctly. Go back and try it again.");
            }

            User::register(
                $username,
                $_POST['password'],
                $_POST['password_confirm'],
                $email,
                empty(trim($realname)) ? $username : $realname,
                $_POST['terms']
            );

            $tpl->assign(
                'success',
                _h("Account creation was successful. Please activate your account using the link emailed to you.")
            );
        }
        catch (UserException $e)
        {
            $tpl->assign('errors', $e->getMessage());
            $register['display'] = true;
        }
        break;

    case 'valid': // activation link
        try
        {
            // validate
            $errors = Validate::ensureNotEmpty($_GET, ["num", "user"]);
            if ($errors)
            {
                throw new UserException(implode("<br>", $errors));
            }

            User::activate($_GET['user'] /* user id */, $_GET["num"] /* verification code */);

            $tpl->assign('success', _h('Your account has been activated.'));
            $tpl->setMetaRefresh("login.php", 10);
        }
        catch (UserException $e)
        {
            $tpl->assign(
                'errors',
                $e->getMessage() . ". " . _h('Could not validate your account. The link you followed is not valid.')
            );
        }
        break;

    default:
        $register['display'] = true;
        break;
}

$tpl->assign('register', $register);
echo $tpl;
