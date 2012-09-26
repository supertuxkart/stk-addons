<?php
/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
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

require_once(ROOT.'include/sql.php');
require_once(ROOT.'include/File.class.php');
require_once(ROOT.'include/User.class.php');

class AccessControl {
    // Define permission levels
    private static $permissions = array(
	'basicUser' => array(
	    'basicPage' => true,
            'addAddon' => true,
            'manageaddons' => false,
            'managebasicUsers' => false,
            'managemoderators' => false,
            'manageadministrators' => false,
            'manageroots' => false,
            'managesettings' => false
	),
	'moderator' => array(
	    'basicPage' => true,
            'addAddon' => true,
            'manageaddons' => true,
            'managebasicUsers' => true,
            'managemoderators' => false,
            'manageadministrators' => false,
            'manageroots' => false,
            'managesettings' => false
	),
	'administrator' => array(
	    'basicPage' => true,
            'addAddon' => true,
            'manageaddons' => true,
            'managebasicUsers' => true,
            'managemoderators' => true,
            'manageadministrators' => false,
            'manageroots' => false,
            'managesettings' => true
	),
	'root' => array(
	    'basicPage' => true,
            'addAddon' => true,
            'manageaddons' => true,
            'managebasicUsers' => true,
            'managemoderators' => true,
            'manageadministrators' => true,
            'manageroots' => true,
            'managesettings' => true
	)
    );
    
    public static function setLevel($accessLevel) {
	$role = User::getRole();
	if (is_null($accessLevel)) return true;
	
	$allow = false;
	if ($role == 'unregistered' && $accessLevel == NULL) {
	    $allow = true;
	} elseif ($role == 'unregistered') {
	    $allow = false;
	} else
	    $allow = AccessControl::$permissions[$role][$accessLevel];
	
	if ($allow === false)
	    AccessControl::showAccessDeniedPage();
    }
    
    public static function showAccessDeniedPage() {
	header('HTTP/1.0 401 Unauthorized');
	Template::setFile('access-denied.tpl');
	$fields = array(
	    'ad_reason' => htmlspecialchars(_('You do not have permission to access this page.')),
	    'ad_action' => htmlspecialchars(_('You will be redirected to the home page.')),
	    'ad_redirect_url' => File::rewrite('index.php')
	);
	
	Template::assignments($fields);
	Template::display();

	exit;
    }
}
?>
