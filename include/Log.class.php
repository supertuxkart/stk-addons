<?php
/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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

class Log
{
    /**
     * Add an event to the event log
     *
     * @param string $message Event description
     */
    public static function newEvent($message)
    {
        $userid = (User::isLoggedIn()) ? User::getId() : 0;
        DBConnection::get()->query(
            "CALL `" . DB_PREFIX . "log_event`
            (:userid, :message)",
            DBConnection::NOTHING,
            array(
                ':userid'  => $userid,
                ':message' => h($message)
            )
        );
    }

    /**
     * Return an array of the $number latest events that were logged
     *
     * @param int $number
     *
     * @return array
     * @throws LogException
     */
    public static function getEvents($number = 25)
    {
        if (!is_int($number))
        {
            throw new LogException('$number must be an integer.');
        }

        if (!User::isLoggedIn())
        {
            throw new LogException('You must be logged in ot view the event log.');
        }
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            throw new LogException('You do not have the necessary permissions to view the event log.');
        }

        try
        {
            $events = DBConnection::get()->query(
                'SELECT `l`.`date`, `l`.`user`, `l`.`message`, `u`.`name`
                FROM `' . DB_PREFIX . 'logs` `l`
                LEFT JOIN `' . DB_PREFIX . 'users` `u`
                ON `l`.`user` = `u`.`id`
                ORDER BY `l`.`date` DESC
                LIMIT :limit',
                DBConnection::FETCH_ALL,
                array(':limit' => (int)$number),
                array(':limit' => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new LogException('Failed to fetch log entries.');
        }

        return $events;
    }

    /**
     * Gets a list of events that have not been emailed to the moderator list
     *
     * @return array
     * @throws LogException
     */
    public static function getUnemailedEvents()
    {
        try
        {
            $events = DBConnection::get()->query(
                'SELECT `l`.`date`,`l`.`user`,`l`.`message`,`u`.`name`
                FROM `' . DB_PREFIX . 'logs` `l`
                LEFT JOIN `' . DB_PREFIX . 'users` `u`
                ON `l`.`user` = `u`.`id`
                WHERE `l`.`emailed` = 0
                ORDER BY `l`.`date` DESC',
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            throw new LogException("Failed to load log for email.");
        }

        return $events;
    }

    /**
     * Set the mailed flag on all log messages
     * @throws Exception
     */
    public static function setAllEventsMailed()
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'logs` SET `emailed` = 1',
                DBConnection::NOTHING
            );
        }
        catch(DBException $e)
        {
            throw new LogException('Failed to mark log messages as mailed.');
        }
    }
}
