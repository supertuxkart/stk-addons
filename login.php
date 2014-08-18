<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2011-2014 Stephen Just <stephenjust@users.sourceforge.net>
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

// define possibly undefined variables
$_POST['username'] = isset($_POST['username']) ? $_POST['username'] : null;
$_POST['password'] = isset($_POST['password']) ? $_POST['password'] : null;
$_GET['action'] = isset($_GET['action']) ? $_GET['action'] : null;

// set default redirect url from where the user was
$return_to_url = $safe_url = SITE_ROOT . "index.php";
if (isset($_POST["return_to"]))
{
    $return_to_url = $_POST["return_to"];
}
elseif (isset($_GET["return_to"]))
{
    // decode the get
    $return_to_url = urldecode($_GET["return_to"]);
}

// prevent foreign domain
if (!Util::str_starts_with($return_to_url, SITE_ROOT))
{
    // silently fall back to safe url
    $return_to_url = $safe_url;
}

$tpl = StkTemplate::get('login.tpl')
    ->assignTitle(_h("Login"))
    ->addBootstrapValidatorLibrary();

// Prepare forms
$login_form = [
    'display'     => true,
    'username'    => ['min' => User::MIN_USERNAME, 'max' => User::MAX_USERNAME],
    'password'    => ['min' => User::MIN_PASSWORD, 'max' => User::MAX_PASSWORD],
    'return_to'   => $return_to_url,
    'form_action' => File::rewrite('login.php?action=submit'),
    'links'       => [
        'register'       => File::link('register.php', _h('Sign up here.')),
        'reset_password' => File::link('password-reset.php', _h('(forgot password)'))
    ]
];

switch ($_GET['action'])
{
    case 'logout':
        $login_form['display'] = false;

        User::logout();

        if (User::isLoggedIn())
        {
            $tpl->assign('errors', _h('Failed to logout.'));
        }
        else
        {
            $tpl->setMetaRefresh($safe_url, 3);
            $conf = _h('You have been logged out.') . '. ';
            $conf .= sprintf(_h('Click %shere%s if you do not automatically redirect.'), "<a href=\"{$safe_url}\">", '</a>');
            $tpl->assign('success', $conf);
        }

        break;

    case 'submit':
        $login_form['display'] = false;

        $errors = "";
        try
        {
            User::login($_POST['username'], $_POST['password']);
        }
        catch(UserException $e)
        {
            $login_form['display'] = true;
            $errors = $e->getMessage();
        }

        if (User::isLoggedIn())
        {
            $tpl->setMetaRefresh($return_to_url, 3);
            $conf = sprintf(_h('Welcome, %s!'), User::getLoggedRealName()) . '. ';
            $conf .= sprintf(_h('Click %shere%s if you do not automatically redirect.'), "<a href=\"{$return_to_url}\">", '</a>');
            $tpl->assign('success', $conf);
        }
        else
        {
            $login_form['display'] = true;
            $tpl->assign('errors', $errors);
        }

        break;

    default:
        if (User::isLoggedIn())
        {
            $login_form['display'] = false;
            $tpl->setMetaRefresh('index.php', 3);

            $conf = _h('You are already logged in.') . '. ';
            $conf .= 'Click <a href="index.php">here</a> if you do not automatically redirect.';
            $tpl->assign('success', $conf);
        }
        break;
}

$tpl->assign('login', $login_form);
echo $tpl;
