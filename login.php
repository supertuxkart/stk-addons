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

define('ROOT', './');
require_once(ROOT . 'config.php');

// define possibly undefined variables
$_POST['user'] = (isset($_POST['user'])) ? $_POST['user'] : null;
$_POST['pass'] = (isset($_POST['pass'])) ? $_POST['pass'] : null;
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;

$tpl = new StkTemplate('login.tpl');
// Prepare forms
$login_form = array(
    'display' => true,
    'form'    => array(
        'action'    => File::rewrite('login.php?action=submit'),
    ),
    'links'   => array(
        'register'       => File::link('register.php', htmlspecialchars(_('Create an account.'))),
        'reset_password' => File::link('password-reset.php', htmlspecialchars(_('Forgot password?')))
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
            $tpl->assign('confirmation', htmlspecialchars(_('Failed to logout.')));
        }
        else
        {
            $tpl->setMetaRefresh('index.php', 3);
            $conf = htmlspecialchars(_('You have been logged out.')) . '<br />';
            $conf .= sprintf(
                    htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),
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
            $tpl->setMetaRefresh('index.php', 3);
            $conf = sprintf(htmlspecialchars(_('Welcome, %s!')) . '<br />', $_SESSION['real_name']);
            $conf .= sprintf(
                    htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),
                    '<a href="index.php">',
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
            $conf = htmlspecialchars(_('You are already logged in.')) . ' ';
            $conf .= sprintf(
                    htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),
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
