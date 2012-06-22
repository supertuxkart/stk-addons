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

$error_code = (!isset($_GET['e'])) ? NULL : $_GET['e'];

// Send appropriate error header
switch ($error_code) {
    default:
	break;

    case '403':
	header('HTTP/1.1 403 Forbidden');
	break;
    case '404':
	header('HTTP/1.1 404 Not Found');
	break;
}

define('ROOT','./');
$security ="";
require('include.php');
include('include/top.php');
echo '</head><body>';
include('include/menu.php');
echo '<div id="error-container">';
// by Bryan Lunduke [lunduke.com]? Not sure of original source.
echo '<img src="'.SITE_ROOT.'image/tux-sad.png" alt="Sad Tux" width="200" height="160" />';

switch ($error_code) {
    default:
	echo '<h1>An Error Occurred</h1>';
	echo '<p>Something broke! We\'ll try to fix it as soon as we can!</p>';
	break;
    case '403':
	printf('<h1>%s</h1>',htmlspecialchars(_('403 - Forbidden')));
	printf('<p>%s</p>',
		htmlspecialchars(_('You\'re not supposed to be here. Click one of the links in the menu above to find some better content.')));
	break;
    case '404':
	printf('<h1>%s</h1>',htmlspecialchars(_('404 - Not Found')));
	printf('<p>%s</p>',
		htmlspecialchars(_('We can\'t find what you are looking for. The link you followed may be broken.')));
	break;
}

echo '</div>';
include('include/footer.php');
?>
