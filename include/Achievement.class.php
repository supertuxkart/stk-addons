<?php
/**
 * copyright 2013      Glenn De Jonghe
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
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
 * Achievement class
 */
class Achievement
{
    /**
     * Get all the achievements ids of a user
     *
     * @param int $user_id
     *
     * @return array of achievement id's
     * @throws AchievementException
     */
    public static function getAchievementsIdsOf($user_id)
    {
        try
        {
            $achievements = DBConnection::get()->query(
                "SELECT `achievement_id` FROM " . DB_PREFIX . "achieved
                WHERE `user_id` = :user_id",
                DBConnection::FETCH_ALL,
                [':user_id' => $user_id],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new AchievementException(exception_message_db(_('fetch the achieved achievements')));
        }

        // build array of id's
        $return_achievements = [];
        foreach ($achievements as $achievement)
        {
            $return_achievements[] = $achievement['achievement_id'];
        }

        return $return_achievements;
    }

    /**
     * Get all the achievements of a user
     *
     * @param int $user_id
     *
     * @return array of achievement id's and name
     * @throws AchievementException
     */
    public static function getAchievementsOf($user_id)
    {
        try
        {
            $achievements = DBConnection::get()->query(
                "SELECT A.id, A.name
                FROM " . DB_PREFIX . "achieved AS AC
                INNER JOIN " . DB_PREFIX . "achievements AS A
                    ON AC.achievement_id = A.id
                WHERE `user_id` = :user_id",
                DBConnection::FETCH_ALL,
                [':user_id' => $user_id],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new AchievementException(exception_message_db(_('fetch the achieved achievements')));
        }

        return $achievements;
    }

    /**
     * a use has achieved an achievement
     *
     * @param int $user_id
     * @param int $achievement_id
     *
     * @throws AchievementException
     */
    public static function achieve($user_id, $achievement_id)
    {
        try
        {
            DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "achieved` (`user_id`, `achievement_id`)
                VALUES (:user_id, :achievement_id)
                ON DUPLICATE KEY UPDATE `user_id` = :user_id",
                DBConnection::NOTHING,
                [
                    ':achievement_id' => $achievement_id,
                    ':user_id'        => $user_id
                ],
                [
                    ':achievement_id' => DBConnection::PARAM_INT,
                    ':user_id'        => DBConnection::PARAM_INT
                ]
            );
        }
        catch (DBException $e)
        {
            // TODO find if this error code is for every database or only MYSQL
            if ($e->getSqlErrorCode() == "23503")
            {
                throw new AchievementException(_h("Provided an id of an achievement that doesn't exist in the database."));
            }
            else
            {
                throw new AchievementException(exception_message_db(_('confirm your achievement')));
            }
        }
    }
}
