<?php
/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
 *
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

$tpl = StkTemplate::get("stats/page/files.tpl");

$query_images = <<<SQL
    SELECT `addon_id`, AT.`name_singular` AS `addon_type`, `path`, DATE(`date_added`) AS `date_added`, 
            `is_approved`, `downloads`
    FROM `{DB_VERSION}_files` F
    INNER JOIN `{DB_VERSION}_addons` A
        ON A.id = F.addon_id
    INNER JOIN `{DB_VERSION}_addon_types` AT
        ON A.`type` = AT.`type`
    WHERE F.`type` = '1'
    ORDER BY `addon_id` ASC, `date_added` ASC
SQL;


$query_source = <<<SQL
    SELECT `addon_id`, AT.`name_singular` AS `addon_type`, `path`, DATE(`date_added`) AS `date_added`, 
            `is_approved`, `downloads`
    FROM `{DB_VERSION}_files` F
    INNER JOIN `{DB_VERSION}_addons` A
        ON A.id = F.addon_id
    INNER JOIN `{DB_VERSION}_addon_types` AT
        ON A.`type` = AT.`type`
    WHERE F.`type` = '2'
    ORDER BY `addon_id` ASC, `date_added` ASC
SQL;

$query_file_downloads_month_30 = <<<SQL
    SELECT `date`, SUM(`value`) AS count
    FROM `{DB_VERSION}_stats`
    WHERE `date` >= CURDATE() - INTERVAL 30 DAY
    GROUP BY `date`
    ORDER BY `date` DESC
SQL;

$query_file_downloads_months_12 = <<<SQL
    SELECT MONTHNAME(`date`) AS `month`, YEAR(`date`) AS `year`, SUM(`value`) AS count
    FROM `{DB_VERSION}_stats`
    WHERE `date` >= CURDATE() - INTERVAL 1 YEAR
    GROUP BY `year`, MONTH(`date`), `month`
    ORDER BY `year` DESC, MONTH(`date`) DESC
SQL;

$file_type_addon = File::ADDON;
$query_downloads_addon_type = <<<SQL
    SELECT AT.`name_plural` AS `addon_type`, SUM(`downloads`)
    FROM `{DB_VERSION}_files` F
    INNER JOIN `{DB_VERSION}_addons` A
        ON A.id = F.addon_id
    INNER JOIN `{DB_VERSION}_addon_types` AT
        ON A.`type` = AT.`type`
    WHERE F.`type` = '$file_type_addon'
    GROUP BY `addon_type`
SQL;


$tpl_data = [
    "sections" => [
        Statistic::getChart(
            $query_downloads_addon_type,
            Statistic::CHART_PIE,
            "File Downloads (by add-on type)",
            "files_pie"
        ),
        Statistic::getSection($query_file_downloads_month_30, "File Downloads in the Last 30 Days"),
        Statistic::getSection($query_file_downloads_months_12, "File Downloads per Month in the Last 12 Months"),
        Statistic::getSection($query_images, "Images"),
        Statistic::getSection($query_source, "Source")
    ],
];

$tpl->assign("files", $tpl_data);
echo $tpl;
