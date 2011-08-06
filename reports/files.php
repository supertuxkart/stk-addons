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

$report = new Report("STK Add-Ons File Report");
$description = '<p>This report contains a list of all addon package files, and '
 .'their download counts. Add-ons that appear more than once in each list have '
 .'more than one revision or more than one file uploaded. Files with blank '
 .'dates were uploaded before that field was added.</p>';
$report->addDescription($description);

$addon_section = $report->addSection("Add-Ons");
$addon_query = 'SELECT `addon_id`,`addon_type`,`file_path`,`date_added`,`downloads`
    FROM `'.DB_PREFIX.'files`
    WHERE `file_type` = \'addon\'
    ORDER BY `addon_id` ASC, `date_added` ASC';
$report->addQuery($addon_section,$addon_query);

$image_section = $report->addSection("Images");
$image_query = 'SELECT `addon_id`,`addon_type`,`file_path`,`date_added`,`approved`,`downloads`
    FROM `'.DB_PREFIX.'files`
    WHERE `file_type` = \'image\'
    ORDER BY `addon_id` ASC, `date_added` ASC';
$report->addQuery($image_section,$image_query);

print($report);
?>
