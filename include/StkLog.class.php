<?php
/**
 * copyright 2012      Stephen Just <stephenjust@users.sf.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
 * StkLog application specific events on the `logs` table.
 * DO NOT USE THIS FOR error logging.
 */
class StkLog
{
    /**
     * Add an event to the event log
     *
     * @param string $message Event description
     * @param string $log_level the log level used
     */
    public static function newEvent($message, $log_level = LogLevel::INFO)
    {
        // everything besides INFO and NOTICE is an error
        if ($log_level !== LogLevel::INFO && $log_level != LogLevel::NOTICE)
        {
            Debug::addMessage($message, $log_level, false);
            error_log(sprintf("%s: %s", $log_level, $message));
        }

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
                "INSERT IGNORE INTO `{DB_VERSION}_logs` (`user_id`, `message`)
                VALUES (:user_id, :message)",
                DBConnection::NOTHING,
                [
                    ':user_id' => $user_id,
                    ':message' => $message
                ],
                [':user_id' => $user_id_type]
            );
        }
        catch (DBException $e)
        {
            Debug::addException(new LogException(exception_message_db(_('log a new event'))));
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
            throw new LogException('$limit must be an integer.');
        }

        try
        {
            $events = DBConnection::get()->query(
                'SELECT L.`date`, L.`user_id`, L.`message`, U.`username`
                FROM `{DB_VERSION}_logs` L
                LEFT JOIN `{DB_VERSION}_users` U
                    ON L.`user_id` = U.`id`
                ORDER BY L.`date` DESC
                LIMIT :limit',
                DBConnection::FETCH_ALL,
                [':limit' => $limit],
                [':limit' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
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
                FROM `{DB_VERSION}_logs` L
                LEFT JOIN `{DB_VERSION}_users` U
                    ON L.`user_id` = U.`id`
                WHERE L.`is_emailed` = 0
                ORDER BY L.`date` DESC',
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
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
                'UPDATE `{DB_VERSION}_logs` SET `is_emailed` = 1',
                DBConnection::NOTHING
            );
        }
        catch (DBException $e)
        {
            throw new LogException(exception_message_db(_('mark log messages as mailed')));
        }
    }
}
