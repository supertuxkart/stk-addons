<?php
/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sourceforge.net>
 *                2014 Daniel Butum <danibutum at gmail dot com>
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
 * Contains static methods to manage configuration values in database
 */
class ConfigManager
{
    /**
     * Cache the results
     * @var array
     */
    private static $cache = [];

    /**
     * Get a config option by name
     *
     * @param string $config_name
     *
     * @throws InvalidArgumentException when config_name is not a string
     * @return null|string
     */
    public static function getConfig($config_name)
    {
        // Validate parameters
        if (!is_string($config_name))
        {
            throw new InvalidArgumentException("config_name is no a string");
        }
        if (!$config_name)
        {
            return null;
        }

        // Populate the config cache
        if (empty(static::$cache))
        {
            try
            {
                $configs = DBConnection::get()->query(
                    'SELECT `name`, `value`' .
                    'FROM `' . DB_PREFIX . 'config`',
                    DBConnection::FETCH_ALL
                );
            }
            catch(DBException $e)
            {
                trigger_error($e->getMessage());

                return null;
            }

            // the table is empty no need to continue
            if (empty($configs))
            {
                return null;
            }

            // fill cache
            foreach ($configs as $config)
            {
                static::$cache[$config['name']] = $config['value'];
            }
        }

        // the config does not exist
        if (!isset(static::$cache[$config_name]))
        {
            return null;
        }

        return static::$cache[$config_name];
    }

    /**
     * Set a config in the database
     *
     * @param string $config_name
     * @param string $config_value
     *
     * @return bool true on success, false otherwise
     */
    public static function setConfig($config_name, $config_value)
    {
        // Validate parameters
        if (!is_string($config_name))
        {
            return false;
        }
        if (!$config_name || !$config_value)
        {
            return false;
        }

        try
        {
            DBConnection::get()->query(
                'INSERT INTO `' . DB_PREFIX . 'config` ' .
                '(`name`, `value`) ' .
                'VALUES ' .
                '(:name, :value) ' .
                'ON DUPLICATE KEY UPDATE `value` = :value',
                DBConnection::NOTHING,
                [
                    ':name'  => $config_name,
                    ':value' => $config_value
                ]
            );
        }
        catch(DBException $e)
        {
            trigger_error($e->getMessage());

            return false;
        }

        // Update cache - first, make sure the cache exists
        static::getConfig($config_name); // TODO check if we really need to update cache
        static::$cache[$config_name] = $config_value;

        return true;
    }
}
