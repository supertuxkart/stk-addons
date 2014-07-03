<?php
/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
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
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

$tpl = StkTemplate::get("stats-files.tpl");

$query_images = "SELECT `addon_id`,`addon_type`,`file_path`,`date_added`,`approved`,`downloads`
    FROM `" . DB_PREFIX . "files`
    WHERE `file_type` = 'image'
    ORDER BY `addon_id` ASC, `date_added` ASC";

$query_source = "SELECT `addon_id`,`addon_type`,`file_path`,`date_added`,`approved`,`downloads`
    FROM `" . DB_PREFIX . "files`
    WHERE `file_type` = 'source'
    ORDER BY `addon_id` ASC, `date_added` ASC";

$query_file_downloads_month_30 = "SELECT `date`,SUM(`value`) AS count
    FROM `" . DB_PREFIX . "stats`
    WHERE `date` >= CURDATE() - INTERVAL 30 DAY
    GROUP BY `date`
    ORDER BY `date` DESC";

$query_file_downloads_months_12 = "SELECT CONCAT(MONTHNAME(`date`), ' ', YEAR(`date`)) AS `month`, SUM(`value`) AS count
    FROM `" . DB_PREFIX . "stats`
    WHERE `date` >= CURDATE() - INTERVAL 12 MONTH
    GROUP BY `month`
    ORDER BY `date` DESC";

$query_downloads_addon_type = "SELECT `addon_type`,SUM(`downloads`)
    FROM `" . DB_PREFIX . "files`
    WHERE `file_type` = 'addon'
    GROUP BY `addon_type`";

$tplData = array(
    "sections" => array(
        Statistic::getChart($query_downloads_addon_type, Statistic::CHART_PIE, "File Downloads (by add-on type)", "files_pie"),
        Statistic::getSection($query_file_downloads_month_30, "File Downloads in the Last 30 Days"),
        Statistic::getSection($query_file_downloads_months_12, "File Downloads per Month in the Last 12 Months"),
        Statistic::getSection($query_images, "Images"),
        Statistic::getSection($query_source, "Source")
    ),
);

$tpl->assign("files", $tplData);
echo $tpl;
