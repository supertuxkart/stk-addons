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

$tpl = StkTemplate::get("stats/page/addons.tpl");

$query_addon_revisions = "SELECT `addon_id`, AT.`name_singular` as `addon_type`, `path`, `date_added`, `downloads`
    FROM `" . DB_PREFIX . "addons` A
    INNER JOIN " . DB_PREFIX . "files F
        ON A.id = F.addon_id
    INNER JOIN " . DB_PREFIX . "addon_types AT
        ON A.`type` = AT.`type`
    WHERE F.`type` = 'addon'
    ORDER BY `addon_id` ASC, `date_added` ASC";

$query_addon_cumulative = "SELECT A.`id`, AT.`name_singular` as `type`, A.`name`, SUM(F.`downloads`) AS `dl_count`
    FROM `" . DB_PREFIX . "addons` A
    INNER JOIN `" . DB_PREFIX . "files` F
        ON A.id = F.addon_id
    INNER JOIN " . DB_PREFIX . "addon_types AT
        ON A.`type` = AT.`type`
    WHERE A.`id` = F.`addon_id`
    AND F.`type` = 'addon'
    GROUP BY A.`id`
    ORDER BY A.`id` ASC";

$query_addon_user = "SELECT A.`id`, AT.`name_singular` as `type`, A.`name`, U.`username` AS `uploader`,
    A.`creation_date`, A.`designer`, A.`description`, A.`license`
    FROM `" . DB_PREFIX . "addons` A
    INNER JOIN `" . DB_PREFIX . "users` U
        ON A.`uploader` = U.`id`
    INNER JOIN " . DB_PREFIX . "addon_types AT
        ON A.`type` = AT.`type`
    ORDER BY A.`id` ASC";

$query_addon_type = "SELECT AT.`name_plural` as `type`, COUNT(`id`) AS `count`
    FROM `" . DB_PREFIX . "addons` A
    INNER JOIN " . DB_PREFIX . "addon_types AT
        ON A.`type` = AT.`type`
    GROUP BY `type`";

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
