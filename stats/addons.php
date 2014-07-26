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

$tpl = StkTemplate::get("stats-addons.tpl");

$query_addon_revisions = "SELECT `addon_id`, `addon_type`, `file_path`, `date_added`, `downloads`
    FROM `" . DB_PREFIX . "files`
    WHERE `file_type` = 'addon'
    ORDER BY `addon_id` ASC, `date_added` ASC";

$query_addon_cumulative = "SELECT `a`.`id`, `a`.`type`, `a`.`name`, SUM(`f`.`downloads`) AS `dl_count`
    FROM `" . DB_PREFIX . "addons` `a`, `" . DB_PREFIX . "files` `f`
    WHERE `a`.`id` = `f`.`addon_id`
    AND `f`.`file_type` = 'addon'
    GROUP BY `a`.`id`
    ORDER BY `a`.`id` ASC";

$query_addon_user = "SELECT `a`.`id`, `a`.`type`, `a`.`name`, `u`.`name` AS `uploader`,
    `a`.`creation_date`, `a`.`designer`, `a`.`description`, `a`.`license`
    FROM `" . DB_PREFIX . "addons` `a`
    LEFT JOIN `" . DB_PREFIX . "users` `u`
    ON `a`.`uploader` = `u`.`id`
    ORDER BY `a`.`id` ASC";

$query_addon_type = "SELECT `type`, COUNT(`id`) AS `count` FROM `" . DB_PREFIX . "addons` GROUP BY `type`";

$tplData = [
    "sections" => [
        Statistic::getChart($query_addon_type, Statistic::CHART_PIE, "Add-On Types", "addon_type_pie"),
        Statistic::getSection($query_addon_revisions, "Add-Ons (by revision)"),
        Statistic::getSection($query_addon_cumulative, "Add-Ons Cumulative Downloads"),
        Statistic::getSection($query_addon_user, "Add-Ons - user combination")
    ],
];

$tpl->assign("addons", $tplData);
echo $tpl;
