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

function stat_most_downloaded($addontype, $filetype = 'addon')
{
    if ($addontype != 'karts' && $addontype != 'tracks')
        return false;

    $query = 'SELECT `addon_id`, `downloads`
        FROM `'.DB_PREFIX.'files`
        WHERE `addon_type` = \''.$addontype.'\'
        AND `file_type` = \''.$filetype.'\'
        ORDER BY `addon_id` ASC';
    $handle = sql_query($query);
    if (!$handle)
        return false;
    if (mysql_num_rows($handle) == 0)
        return false;

    // Tabulate the download counts for each addon
    $dl_counts = array();
    for ($i = 0; $i < mysql_num_rows($handle); $i++)
    {
        $result = mysql_fetch_assoc($handle);
        if (!array_key_exists($result['addon_id'],$dl_counts))
            $dl_counts[$result['addon_id']] = 0;
        $dl_counts[$result['addon_id']] += $result['downloads'];
    }
    // Sort the results
    arsort($dl_counts);
    $sorted_keys = array_keys($dl_counts);
    return $sorted_keys[0];
}

function stat_newest($addontype) {
    if ($addontype != 'karts'
            && $addontype != 'tracks'
            && $addontype != 'arenas')
        return false;

    $query = 'SELECT `a`.`id`
        FROM `'.DB_PREFIX.'addons` `a`
        LEFT JOIN `'.DB_PREFIX.$addontype.'_revs` `r`
        ON `a`.`id` = `r`.`addon_id`
        WHERE `r`.`status` & '.F_APPROVED.'
        ORDER BY `a`.`creation_date` DESC 
        LIMIT 1';
    $handle = sql_query($query);
    if (!$handle)
        return false;
    if (mysql_num_rows($handle) !== 1)
        return false;
    $result = mysql_fetch_assoc($handle);
    return $result['id'];
}
?>
