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

$tpl = StkTemplate::get('register.tpl')->assignTitle(_h('Register'));

// CAPTCHA
$captcha = new Captcha();
$captcha->setPublicKey(CAPTCHA_PUB)->setPrivateKey(CAPTCHA_PRIV);

$register = [
    'display_form' => false,
    'captcha'      => $captcha->html(),
    'form'         => [
        'username' => ['min' => 4, 'value' => h($_POST['user'])],
        'password' => ['min' => 8],
        'name'     => ['value' => h($_POST['name'])],
        'email'    => ['value' => h($_POST['mail'])]
    ]
];

// define possibly undefined variables

switch ($_GET['action'])
{
    case 'register': // Register new account
        try
        {
            // validate
            $errors = Validate::ensureInput($_POST, ["user", "pass1", "pass2", "mail", "name", "terms"]);
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
                $_POST['user'],
                $_POST['pass1'],
                $_POST['pass2'],
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
            $register['display_form'] = true;
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
        }
        catch(UserException $e)
        {
            $tpl->assign('errors', $e->getMessage() . ". " . _h('Could not validate your account. The link you followed is not valid.'));
        }
        break;

    default:
        $register['display_form'] = true;
        break;
}

$tpl->assign('register', $register);
echo $tpl;
