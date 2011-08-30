<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
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

/**
 * Manage the newsfeed that is fed to the game
 *
 * @author Stephen
 */
class News {

    public static function refreshDynamicEntries() {
        // Get dynamic entries
        $getQuery = 'SELECT * FROM `'.DB_PREFIX.'news`
            WHERE `dynamic` = 1
            ORDER BY `id` ASC';
        $getHandle = sql_query($getQuery);
        if (!$getHandle)
            return false;
        $numEntries = mysql_num_rows($getHandle);
        // Build array of existing entries
        $entries = array();
        for ($i = 1; $i <= $numEntries; $i++) {
            $entry = mysql_fetch_assoc($getHandle);
            $entries[] = $entry;
        }
        
        // Dynamic newest kart display
        $new_kart = Addon::getName(stat_newest('karts'));
        $existing_id = false;
        foreach ($entries AS $entry) {
            if (preg_match('/^Newest add-on kart: (.*)$/i',$entry['content'], $matches)) {
                if ($matches[1] != $new_kart) {
                    // Delete old record
                    $delQuery = 'DELETE FROM `'.DB_PREFIX.'news`
                        WHERE `id` = '.$entry['id'];
                    $delHandle = sql_query($delQuery);
                    if (!$delHandle)
                        echo 'Warning: failed to delete old news record.<br />';
                } else {
                    $existing_id = true;
                    break;
                }
            }
        }
        // Add new entry
        if ($existing_id === false && $new_kart !== false) {
            $insQuery = 'INSERT INTO `'.DB_PREFIX.'news`
                (`content`,`web_display`,`dynamic`)
                VALUES
                (\'Newest add-on kart: '.mysql_real_escape_string($new_kart).'\',0,1)';
            $insHandle = sql_query($insQuery);
            if (!$insHandle)
                echo 'Failed to insert newest kart news entry.<br />';
        }
        
        // Dynamic newest track display
        $new_track = Addon::getName(stat_newest('tracks'));
        $existing_id = false;
        foreach ($entries AS $entry) {
            if (preg_match('/^Newest add-on track: (.*)$/i',$entry['content'], $matches)) {
                if ($matches[1] != $new_track) {
                    // Delete old record
                    $delQuery = 'DELETE FROM `'.DB_PREFIX.'news`
                        WHERE `id` = '.$entry['id'];
                    $delHandle = sql_query($delQuery);
                    if (!$delHandle)
                        echo 'Warning: failed to delete old news record.<br />';
                } else {
                    $existing_id = true;
                    break;
                }
            }
        }
        // Add new entry
        if ($existing_id === false && $new_track !== false) {
            $insQuery = 'INSERT INTO `'.DB_PREFIX.'news`
                (`content`,`web_display`,`dynamic`)
                VALUES
                (\'Newest add-on track: '.mysql_real_escape_string($new_track).'\',0,1)';
            $insHandle = sql_query($insQuery);
            if (!$insHandle)
                echo 'Failed to insert newest track news entry.<br />';
        }
        
        // Dynamic newest arena display
        $new_arena = Addon::getName(stat_newest('arenas'));
        $existing_id = false;
        foreach ($entries AS $entry) {
            if (preg_match('/^Newest add-on arena: (.*)$/i',$entry['content'], $matches)) {
                if ($matches[1] != $new_arena) {
                    // Delete old record
                    $delQuery = 'DELETE FROM `'.DB_PREFIX.'news`
                        WHERE `id` = '.$entry['id'];
                    $delHandle = sql_query($delQuery);
                    if (!$delHandle)
                        echo 'Warning: failed to delete old news record.<br />';
                } else {
                    $existing_id = true;
                    break;
                }
            }
        }
        // Add new entry
        if ($existing_id === false && $new_arena !== false) {
            $insQuery = 'INSERT INTO `'.DB_PREFIX.'news`
                (`content`,`web_display`,`dynamic`)
                VALUES
                (\'Newest add-on arena: '.mysql_real_escape_string($new_arena).'\',0,1)';
            $insHandle = sql_query($insQuery);
            if (!$insHandle)
                echo 'Failed to insert newest kart news entry.<br />';
        }
    }
}

?>
