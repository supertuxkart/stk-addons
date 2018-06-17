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

/**
 * Class Ranking
 */
class Ranking
{
    /**
     * Get top n players based on ranking scores (called by everyone)
     *
     * @param int $topn
     *
     * @return array list of player infos with rank in array
     */
    public static function getTopPlayersFromRanking($topn)
    {
        try
        {
            $result = DBConnection::get()->query(
                "SELECT
                    FIND_IN_SET(scores,
                        (SELECT GROUP_CONCAT(DISTINCT scores ORDER BY scores DESC)
                        FROM `{DB_VERSION}_rankings`)
                    ) AS rank,
                    user_id,
                    username,
                    scores,
                    max_scores,
                    num_races_done
                FROM `{DB_VERSION}_rankings`
                INNER JOIN `{DB_VERSION}_users` ON `{DB_VERSION}_rankings`.user_id = `{DB_VERSION}_users`.id
                ORDER BY rank LIMIT :topn",
                DBConnection::FETCH_ALL,
                [':topn' => $topn],
                [':topn' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            return [];
        }
        if (!$result)
        {
            return [];
        }
        return $result;
    }

    /**
     * Get initial ranking for all players
     *
     * @return array initial ranking with scores data in array
     */
    public static function getInitialRanking()
    {
        return
            [
                'rank' => -1,
                'scores' => 2000.0,
                'max_scores' => 2000.0,
                'num_races_done' => 0
            ];
    }

    /**
     * Get ranking of a player (called by everyone)
     *
     * @param int $user_id
     *
     * @return array player info with rank
     */
    public static function getRanking($user_id)
    {
        try
        {
            $result = DBConnection::get()->query(
                "SELECT
                    FIND_IN_SET(scores,
                        (SELECT GROUP_CONCAT(DISTINCT scores ORDER BY scores DESC)
                        FROM `{DB_VERSION}_rankings`)
                    ) AS rank,
                    user_id,
                    scores,
                    max_scores,
                    num_races_done
                FROM `{DB_VERSION}_rankings`
                WHERE `user_id` = :user_id",
                DBConnection::FETCH_FIRST,
                [':user_id' => $user_id],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            return static::getInitialRanking();
        }
        if (!$result)
        {
            return static::getInitialRanking();
        }
        return $result;
    }

    /**
     * Reset ranking of all players (called only by user with PERM_SUMBIT_RANKINGS)
     *
     * @param array $user_permissions Permissions from user
     *
     * @throws RankingException
     */
    public static function resetRanking($user_permissions)
    {
        if (!in_array(AccessControl::PERM_SUMBIT_RANKINGS, $user_permissions))
        {
            throw new RankingException("Invalid user to reset ranking", ErrorType::USER_INVALID_PERMISSION);
        }
        try
        {
            DBConnection::get()->query("DELETE FROM `{DB_VERSION}_rankings`");
        }
        catch (DBException $e)
        {
            throw new RankingException(exception_message_db(_('reset ranking')));
        }
    }

    /**
     * Submit new ranking of a player (called only by user with PERM_SUMBIT_RANKINGS)
     *
     * @param array $user_permissions Permissions from user
     * @param int $id_for_ranked
     * @param double $new_scores
     * @param double $new_max_scores
     * @param int $new_num_races_done
     *
     * @throws RankingException
     */
    public static function submitRanking($user_permissions, $id_for_ranked, $new_scores, $new_max_scores,
                                         $new_num_races_done)
    {
        if (!in_array(AccessControl::PERM_SUMBIT_RANKINGS, $user_permissions))
        {
            throw new RankingException("Invalid user to sumbit ranking", ErrorType::USER_INVALID_PERMISSION);
        }
        try
        {
            DBConnection::get()->query(
                "INSERT INTO `{DB_VERSION}_rankings` (user_id, scores,
                max_scores, num_races_done) VALUES (:user_id, :scores, :max_scores, :num_races_done)
                ON DUPLICATE KEY UPDATE `scores` = :scores,
                `max_scores` = :max_scores, `num_races_done`= :num_races_done",
                DBConnection::NOTHING,
                [
                    ':user_id' => $id_for_ranked,
                    ':scores' => $new_scores,
                    ':max_scores' => $new_max_scores,
                    ':num_races_done' => $new_num_races_done
                ],
                [
                    ':user_id' => DBConnection::PARAM_INT,
                    ':scores' => DBConnection::PARAM_STR,
                    ':max_scores' => DBConnection::PARAM_STR,
                    ':num_races_done' => DBConnection::PARAM_INT
                ]
            );
        }
        catch (DBException $e)
        {
            throw new RankingException(exception_message_db(_('sumbit ranking')));
        }
    }
}
