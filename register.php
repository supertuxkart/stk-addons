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

$_POST['user'] = empty($_POST['user']) ? null : $_POST['user'];
$_POST['name'] = empty($_POST['name']) ? null : $_POST['name'];
$_POST['mail'] = empty($_POST['mail']) ? null : $_POST['mail'];
$_GET['action'] = empty($_GET['action']) ? null : $_GET['action'];

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
    'username' => ['min' => User::MIN_USERNAME, 'max' => User::MAX_USERNAME, 'value' => h($_POST['user'])],
    'password' => ['min' => User::MIN_PASSWORD, 'max' => User::MAX_PASSWORD],
    'name'     => ['min' => User::MIN_REALNAME, 'max' => User::MAX_USERNAME, 'value' => h($_POST['name'])],
    'email'    => ['max' => User::MAX_EMAIL, 'value' => h($_POST['mail'])]

];

// define possibly undefined variables

switch ($_GET['action'])
{
    case 'register': // Register new account
        try
        {
            // validate
            $errors = Validate::ensureInput($_POST, ["username", "password", "password_confirm", "mail", "name", "terms"]);
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
                $_POST['username'],
                $_POST['password'],
                $_POST['password_confirm'],
                $_POST['mail'],
                $_POST['name'],
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
            if (empty($_GET["num"]))
            {
                throw new UserException(_h("Activation code is empty"));
            }

            $username = h($_GET['user']);
            $verification_code = h($_GET['num']);

            User::activate($username, $verification_code);

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
