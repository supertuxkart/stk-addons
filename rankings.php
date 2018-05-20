<?php
/**
 * copyright 2018 SuperTuxKart-Team
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

require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");

$tpl = StkTemplate::get('rankings.tpl')
    ->addDataTablesLibrary()
    ->addScriptInclude("stats.js");

$tpl->assignTitle(_h('Player Rankings'));

$query_rankings = <<<SQL
    SELECT
        FIND_IN_SET(scores,
            (SELECT GROUP_CONCAT(DISTINCT scores ORDER BY scores DESC)
            FROM `{DB_VERSION}_rankings`)
        ) AS Rank,
        username `Username`,
        scores `Scores`,
        max_scores `Maximum scores obtained`,
        num_races_done `Races done`
    FROM `{DB_VERSION}_rankings`
    INNER JOIN `{DB_VERSION}_users` ON `{DB_VERSION}_rankings`.user_id = `{DB_VERSION}_users`.id
    ORDER BY Rank
SQL;

$player_data = [ "sections" => [ Statistic::getSection($query_rankings) ] ];

$tpl->assign("player_rankings", $player_data);

echo $tpl;
