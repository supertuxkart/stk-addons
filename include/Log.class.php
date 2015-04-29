<?php
/**
 * copyright 2012      Stephen Just <stephenjust@users.sf.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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

/**
 * Log class
 */
class Log
{
    /**
     * Add an event to the event log
     *
     * @param string $message Event description
     *
     * @throws LogException
     */
    public static function newEvent($message)
    {
        try
        {
            $user_id = User::getLoggedId();
            $user_id_type = DBConnection::PARAM_INT;
            if ($user_id === -1) // no user, respect database constraints, set it null
            {
                $user_id = null;
                $user_id_type = DBConnection::PARAM_NULL;
            }

            DBConnection::get()->query(
                "INSERT INTO " . DB_PREFIX . "logs (`user_id`, `message`)
                VALUES (:user_id, :message)",
                DBConnection::NOTHING,
                [
                    ':user_id' => $user_id,
                    ':message' => $message
                ],
                [':user_id' => $user_id_type]
            );
        }
        catch(DBException $e)
        {
            throw new LogException(exception_message_db(_('log a new event')));
        }
    }

    /**
     * Return an array of the latest events that were logged
     *
     * @param int $limit
     *
     * @return array
     * @throws LogException
     */
    public static function getEvents($limit = 100)
    {
        if (!is_int($limit))
        {
            throw new LogException('$number must be an integer.');
        }

        try
        {
            $events = DBConnection::get()->query(
                'SELECT L.`date`, L.`user_id`, L.`message`, U.`username`
                FROM `' . DB_PREFIX . 'logs` L
                LEFT JOIN `' . DB_PREFIX . 'users` U
                    ON L.`user_id` = U.`id`
                ORDER BY L.`date` DESC
                LIMIT :limit',
                DBConnection::FETCH_ALL,
                [':limit' => $limit],
                [':limit' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new LogException(exception_message_db(_('fetch the log entries')));
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
                'SELECT L.`date`, L.`user_id`, L.`message`, U.`name`
                FROM `' . DB_PREFIX . 'logs` L
                LEFT JOIN `' . DB_PREFIX . 'users` U
                    ON L.`user_id` = U.`id`
                WHERE L.`is_emailed` = 0
                ORDER BY L.`date` DESC',
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            throw new LogException(exception_message_db(_('load unemailed logs')));
        }

        return $events;
    }

    /**
     * Set the mailed flag on all log messages
     *
     * @throws LogException
     */
    public static function setAllEventsMailed()
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'logs` SET `is_emailed` = 1',
                DBConnection::NOTHING
            );
        }
        catch(DBException $e)
        {
            throw new LogException(exception_message_db(_('mark log messages as mailed')));
        }
    }
}
