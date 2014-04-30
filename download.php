<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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
require_once('config.php');
require_once(INCLUDE_DIR . 'DBConnection.class.php');

$dir = $_GET['type'];
$file = $_GET['file'];
// Make sure directory is not unsafe
if (!preg_match('/^[a-z]+$/i',$dir))
{
    // Directory is unsafe - throw a 404 error
    header("HTTP/1.0 404 Not Found");
    exit;
}
// Make sure file name is not unsafe
if (!preg_match('/^[\w\-\ ]+\.[a-z0-9]+$/i',$file))
{
    // File is unsafe - throw a 404 error
    header("HTTP/1.0 404 Not Found");
    exit;
}

if ($dir != 'assets')
    $assetpath = $dir.'/'.$file;
else
    $assetpath = $file;

// Don't bother checking if the file exists - if it doesn't exist, you'll get
// a 404 error anyways after redirecting. Yes, this may make the stats below
// inaccurate, but the actual 404's that used to be thrown here were relatively
// rare anyways.

// Check user-agent
$uagent = $_SERVER['HTTP_USER_AGENT'];
$matches = array();
if (preg_match('#^(SuperTuxKart/[a-z0-9\\.\\-_]+)( \\(.*\\))?$#',$uagent,&$matches)) {
    try {
        DBConnection::get()->query(
                'INSERT IGNORE INTO `'.DB_PREFIX.'clients`
                 (`agent_string`)
                 VALUES
                 (:uagent)',
                DBConnection::NOTHING,
                array(':uagent' => $matches[1]));
    } catch (DBException $e) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }
    
    // Increase daily count for this user-agent
    try {
        DBConnection::get()->query(
            'INSERT INTO `'.DB_PREFIX.'stats`
             (`type`,`date`,`value`)
             VALUES
             (:type, CURDATE(), 1)
             ON DUPLICATE KEY UPDATE
             `value` = `value` + 1',
            DBConnection::NOTHING,
            array(':type' => 'uagent '.$uagent));
    } catch (DBException $e) {
        header("HTTP/1.0 404 Not Found");
        echo 'Failed to update statistics';
        exit;
    }
}

// Update download count for addons
try {
    DBConnection::get()->query('CALL `'.DB_PREFIX.'increment_download` (:path)',
            DBConnection::NOTHING, array(':path' => $assetpath));
} catch (DBException $e) {
    // Do nothing
}

// Redirect to actual resource
if ($dir == 'xml') {
    header('Location: http://stkaddons.net/xml/'.$file);
} else {
    header('Location: http://downloads.tuxfamily.org/stkaddons/assets/'.$assetpath);
}
exit;
