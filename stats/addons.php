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

$tpl = StkTemplate::get("stats/page/addons.tpl");
$file_type_addon = File::ADDON;

$query_addon_revisions = <<<SQL
    SELECT `addon_id`, AT.`name_singular` as `addon_type`, `path`, `date_added`, `downloads`
    FROM `{DB_VERSION}_addons` A
    INNER JOIN `{DB_VERSION}_files` F
        ON A.id = F.addon_id
    INNER JOIN `{DB_VERSION}_addon_types` AT
        ON A.`type` = AT.`type`
    WHERE F.`type` = '$file_type_addon'
    ORDER BY `addon_id` ASC, `date_added` ASC
SQL;

$query_addon_cumulative = <<<SQL
    SELECT A.`id`, AT.`name_singular` as `type`, A.`name`, SUM(F.`downloads`) AS `dl_count`
    FROM `{DB_VERSION}_addons` A
    INNER JOIN `{DB_VERSION}_files` F
        ON A.id = F.addon_id
    INNER JOIN `{DB_VERSION}_addon_types` AT
        ON A.`type` = AT.`type`
    WHERE A.`id` = F.`addon_id`
    AND F.`type` = '$file_type_addon'
    GROUP BY A.`id`
    ORDER BY A.`id` ASC
SQL;

$query_addon_user = <<<SQL
    SELECT A.`id`, AT.`name_singular` as `type`, A.`name`, U.`username` AS `uploader`,
        A.`creation_date`, A.`designer`, A.`description`, A.`license`
    FROM `{DB_VERSION}_addons` A
    INNER JOIN `{DB_VERSION}_users` U
        ON A.`uploader` = U.`id`
    INNER JOIN `{DB_VERSION}_addon_types` AT
        ON A.`type` = AT.`type`
    ORDER BY A.`id` ASC
SQL;

$query_addon_type = <<<SQL
    SELECT AT.`name_plural` as `type`, COUNT(`id`) AS `count`
    FROM `{DB_VERSION}_addons` A
    INNER JOIN `{DB_VERSION}_addon_types` AT
        ON A.`type` = AT.`type`
    GROUP BY `type`
SQL;

$tpl_data = [
    "sections" => [
        Statistic::getChart($query_addon_type, Statistic::CHART_PIE, "Add-On Types", "addon_type_pie"),
        Statistic::getSection($query_addon_revisions, "Add-Ons (by revision)"),
        Statistic::getSection($query_addon_cumulative, "Add-Ons Cumulative Downloads"),
        Statistic::getSection($query_addon_user, "Add-Ons - user combination")
    ],
];

$tpl->assign("addons", $tpl_data);
echo $tpl;
