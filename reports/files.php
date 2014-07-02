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

$report = new Report("STK Add-Ons File Report");
$description = '<p>This report contains a list of all addon package files, and '
    . 'their download counts. Add-ons that appear more than once in each list have '
    . 'more than one revision or more than one file uploaded. Files with blank '
    . 'dates were uploaded before that field was added.</p>';
$report->addDescription($description);

$chart_section = $report->addSection("File Downloads (by add-on type)");
$chart_query = 'SELECT `addon_type`,SUM(`downloads`)
    FROM `' . DB_PREFIX . 'files`
    WHERE `file_type` = \'addon\'
    GROUP BY `addon_type`';
$report->addPieChart($chart_section, $chart_query, 'Downloads by Type', 'files_pie');

echo $report;
