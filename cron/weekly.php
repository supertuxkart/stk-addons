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

define('ROOT','./web/');
require (ROOT.'include.php');

log_email();

function log_email() {
    $query = 'SELECT `l`.`date`,`l`.`user`,`l`.`message`,`u`.`name`
            FROM `'.DB_PREFIX.'logs` `l`
            LEFT JOIN `'.DB_PREFIX.'users` `u`
            ON `l`.`user` = `u`.`id`
            WHERE `l`.`emailed` = 0
            ORDER BY `l`.`date` DESC';
    $handle = sql_query($query);
    if (!$handle)
        throw new Exception('Query error in log_email(): '.mysql_error());
    
    if (mysql_num_rows($handle) == 0) {
        print "No new log messages to email.\n";
        return;
    }
    
    $table = '<table><thead><tr><th>Date</th><th>User</th><th>Description</th></tr></thead><tbody>';
    for ($i = 0; $i < mysql_num_rows($handle); $i++) {
        $result = mysql_fetch_assoc($handle);
        $table .= '<tr><td>'.$result['date'].'</td><td>'.$result['name'].'</td><td>'.$result['message'].'</td></tr>';
    }
    $table .= '</tbody></table>';
    
    $content = 'The following events have occurred in the last 7 days:<br />'.$table;
    
    moderator_email('Weekly log update',$content);
    
    // Set emailed flag to true
    $set_mailed_query = 'UPDATE `'.DB_PREFIX.'logs` SET `emailed` = 1';
    $set_mailed_handle = sql_query($set_mailed_query);
    if (!$set_mailed_handle)
        throw new Exception('Failed to mark log messages as mailed.');
    
    print "Sent log message email.\n";
}
?>
