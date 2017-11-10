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
declare(strict_types=1);
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

$tpl = StkTemplate::get("stats/page/clients.tpl");

$query_version = <<<SQL
    SELECT `label`, `date`, SUM(`value`) FROM (
        SELECT SUBSTRING_INDEX(SUBSTRING(`type`, 21), ' ', 1) AS `label`, `date`, `value`
        FROM `{DB_VERSION}_stats`
        WHERE `date` >= CURDATE() - INTERVAL 1 YEAR
        AND `type` LIKE 'uagent %'
        ORDER BY `date` DESC
    ) AS `t`
    GROUP BY `t`.`date`, `t`.`label`
    ORDER BY `t`.`date` DESC, `t`.`label` DESC
SQL;

$query_time = <<<SQL
    SELECT CASE WHEN `label` = '' THEN 'Unknown' ELSE `label` END AS `label`, `date`, SUM(`value`) FROM (
            SELECT TRIM(REPLACE(REPLACE(REPLACE(`type`, SUBSTRING_INDEX(`type`,' ',2),''),')',''),'(',''))
            AS `label`, `date`, `value`
            FROM `{DB_VERSION}_stats`
            WHERE `date` >= CURDATE() - INTERVAL 1 YEAR
            AND `type` LIKE 'uagent %'
            ORDER BY `date` DESC
        ) AS `t`
    GROUP BY `t`.`date`,`t`.`label`
    ORDER BY `t`.`date` DESC, `t`.`label` DESC
SQL;

$tpl_data = [
    "sections" => [
        Statistic::getChart($query_version, Statistic::CHART_TIME, "File Downloads per Version in the Last Year", "downloads_version_year"),
        Statistic::getChart($query_time, Statistic::CHART_TIME, "File Downloads per OS in the Last Year", "downloads_os_year")
    ]
];

$tpl->assign("clients", $tpl_data);
echo $tpl;
