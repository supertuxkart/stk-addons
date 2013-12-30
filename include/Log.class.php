<?php
/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
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

require_once(INCLUDE_DIR . 'DBConnection.class.php');
require_once(INCLUDE_DIR . 'User.class.php');

class Log {
    /**
     * Add an event to the event log
     * @param string $message Event description
     */
    public static function newEvent($message) {
        $userid = (User::$logged_in) ? User::$user_id : 0;
        DBConnection::get()->query(
            "CALL `".DB_PREFIX."log_event`
            (:userid, :message)",
            DBConnection::NOTHING,
            array
            (
                    ':userid'   => $userid,
                    ':message'  => strip_tags( (string) $message)
            )
        );
    }
    
    /**
     * Return an array of the $number latest events that were logged
     * @param integer $number
     * @return array 
     */
    public static function getEvents($number = 25) {
        if (!is_int($number))
            throw new Exception('$number must be an integer.');

        if (!User::$logged_in)
            throw new Exception('You must be logged in ot view the event log.');
        if (!$_SESSION['role']['manageaddons'])
            throw new Exception('You do not have the necessary permissions to view the event log.');
        
        try {
            $events = DBConnection::get()->query(
                    'SELECT `l`.`date`, `l`.`user`, `l`.`message`, `u`.`name`
                     FROM `'.DB_PREFIX.'logs` `l`
                     LEFT JOIN `'.DB_PREFIX.'users` `u`
                     ON `l`.`user` = `u`.`id`
                     ORDER BY `l`.`date` DESC
                     LIMIT :limit',
                    DBConnection::FETCH_ALL,
                    array(':limit' => (int) $number));
        } catch (DBException $e) {
            throw new Exception('Failed to fetch log entries.');
        }
        
        return $events;
    }
    
    /**
     * Gets a list of events that have not been emailed to the moderator list
     * @return array
     */
    public static function getUnemailedEvents() {
        try {
            $events = DBConnection::get()->query(
                    'SELECT `l`.`date`,`l`.`user`,`l`.`message`,`u`.`name`
                     FROM `'.DB_PREFIX.'logs` `l`
                     LEFT JOIN `'.DB_PREFIX.'users` `u`
                     ON `l`.`user` = `u`.`id`
                     WHERE `l`.`emailed` = 0
                     ORDER BY `l`.`date` DESC',
                    DBConnection::FETCH_ALL,
                    null);
            return $events;
        } catch (DBException $e) {
            print "Failed to load log for email.\n";
            return array();
        }
    }
    
    /**
     * Set the mailed flag on all log messages
     * @throws Exception
     */
    public static function setAllEventsMailed() {
        try {
            DBConnection::get()->query(
                    'UPDATE `'.DB_PREFIX.'logs` SET `emailed` = 1',
                    DBConnection::NOTHING,
                    null);
        } catch (DBException $e) {
            throw new Exception('Failed to mark log messages as mailed.');
        }
    }
}
?>
