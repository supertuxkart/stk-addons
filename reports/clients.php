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

$report = new Report("STK Add-Ons Client Report");
$description = '<p>This report is designed to give day-to-day usage statistics, ' .
    'using download counts, collected daily.</p>';
$report->addDescription($description);

$uaVer1y_section = $report->addSection("File Downloads per Version in the Last Year");
$uaVer1y_query = 'SELECT `label`,`date`,SUM(`value`) FROM (
        SELECT SUBSTRING_INDEX(SUBSTRING(`type`,21),\' \',1) AS `label`,`date`,`value`
        FROM `' . DB_PREFIX . 'stats`
        WHERE `date` >= CURDATE() - INTERVAL 1 YEAR
        AND `type` LIKE \'uagent %\'
        ORDER BY `date` DESC
    ) AS `t`
    GROUP BY `t`.`date`,`t`.`label`
    ORDER BY `t`.`date` DESC, `t`.`label` DESC';
$report->addTimeGraph($uaVer1y_section, $uaVer1y_query, 'File Downloads per Version', 'ua_ver_1y');

$uaTime1y_section = $report->addSection("File Downloads per OS in the Last Year");
$uaTime1y_query = 'SELECT CASE WHEN `label` = \'\' THEN \'Unknown\' ELSE `label` END AS `label`,`date`,SUM(`value`) FROM (
        SELECT TRIM(REPLACE(REPLACE(REPLACE(`type`,SUBSTRING_INDEX(`type`,\' \',2),\'\'),\')\',\'\'),\'(\',\'\'))
        AS `label`,`date`,`value`
        FROM `' . DB_PREFIX . 'stats`
        WHERE `date` >= CURDATE() - INTERVAL 1 YEAR
        AND `type` LIKE \'uagent %\'
        ORDER BY `date` DESC
    ) AS `t`
    GROUP BY `t`.`date`,`t`.`label`
    ORDER BY `t`.`date` DESC, `t`.`label` DESC;';
$report->addTimeGraph($uaTime1y_section, $uaTime1y_query, 'File Downloads per OS', 'ua_time_1y');

echo $report;
