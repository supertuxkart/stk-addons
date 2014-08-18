<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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

$dir = isset($_GET['type']) ? $_GET['type'] : null;
$file = isset($_GET['file']) ? $_GET['file'] : null;

// Make sure directory is not unsafe
if (!preg_match('/^[a-z]+$/i', $dir))
{
    // Directory is unsafe - throw a 404 error
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Make sure file name is not unsafe
if (!preg_match('/^[\w\-\ ]+\.[a-z0-9]+$/i', $file))
{
    // File is unsafe - throw a 404 error
    header("HTTP/1.0 404 Not Found");
    exit;
}

if ($dir !== 'assets')
{
    $assets_path = $dir . '/' . $file;
}
else
{
    $assets_path = $file;
}

// Don't bother checking if the file exists - if it doesn't exist, you'll get
// a 404 error anyways after redirecting. Yes, this may make the stats below
// inaccurate, but the actual 404's that used to be thrown here were relatively
// rare anyways.

// Check user-agent
$user_agent = $_SERVER['HTTP_USER_AGENT'];
$matches = [];
if (preg_match('#^(SuperTuxKart/[a-z0-9\\.\\-_]+)( \\(.*\\))?$#', $user_agent, $matches))
{
    try
    {
        DBConnection::get()->query(
            'INSERT IGNORE INTO `' . DB_PREFIX . 'clients`
            (`agent_string`)
            VALUES
            (:uagent)',
            DBConnection::NOTHING,
            [':uagent' => $matches[1]]
        );
    }
    catch(DBException $e)
    {
        header("HTTP/1.0 404 Not Found");
        exit;
    }

    // Increase daily count for this user-agent
    try
    {
        DBConnection::get()->query(
            'INSERT INTO `' . DB_PREFIX . 'stats`
             (`type`,`date`,`value`)
             VALUES
             (:type, CURDATE(), 1)
             ON DUPLICATE KEY UPDATE
             `value` = `value` + 1',
            DBConnection::NOTHING,
            [':type' => 'uagent ' . $user_agent]
        );
    }
    catch(DBException $e)
    {
        header("HTTP/1.0 404 Not Found");
        exit('Failed to update statistics');
    }
}

// Update download count for addons
try
{
    DBConnection::get()->query(
        'CALL `' . DB_PREFIX . 'increment_download` (:path)',
        DBConnection::NOTHING,
        [':path' => $assets_path]
    );
}
catch(DBException $e)
{
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Redirect to actual resource, FIXME
//if ($dir === 'xml')
//{
//    header('Location: http://stkaddons.net/xml/' . $file);
//}
//else
//{
//    header('Location: http://downloads.tuxfamily.org/stkaddons/assets/' . $assetpath);
//}
exit;
