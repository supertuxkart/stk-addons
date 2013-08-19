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
include_once('config.php');
include_once('include/sql.php');

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
$filepath = UP_LOCATION.$assetpath;

// Don't bother checking if the file exists - if it doesn't exist, you'll get
// a 404 error anyways after redirecting. Yes, this may make the stats below
// inaccurate, but the actual 404's that used to be thrown here were relatively
// rare anyways.

// Check user-agent
$uagent = $_SERVER['HTTP_USER_AGENT'];
if (preg_match('#^(SuperTuxKart/[a-z0-9\.\-_]+)( \\(.*\\))?$#',$uagent,$matches)) {
    // Check if this user-agent is already known
    $checkSql = 'SELECT `agent_string`, `disabled` FROM `'.DB_PREFIX.'clients`
        WHERE `agent_string` = \''.mysql_real_escape_string($matches[1]).'\'';
    $checkHandle = sql_query($checkSql);
    if (mysql_num_rows($checkHandle) != 1)
    {
        // New user-agent. Add it to the database.
        $newSql = 'INSERT INTO `'.DB_PREFIX.'clients`
            (`agent_string`) VALUES (\''.mysql_real_escape_string($matches[1]).'\')';
        $newHandle = sql_query($newSql);
    }
    else
    {
        $checkResult = sql_next($checkHandle);
        if ($checkResult['disabled'] == 1)
        {
            header("HTTP/1.0 404 Not Found");
            exit;
        }
    }
    
    // Increase daily count for this user-agent
    $checkStatQuery = 'SELECT `id`
        FROM `'.DB_PREFIX.'stats`
        WHERE `type` = \'uagent '.mysql_real_escape_string($uagent).'\'
        AND `date` = CURDATE()';
    $checkStatHandle = sql_query($checkStatQuery);
    if (!$checkStatHandle) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }
    if (mysql_num_rows($checkStatHandle) === 0) {
        // Insert new stat record
        $insertStatQuery = 'INSERT INTO `'.DB_PREFIX.'stats`
            (`type`,`date`,`value`) VALUES
            (\'uagent '.mysql_real_escape_string($uagent).'\',CURDATE(),1)';
    } else {
        $insertStatQuery = 'UPDATE `'.DB_PREFIX.'stats`
            SET `value` = `value` + 1
            WHERE `type` = \'uagent '.mysql_real_escape_string($uagent).'\'
            AND `date` = CURDATE()';
    }
    $insertStatHandle = sql_query($insertStatQuery);
    if (!$insertStatHandle) {
        header("HTTP/1.0 404 Not Found");
        exit;
    }
}

// Update download count for addons
$counterQuery = 'CALL `'.DB_PREFIX.'increment_download` (\''.$assetpath.'\')';
$counterHandle = sql_query($counterQuery);

// Redirect to actual resource
if ($dir == 'xml') {
    header('Location: http://stkaddons.net/xml/'.$file);
} else {
    header('Location: http://downloads.tuxfamily.org/stkaddons/assets/'.$assetpath);
}
exit;
?>
