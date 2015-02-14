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
class Config
{
    /**
     * The admin email address
     */
    const EMAIL_ADMIN = "admin_email";

    /**
     * The list email address
     */
    const EMAIL_LIST = "list_email";

    /**
     * An int describing the maximum image size
     */
    const IMAGE_MAX_DIMENSION = "max_image_dimension";

    /**
     * The rss blog feed url
     */
    const FEED_BLOG = "blog_feed";

    /**
     * The apache rewrites rules
     */
    const APACHE_REWRITES = "apache_rewrites";

    /**
     * Bool flag, whether so show or not invisible addons in the xml files
     */
    const SHOW_INVISIBLE_ADDONS = "list_invisible";

    /**
     * Time in seconds between xml updates
     */
    const XML_UPDATE_TIME = "xml_frequency";

    /**
     * A coma separated string of allowed addon extensions
     */
    const ALLOWED_ADDON_EXTENSIONS = "allowed_addon_exts";

    /**
     * A coma separated string of allowed source extensions
     */
    const ALLOWED_SOURCE_EXTENSIONS = "allowed_source_exts";

    /**
     * The path to the image json, use in conjunction with the apache rewrites.
     * String must include $aid and $atype, that will be replaced with the actual id or type
     */
    const PATH_LICENSE_JSON = "license_json_path";

    /**
     * The path to the image json, use in conjunction with the apache rewrites.
     * String must include $aid and $atype, that will be replaced with the actual id or type
     */
    const PATH_IMAGE_JSON = "image_json_path";

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
    public static function get($config_name)
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
    public static function set($config_name, $config_value)
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

        // TODO set multiple values at once, not that critical, as this is only used in the manage part of the website
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

        // Update cache by cleaning the cache array, on the next call of get() method it will repopulate the cache
        static::$cache = [];

        return true;
    }
}
