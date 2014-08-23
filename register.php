<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2012-2014 Stephen Just <stephenjust@users.sf.net>
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
require_once(ROOT.'config.php');
require_once(INCLUDE_DIR.'StkTemplate.class.php');

$_POST['user'] = (empty($_POST['user'])) ? NULL : $_POST['user'];
$_POST['name'] = (empty($_POST['name'])) ? NULL : $_POST['name'];
$_POST['mail'] = (empty($_POST['mail'])) ? NULL : $_POST['mail'];

$tpl = new StkTemplate('register.tpl');
$tpl->assign('title', htmlspecialchars(_('STK Add-ons').' | '._('Register')));

$terms_text = '=== '.htmlspecialchars(_('STK Addons Terms and Conditions'))." ===\n\n".
htmlspecialchars(_('You must agree to these terms in order to upload content to the STK Addons site.'))."\n\n".
_('The STK Addons service is designed to be a repository exclusively for Super
Tux Kart addon content. All uploaded content must be intended for this
purpose. When you upload your content, it will be available publicly on the
internet, and will be made available in-game for download.')."\n\n".
htmlspecialchars(_('Super Tux Kart aims to comply with the Debian Free Software Guidelines (DFSG).
TuxFamily.org also requires that content they host comply with open licenses.
You may not upload content which is locked down with a restrictive license.
Licenses such as CC-BY-SA 3.0, or other DFSG-compliant licenses are required.
All content taken from third-party sources must be attributed properly, and must
also be available under an open license. Licenses and attribution should be
included in a "license.txt" file in each uploaded archive. Uploads without
proper licenses or attribution may be deleted without warning.'))."\n\n".
htmlspecialchars(_('Even with valid licenses and attribution, content may not contain any
of the following:'))."\n".
'    1. '.htmlspecialchars(_('Profanity'))."\n".
'    2. '.htmlspecialchars(_('Explicit images'))."\n".
'    3. '.htmlspecialchars(_('Hateful messages and/or images'))."\n".
'    4. '.htmlspecialchars(_('Any other content that may be unsuitable for children'))."\n".
htmlspecialchars(_('If any of your uploads are found to contain any of the above, your upload
will be removed, your account may be removed, and any other content you uploaded
may be removed.'))."\n\n".
htmlspecialchars(_('By checking the box below, you are confirming that you understand these
terms. If you have any questions or comments regarding these terms, one of the
members of the development team would gladly assist you.'));

$register = array(
    'heading' => htmlspecialchars(_('Account Registration')),
    'display_form' => false,
    'form' => array(
        'start' => '<form id="register" action="register.php?action=reg" method="POST">',
        'end' => '</form>',
        'username' => array(
            'label' => '<label for="reg_user">'.htmlspecialchars(_('Username:')).'</label>',
            'requirement' => htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'3')),
            'field' => '<input type="text" name="user" id="reg_user" value="'.htmlspecialchars($_POST['user']).'" />'
        ),
        'password' => array(
            'label' => '<label for="reg_pass">'.htmlspecialchars(_('Password:')).'</label>',
            'requirement' => htmlspecialchars(sprintf(_('Must be at least %d characters long.'),'8')),
            'field' => '<input type="password" name="pass1" id="reg_pass" />'
        ),
        'password_conf' => array(
            'label' => '<label for="reg_pass2">'.htmlspecialchars(_('Password (confirm):')).'</label>',
            'field' => '<input type="password" name="pass2" id="reg_pass2" />'
        ),
        'name' => array(
            'label' => '<label for="reg_name">'.htmlspecialchars(_('Name:')).'</label>',
            'field' => '<input type="text" name="name" id="reg_name" value="'.htmlspecialchars($_POST['name']).'" />'
        ),
        'email' => array(
            'label' => '<label for="reg_email">'.htmlspecialchars(_('Email Address:')).'</label>',
            'requirement' => htmlspecialchars(_('Email address used to activate your account.')),
            'field' => '<input type="text" name="mail" id="reg_email" value="'.htmlspecialchars($_POST['mail']).'" />'
        ),
        'terms' => array(
            'label' => '<label for="reg_terms">'.htmlspecialchars(_('Terms:')).'</label>',
            'field' => '<textarea rows="10" cols="80" readonly id="reg_terms">'.$terms_text.'</textarea>'
        ),
        'terms_agree' => array(
            'label' => '<label for="reg_check">'.htmlspecialchars(_('I agree to the above terms')).'</label>',
            'field' => '<input type="checkbox" name="terms" id="reg_check" />'
        ),
        'submit' => '<input type="submit" value="'.htmlspecialchars(_('Register!')).'" />'
    )
);


// define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : NULL;

switch ($_GET['action']) {
    default:
        $register['display_form'] = true;
        break;

    case 'reg':
        // Register new account
        try
        {
            if (!isset($_POST['terms'])) $_POST['terms'] = NULL;
            User::register($_POST['user'],
                            $_POST['pass1'],
                            $_POST['pass2'],
                            $_POST['mail'],
                            $_POST['name'],
                            $_POST['terms']);
            $tpl->assign('confirmation', htmlspecialchars(_("Account creation was successful. Please activate your account using the link emailed to you.")));
        }
        catch (UserException $e)
        {
            $tpl->assign('errors', $e->getMessage());
            $register['display_form'] = true;
        }
        break;

    case 'valid':
        try {
            $username = strip_tags($_GET['user']);
            $verification_code = strip_tags($_GET['num']);
            User::activate($username,$verification_code);
            $tpl->assign('confirmation', htmlspecialchars(_('Your account has been activated.')));
        }
        catch (UserException $e) {
            $tpl->assign('errors', $e->getMessage());
            $tpl->assign('confirmation', htmlspecialchars(_('Could not validate your account. The link you followed is not valid.')));
        }
        break;
}
$tpl->assign('register', $register);

echo $tpl;
?>
