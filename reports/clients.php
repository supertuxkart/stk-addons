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

define('ROOT','../');
$security = "";
include(ROOT.'include.php');
include(ROOT.'include/Report.class.php');

$report = new Report("STK Add-Ons Client Report");
$description = '<p>This report is designed to give day-to-day usage statistics, '.
    'using download counts, collected daily.</p>';
$report->addDescription($description);

$last30_section = $report->addSection("File Downloads in the Last 30 Days");
$last30_query = 'SELECT `date`,SUM(`value`) AS count
    FROM `'.DB_PREFIX.'stats`
    WHERE `date` >= CURDATE() - INTERVAL 30 DAY
    GROUP BY `date`
    ORDER BY `date` DESC';
$report->addQuery($last30_section,$last30_query);

$last30_section = $report->addSection("File Downloads per Month in the Last 12 Months");
$last30_query = 'SELECT CONCAT(MONTHNAME(`date`),\' \',YEAR(`date`)) AS `month`,SUM(`value`) AS count
    FROM `'.DB_PREFIX.'stats`
    WHERE `date` >= CURDATE() - INTERVAL 12 MONTH
    GROUP BY `month`
    ORDER BY `date` DESC';
$report->addQuery($last30_section,$last30_query);

print($report);
?>
