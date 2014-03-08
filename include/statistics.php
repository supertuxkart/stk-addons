<?php
/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
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

require_once(INCLUDE_DIR . 'DBConnection.class.php');

/**
 * Return the most downloaded addon of a given type
 * @param string $addontype
 * @param string $filetype
 * @return string
 */
function stat_most_downloaded($addontype, $filetype = 'addon')
{
    if (!Addon::isAllowedType($addontype)) return false;

    try {
        $download_counts = DBConnection::get()->query(
                'SELECT `addon_id`, SUM(`downloads`) AS `count`
                 FROM `'.DB_PREFIX.'files`
                 WHERE `addon_type` = :addon_type
                 AND `file_type` = :file_type
                 GROUP BY `addon_id`
                 ORDER BY SUM(`downloads`) DESC',
                DBConnection::FETCH_ALL,
                array(
                    ':addon_type' => $addontype,
                    ':file_type' => $filetype
                ));
        if (count($download_counts) === 0) return NULL;
        return $download_counts[0]['addon_id'];
    } catch (DBException $e) {
        return NULL;
    }
}

/**
 * Return the newest addon of a given type
 * @param string $addontype
 * @return string
 */
function stat_newest($addontype) {
    if (!Addon::isAllowedType($addontype)) return NULL;

    try {
        $newest_addon = DBConnection::get()->query(
                'SELECT `a`.`id`
                 FROM `'.DB_PREFIX.'addons` `a`
                 LEFT JOIN `'.DB_PREFIX.$addontype.'_revs` `r`
                 ON `a`.`id` = `r`.`addon_id`
                 WHERE `r`.`status` & '.F_APPROVED.'
                 ORDER BY `a`.`creation_date` DESC 
                 LIMIT 1',
                DBConnection::FETCH_ALL);
        if (count($newest_addon) === 0) return NULL;
        return $newest_addon[0]['id'];
    } catch (DBException $e) {
        return NULL;
    }
}
?>
