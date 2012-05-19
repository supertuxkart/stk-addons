<?php
/**
 * copyright 2012
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

define('ROOT', './');
include_once('config.php');
include_once('include/Addon.class.php');
include_once('include/ClientSession.class.php');
include_once('include/Ratings.class.php');
include_once('include/User.class.php');
$action = (isset($_GET['action'])) ? $_GET['action'] : NULL;
$user_name = (isset($_GET['user'])) ? $_GET['user'] : NULL;
$session_id = (isset($_GET['id'])) ? $_GET['id'] : NULL;

// Print headers
ob_start();
header('Content-type: text/xml');
echo "<?xml version=\"1.0\"?>\n";

// Validate session
try {
    $session = ClientSession::get($session_id, $user_name);
}
catch (Exception $e) {
    ob_clean();
    header('HTTP/1.1 403 Forbidden');
    echo $e->getMessage();
    exit;
}

switch ($action) {
    default:
	ob_clean();
	header('Content-type: text/plain', true, 403);
	echo 'No action.';
	exit;

    // Vote for an add-on
    case 'vote':
	if ($session->getUserId() == 0) {
	    ob_clean();
	    header('Content-type: text/plain', true, 403);
	    echo 'Must be registered to rate an add-on.';
	    exit;
	}

	$addon_id = (isset($_GET['addon_id'])) ? $_GET['addon_id'] : NULL;
	$vote = (isset($_GET['vote'])) ? (int)$_GET['vote'] : NULL;
	if (!Addon::exists($addon_id)) {
	    ob_clean();
	    header('Content-type: text/plain', true, 403);
	    echo 'Add-on not found.';
	    exit;
	}
	if ($vote > 3 || $vote < 1) {
	    ob_clean();
	    header('Content-type: text/plain', true, 403);
	    echo 'Invalid rating.';
	    exit;
	}
	$session->regenerate();
	$rating = new Ratings($addon_id, $session);
	$success = $rating->setClientVote($vote,$session);
	echo '<vote id="'.$session->getSessionId().'" value="'.$rating->getAvgRating().'" addon-id="'.$addon_id.'" success="'.(int)$success.'" />'."\n";
	break;
}

ob_end_flush();
?>
