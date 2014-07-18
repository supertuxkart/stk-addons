<?php
/**
 * copyright 2013 Glenn De Jonghe
 *           2014 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
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

/**
 * Achievement class
 */
class Achievement
{
    /**
     * Get all the achievements of a user
     *
     * @param int $user_id
     *
     * @return array of achievement id's
     * @throws AchievementException
     */
    public static function getAchievementsOf($user_id)
    {
        try
        {
            $achievements = DBConnection::get()->query(
                "SELECT `achievementid` FROM " . DB_PREFIX . "achieved
                WHERE `userid` = :userid",
                DBConnection::FETCH_ALL,
                [':userid' => $user_id],
                [':userid' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new AchievementException(h(
                _('An unexpected error occured while fetching the achieved achievements.') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        // build array of id's
        $return_achievements = [];
        foreach ($achievements as $achievement)
        {
            $return_achievements[] = $achievement['achievementid'];
        }

        return $return_achievements;
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
                "INSERT INTO `" . DB_PREFIX . "achieved` (`userid`, `achievementid`)
                VALUES (:userid, :achievementid)
                ON DUPLICATE KEY UPDATE `userid` = :userid",
                DBConnection::NOTHING,
                [
                    ':achievementid' => $achievement_id,
                    ':userid'        => $user_id
                ],
                [
                    ':achievementid' => DBConnection::PARAM_INT,
                    ':userid'        => DBConnection::PARAM_INT
                ]
            );
        }
        catch(DBException $e)
        {
            if ($e->getErrorCode() == "23503")
            {
                throw new AchievementException(_h("Provided an id of an achievement that doesn't exist in the database."));
            }
            else
            {
                throw new AchievementException(h(
                    _('An unexpected error occured while confirming your achievement.') . ' ' .
                    _('Please contact a website administrator.')
                ));
            }
        }
    }
}
