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
     * @var array
     */
    private static $cache = array();

    /**
     * Get a config option by name
     *
     * @param string $config_name
     *
     * @return null|string
     */
    public static function getConfig($config_name)
    {
        // Validate parameters
        if (!is_string($config_name))
        {
            return null;
        }
        if (empty($config_name))
        {
            return null;
        }

        // Populate the config cache
        if (empty(ConfigManager::$cache))
        {
            try
            {
                $result = DBConnection::get()->query(
                    'SELECT `name`, `value`' .
                    'FROM `' . DB_PREFIX . 'config`',
                    DBConnection::FETCH_ALL
                );
            }
            catch(DBException $e)
            {
                if (DEBUG_MODE)
                {
                    trigger_error($e->getMessage());
                }

                return null;
            }
            if (empty($result))
            {
                return null;
            }

            foreach ($result as $row)
            {
                ConfigManager::$cache[$row['name']] = $row['value'];
            }
        }
        if (!isset(ConfigManager::$cache[$config_name]))
        {
            return null;
        }

        return ConfigManager::$cache[$config_name];
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
        if (empty($config_name))
        {
            return false;
        }
        if (is_array($config_value))
        {
            return false;
        }
        if (empty($config_value))
        {
            return true;
        } // Not changed because we
        // can't accept null values

        try
        {
            DBConnection::get()->query(
                'INSERT INTO `' . DB_PREFIX . 'config` ' .
                '(`name`, `value`) ' .
                'VALUES ' .
                '(:name, :value) ' .
                'ON DUPLICATE KEY UPDATE `value` = :value',
                DBConnection::NOTHING,
                array(
                    ':name'  => (string)$config_name,
                    ':value' => (string)$config_value
                )
            );
        }
        catch(DBException $e)
        {
            if (DEBUG_MODE)
            {
                trigger_error($e->getMessage());
            }

            return false;
        }

        // Update cache - first, make sure the cache exists
        ConfigManager::getConfig($config_name);
        ConfigManager::$cache[$config_name] = $config_value;

        return true;
    }
}
