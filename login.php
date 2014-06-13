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
$_POST['user'] = (isset($_POST['user'])) ? $_POST['user'] : null;
$_POST['pass'] = (isset($_POST['pass'])) ? $_POST['pass'] : null;
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;

// set default
$return_to_url = "index.php";
if (isset($_POST["return_to"]))
{
    $return_to_url = $_POST["return_to"];
}
elseif (isset($_GET["return_to"]))
{
    // decode the get
    $return_to_url = urldecode($_GET["return_to"]);
}

$tpl = new StkTemplate('login.tpl');
// Prepare forms
$login_form = array(
    'display'   => true,
    'return_to' => $return_to_url,
    'form'      => array(
        'action' => File::rewrite('login.php?action=submit'),
    ),
    'links'     => array(
        'register'       => File::link('register.php', _h('Create an account.')),
        'reset_password' => File::link('password-reset.php', _h('Forgot password?'))
    )
);

$errors = '';
switch ($_GET['action'])
{
    case 'logout':
        $login_form['display'] = false;

        User::logout();
        if (User::isLoggedIn() == true)
        {
            $tpl->assign('confirmation', _h('Failed to logout.'));
        }
        else
        {
            $tpl->setMetaRefresh("index.php", 3);
            $conf = _h('You have been logged out.') . '<br />';
            $conf .= sprintf(
                    _h('Click %shere%s if you do not automatically redirect.'),
                    '<a href="index.php">',
                    '</a>'
                ) . '<br />';
            $tpl->assign('confirmation', $conf);
        }

        break;

    case 'submit':
        $login_form['display'] = false;

        try
        {
            // Variable validation is done by the function below
            User::login($_POST['user'], $_POST['pass']);
        }
        catch(UserException $e)
        {
            $errors .= $e->getMessage();
        }
        if (User::isLoggedIn())
        {
            $tpl->setMetaRefresh($return_to_url, 3);
            $conf = sprintf(_h('Welcome, %s!') . '<br />', $_SESSION['real_name']);
            $conf .= sprintf(
                    _h('Click %shere%s if you do not automatically redirect.'),
                    "<a href=\"{$return_to_url}\">",
                    '</a>'
                ) . '<br />';
            $tpl->assign('confirmation', $conf);
        }
        else
        {
            $tpl->assign('confirmation', $errors);
        }
        break;

    default:
        if (User::isLoggedIn())
        {
            $login_form['display'] = false;
            $tpl->setMetaRefresh('index.php', 3);
            $conf = _h('You are already logged in.') . ' ';
            $conf .= sprintf(
                    _h('Click %shere%s if you do not automatically redirect.'),
                    '<a href="index.php">',
                    '</a>'
                ) . '<br />';
            $tpl->assign('confirmation', $conf);
        }
        break;
}

$tpl->assign('title', htmlspecialchars(_('STK Add-ons') . ' | ' . _('Login')));
$tpl->assign('login', $login_form);

echo $tpl;
