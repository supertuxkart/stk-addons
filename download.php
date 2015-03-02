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

$file = isset($_GET['file']) ? $_GET['file'] : null;

$assets_path = filter_var($file, FILTER_SANITIZE_URL);

// TODO probably the best solutions is not to redirect to the file, but instead output the file from here
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
        http_response_code(404);
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
        http_response_code(404);
        exit('Failed to update statistics');
    }
}

// Update download count for addons
try
{
    DBConnection::get()->query(
        "UPDATE `" . DB_PREFIX . "files`
        SET `downloads` = `downloads` + 1
        WHERE `file_path` = :path
        ",
        DBConnection::NOTHING,
        [':path' => $assets_path]
    );
}
catch(DBException $e)
{
    http_response_code(404);
    exit;
}

// Redirect to actual resource,
header('Location: ' . ROOT_LOCATION . 'downloads/' . $assets_path);
exit;
