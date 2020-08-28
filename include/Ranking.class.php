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
                    IF (@score=s.scores, @rank:=@rank, @rank:=@rank+1) rank,
                    user_id,
                    username,
                    @score:=s.scores scores, max_scores,
                    num_races_done,
                    raw_scores,
                    rating_deviation,
                    disconnects
                FROM `{DB_VERSION}_rankings` s
                INNER JOIN `{DB_VERSION}_users` ON user_id = `{DB_VERSION}_users`.id,
                (SELECT @score:=0, @rank:=0) r
                ORDER BY scores DESC LIMIT :topn",
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
                'scores' => 1300.0,
                'max_scores' => 1300.0,
                'num_races_done' => 0,
                'raw_scores' => 4000.0,
                'rating_deviation' => 1000.0,
                'disconnects' => 0
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
                    a.user_id,
                    a.scores,
                    a.max_scores,
                    a.num_races_done,
                    a.raw_scores,
                    a.rating_deviation,
                    a.disconnects,
                    (SELECT
                        COUNT(DISTINCT scores)
                    FROM `{DB_VERSION}_rankings` b
                    WHERE a.`scores` < b.`scores`) + 1 rank
                FROM `{DB_VERSION}_rankings` a
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
     * @param double $new_raw_scores
     * @param double $new_rating_deviation
     * @param $new_disconnects
     *
     * @throws RankingException
     */
    public static function submitRanking(
        $user_permissions,
        $id_for_ranked,
        $new_scores,
        $new_max_scores,
        $new_num_races_done,
        $new_raw_scores,
        $new_rating_deviation,
        $new_disconnects
    ) {
        if (!in_array(AccessControl::PERM_SUMBIT_RANKINGS, $user_permissions))
        {
            throw new RankingException("Invalid user to sumbit ranking", ErrorType::USER_INVALID_PERMISSION);
        }
        try
        {
            DBConnection::get()->query(
                "INSERT INTO `{DB_VERSION}_rankings` (user_id, scores,
                max_scores, num_races_done, raw_scores, rating_deviation, disconnects)
                VALUES (:user_id, :scores, :max_scores, :num_races_done, :raw_scores, :rating_deviation, :disconnects)
                ON DUPLICATE KEY UPDATE `scores` = :scores,
                `max_scores` = :max_scores, `num_races_done`= :num_races_done,
                `raw_scores` = :raw_scores, `rating_deviation` = :rating_deviation, `disconnects`= :disconnects",
                DBConnection::NOTHING,
                [
                    ':user_id' => $id_for_ranked,
                    ':scores' => $new_scores,
                    ':max_scores' => $new_max_scores,
                    ':num_races_done' => $new_num_races_done,
                    ':raw_scores' => $new_raw_scores,
                    ':rating_deviation' => $new_rating_deviation,
                    ':disconnects' => $new_disconnects,
                ],
                [
                    ':user_id' => DBConnection::PARAM_INT,
                    ':scores' => DBConnection::PARAM_STR,
                    ':max_scores' => DBConnection::PARAM_STR,
                    ':num_races_done' => DBConnection::PARAM_INT,
                    ':raw_scores' => DBConnection::PARAM_STR,
                    ':rating_deviation' => DBConnection::PARAM_STR,
                    ':disconnects' => DBConnection::PARAM_STR, // Do not use PARAM_INT for 64bit unsigned integer
                ]
            );
        }
        catch (DBException $e)
        {
            throw new RankingException(exception_message_db(_('sumbit ranking')));
        }
    }

    /**
     * Increase rating deviation of all records daily.
     * Accounts playing ranked rarely will have a rising rating uncertainty
     * Reduce the score accordingly to keep the scores in sync with raw scores and rating deviation
     * (300 is 3 times the minimum rating deviation, we can't fetch the C++ code constant here).
     * The formula increases a rating deviation of 100 to 400 in 500 days and from 400 to 1000 in 500 more days.
     *
     * @throws RankingException
     */
    public static function cron()
    {
        try
        {
            DBConnection::get()->query(
                "UPDATE `{DB_VERSION}_rankings`
                SET rating_deviation = LEAST(1000.0, (rating_deviation + 0.277) * 1.001388),
                scores = raw_scores - 3.0 * rating_deviation + 300.0");
        }
        catch (DBException $e)
        {
            throw new RankingException(exception_message_db('cron'));
        }
    }
}
