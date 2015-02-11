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
use Captcha\Captcha;

$username = empty($_POST['username']) ? null : $_POST['username'];
$realname = empty($_POST['realname']) ? null : $_POST['realname'];
$email = empty($_POST['email']) ? null : $_POST['email'];
$action = empty($_GET['action']) ? null : $_GET['action'];

$tpl = StkTemplate::get('register.tpl')
    ->assignTitle(_h('Register'))
    ->addBootstrapValidatorLibrary()
    ->setMinify(false);

// CAPTCHA
$captcha = new Captcha();
$captcha->setPublicKey(CAPTCHA_PUB)->setPrivateKey(CAPTCHA_PRIV);

$register = [
    'display' => false,
    'captcha'  => $captcha->html(),
    'username' => ['min' => User::MIN_USERNAME, 'max' => User::MAX_USERNAME, 'value' => h($username)],
    'password' => ['min' => User::MIN_PASSWORD, 'max' => User::MAX_PASSWORD],
    'realname' => ['min' => User::MIN_REALNAME, 'max' => User::MAX_USERNAME, 'value' => h($realname)],
    'email'    => ['max' => User::MAX_EMAIL, 'value' => h($email)]

];

// define possibly undefined variables

switch ($action)
{
    case 'register': // register new account
        try
        {
            // validate
            $errors = Validate::ensureNotEmpty($_POST, ["username", "password", "password_confirm", "email", "terms"]);
            if ($errors)
            {
                throw new UserException(implode("<br>", $errors));
            }

            // check captcha
            $response = $captcha->check();
            if (!$response->isValid())
            {
                throw new UserException("The reCAPTCHA wasn't entered correctly. Go back and try it again.");
            }

            User::register(
                $username,
                $_POST['password'],
                $_POST['password_confirm'],
                $email,
                $realname,
                $_POST['terms']
            );

            $tpl->assign(
                'success',
                _h("Account creation was successful. Please activate your account using the link emailed to you.")
            );
        }
        catch(UserException $e)
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

            User::activate($_GET['user'] /* userid */, $_GET["num"] /* verification code */);

            $tpl->assign('success', _h('Your account has been activated.'));
            $tpl->setMetaRefresh("login.php", 10);
        }
        catch(UserException $e)
        {
            $tpl->assign('errors', $e->getMessage() . ". " . _h('Could not validate your account. The link you followed is not valid.'));
        }
        break;

    default:
        $register['display'] = true;
        break;
}

$tpl->assign('register', $register);
echo $tpl;
