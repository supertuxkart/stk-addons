<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2011-2013 Stephen Just <stephenjust@users.sourceforge.net>
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
require_once(ROOT . 'config.php');
require_once(INCLUDE_DIR . 'locale.php');
require_once(INCLUDE_DIR . 'File.class.php');
require_once(INCLUDE_DIR . 'Template.class.php');
require_once(INCLUDE_DIR . 'User.class.php');

// define possibly undefined variables
$_POST['user'] = (isset($_POST['user'])) ? $_POST['user'] : NULL;
$_POST['pass'] = (isset($_POST['pass'])) ? $_POST['pass'] : NULL;
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : NULL;

$tpl = array();
// Prepare forms
$login_form = array(
    'display' => true,
    'form' => array(
	'start' => '<form action="'.File::rewrite('login.php?action=submit').'" method="POST">',
	'end'	=> '</form>',
	'username' => array(
	    'label' => htmlspecialchars(_('Username:')),
	    'field' => '<input type="text" name="user" />'
	),
	'password' => array(
	    'label' => htmlspecialchars(_('Password:')),
	    'field' => '<input type="password" name="pass" />'
	),
	'submit' => '<input type="submit" value="'.htmlspecialchars(_('Log In')).'" />'
    ),
    'links' => array(
	'register'	=> File::link('register.php',htmlspecialchars(_('Create an account.'))),
	'reset_password'=> File::link('password-reset.php',htmlspecialchars(_('Forgot password?')))
    )
);

$errors = '';
switch ($_GET['action']) {
    case 'logout':
	$login_form['display'] = false;

	User::logout();
	if (User::$logged_in === true)
	    $tpl['confirmation'] = htmlspecialchars(_('Failed to logout.'));
	else {
	    Template::$meta_tags['refresh'] = '3;URL=index.php';
	    $conf = htmlspecialchars(_('You have been logged out.')).'<br />';
	    $conf .= sprintf(htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),'<a href="index.php">','</a>').'<br />';
	    $tpl['confirmation'] = $conf;
	}
	
	break;
    
    case 'submit':
	$login_form['display'] = false;

	try
	{
	    // Variable validation is done by the function below
	    User::login($_POST['user'],$_POST['pass']);
	}
	catch (UserException $e)
	{
	    $errors .= $e->getMessage();
	}
	if (User::$logged_in === true) {
	    Template::$meta_tags['refresh'] = '3;URL=index.php';
	    $conf = sprintf(htmlspecialchars(_('Welcome, %s!')).'<br />',$_SESSION['real_name']);
	    $conf .= sprintf(htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),'<a href="index.php">','</a>').'<br />';
	    $tpl['confirmation'] = $conf;
	} else {
	    $tpl['confirmation'] = $errors;
	}
	break;
    
    default:
	if (User::$logged_in) {
	    $login_form['display'] = false;
	    Template::$meta_tags['refresh'] = '3;URL=index.php';
	    $conf = htmlspecialchars(_('You are already logged in.')).' ';
	    $conf .= sprintf(htmlspecialchars(_('Click %shere%s if you do not automatically redirect.')),'<a href="index.php">','</a>').'<br />';
	    $tpl['confirmation'] = $conf;
	}
	break;
}

Template::setFile('login.tpl');

$tpl['title'] = htmlspecialchars(_('STK Add-ons').' | '._('Login'));
$tpl['login'] = $login_form;

Template::assignments($tpl);

Template::display();
