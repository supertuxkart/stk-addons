<?php
/**
 * copyright 2013 Glenn De Jonghe
 *
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

require_once(INCLUDE_DIR . 'exceptions.php');
require_once(INCLUDE_DIR . 'DBConnection.class.php');
require_once(INCLUDE_DIR . 'User.class.php');
require_once(INCLUDE_DIR . 'ClientSession.class.php');


class AchievementException extends Exception {}

/**
 * Achievement class
 */
class Achievement
{
    public static function getAchievementsOf($userid)
    {
        try{
            $result = DBConnection::get()->query
            (
                "SELECT `achievementid` FROM " . DB_PREFIX ."achieved
                WHERE `userid` = :userid",
                DBConnection::FETCH_ALL,
                array
                (
                    ':userid'       => (int) $userid
                )
            );
        }catch (DBException $e){
            throw new AchievementException(
                _('An unexpected error occured while fetching the achieved achievements.') . ' ' .
                _('Please contact a website administrator.'));
        }
        $string_list = "";
        foreach ($result as $r){
            $string_list .= $r['achievementid'];
            $string_list .= ' ';
        }
        $string_list = trim($string_list);
        return $string_list;
    }
    
    public static function achieve($userid, $achievementid)
    {
        try{
            $count = DBConnection::get()->query
            (
                "INSERT INTO `" . DB_PREFIX ."achieved` (`userid`, `achievementid`)
                VALUES (:userid, :achievementid)
                ON DUPLICATE KEY UPDATE `userid` = :userid",
                DBConnection::ROW_COUNT,
                array
                (
                        ':achievementid'   => (int) $achievementid,
                        ':userid' => (int) $userid
                )
            );
        }catch (DBException $e){
            if($e->getErrorCode() == "23503")
                throw new AchievementException(
                    _("Provided an id of an achievement that doesn't exist in the database."));
            else
                throw new AchievementException(
                    _('An unexpected error occured while confirming your achievement.') . ' ' .
                    _('Please contact a website administrator.'));
        }
    }
}

?>
