<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
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

/**
 * Contains static methods to manage configuration values in database
 */
class ConfigManager
{
    private static $cache = array();

    public static function get_config($config_name) {
        // Validate parameters
        if (!is_string($config_name)) {
            return NULL;
        }
        if (strlen($config_name) < 1) {
            return NULL;
        }

        $config_name = mysql_real_escape_string($config_name);

        // Populate the config cache
        if (count(ConfigManager::$cache == 0)) {
            $query = 'SELECT `name`, `value`
                FROM `'.DB_PREFIX.'config`';
            $handle = sql_query($query);
            if (!$handle) return NULL;

            $num_config = mysql_num_rows($handle);
            if ($num_config == 0) {
                return NULL;
            }
            for ($i = 1; $i <= $num_config; $i++) {
                $config = sql_next($handle);
                ConfigManager::$cache[$config['name']] = $config['value'];
            }
        }
        if (!isset(ConfigManager::$cache[$config_name])) {
            return NULL;
        }
        return ConfigManager::$cache[$config_name];
    }

    public static function set_config($config_name,$config_value) {
            // Validate parameters
            if (!is_string($config_name)) {
                    return false;
            }
            if (strlen($config_name) < 1) {
                    return false;
            }
            if (is_array($config_value)) {
                    return false;
            }
            if (strlen($config_value) < 1) {
                    return true; // Not changed because we can't accept null values
            }

            $config_name = mysql_real_escape_string($config_name);
            $config_value = mysql_real_escape_string($config_value);

            // Check if value already exists in database
            if (!is_null(ConfigManager::get_config($config_name))) {
                    // Value exists
                    $set_query = "UPDATE `".DB_PREFIX."config`
                            SET `value` = '$config_value'
                            WHERE `name` = '$config_name'";
            } else {
                    // Value does not exist
                    $set_query = "INSERT INTO `".DB_PREFIX."config`
                            (`name`,`value`)
                            VALUES ('$config_name','$config_value')";
            }
            $set_handle = sql_query($set_query);
            if (!$set_handle) return false;

            // Update cache - first, make sure the cache exists
            ConfigManager::get_config($config_name);
            ConfigManager::$cache[$config_name] = $config_value;

            return true;
    }
}
?>
