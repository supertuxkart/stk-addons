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

require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

$report = new Report("STK Add-Ons Add-On Records Report");
$description = '<p>This report contains a list of all add-ons.</p>';
$report->addDescription($description);

$addon_section = $report->addSection("Add-Ons");
$chart_query = 'SELECT `type`, COUNT(`id`) AS `count`
    FROM `' . DB_PREFIX . 'addons` GROUP BY `type`';
$report->addPieChart($addon_section, $chart_query, 'Add-On Types', 'ar_pie');

$addon_query = 'SELECT `a`.`id`,`a`.`type`,`a`.`name`,`u`.`name` AS `uploader`,
    `a`.`creation_date`,`a`.`designer`,`a`.`description`,`a`.`license` 
    FROM `' . DB_PREFIX . 'addons` `a`
    LEFT JOIN `' . DB_PREFIX . 'users` `u`
    ON `a`.`uploader` = `u`.`id`
    ORDER BY `a`.`id` ASC';
$report->addQuery($addon_section, $addon_query);

echo $report;
