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

// disconnects is a 64bit bitflag,
// divide it with min(num_races_done, 64) for races done < 64 to have correct value
$query_rankings = <<<SQL
    SELECT
        IF (@score=s.scores, @rank:=@rank, @rank:=@rank+1)
        `Rank`,
        username `Username`,
        ROUND(@score:=s.scores,2) `Scores`,
        ROUND(max_scores, 2) `Maximum scores obtained`,
        num_races_done `Races done`,
        ROUND(rating_deviation, 2) `Rating Deviation`,
        concat(ROUND(BIT_COUNT(disconnects) /
        CAST(LEAST(num_races_done, 64) AS DOUBLE) * 100.0 , 2), '%') `Disconnection rate`
    FROM `{DB_VERSION}_rankings` s
    INNER JOIN `{DB_VERSION}_users` ON user_id = `{DB_VERSION}_users`.id,
    (SELECT @score:=0, @rank:=0) r
    ORDER BY Scores DESC
SQL;

$player_data = [ "sections" => [ Statistic::getSection($query_rankings) ] ];

$tpl->assign("player_rankings", $player_data);

echo $tpl;
