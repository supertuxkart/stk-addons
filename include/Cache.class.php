<?php

/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *           2014-2016 Daniel Butum <danibutum at gmail dot com>
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
 * Handles management of temporary files
 * @author Stephen
 */
class Cache
{
    /**
     * The default image if the desired image does not exist
     */
    const NOT_FOUND_IMAGE = 'notfound.png';

    /**
     * Do not touch the graph_files and the .gitignore file
     */
    const DEFAULT_EXCLUDE_REGEX = '/^^(cache_graph_.*\.json)|\.gitignore$/i';

    /**
     * Empty the cache in the filesystem and database, leave certain files in place
     *
     * @param string $exclude_regex files to exclude regex, uses the static::DEFAULT_EXCLUDE_REGEX if null
     *
     * @throws CacheException
     */
    public static function clear($exclude_regex = null)
    {
        static::clearFS($exclude_regex);
        static::clearDB($exclude_regex);
    }

    /**
     * Empty the cache in the filesystem only, leave certain files in place
     *
     * @param string $exclude_regex files to exclude regex, uses the static::DEFAULT_EXCLUDE_REGEX if null
     *
     * @throws CacheException
     */
    public static function clearFS($exclude_regex = null)
    {
        if (!$exclude_regex) $exclude_regex = static::DEFAULT_EXCLUDE_REGEX;

        try
        {
            FileSystem::removeDirectory(CACHE_PATH, false, $exclude_regex);
        }
        catch (FileException $e)
        {
            throw new CacheException($e->getMessage());
        }
    }

    /**
     * Empty the cache in the database only, leave certain files in place
     *
     * @param string $exclude_regex files to exclude regex, uses the static::DEFAULT_EXCLUDE_REGEX if null
     *
     * @throws CacheException
     */
    public static function clearDB($exclude_regex = null)
    {
        if (!$exclude_regex) $exclude_regex = static::DEFAULT_EXCLUDE_REGEX;

        try
        {
            $cache_list = DBConnection::get()->query(
                'SELECT `file` FROM `' . DB_PREFIX . 'cache`',
                DBConnection::FETCH_ALL
            );
            foreach ($cache_list as $cache_item)
            {
                // matches exclude regex, ignore
                if (preg_match($exclude_regex, $cache_item['file']))
                {
                    continue;
                }

                DBConnection::get()->delete("cache", "`file` = :file", [':file' => $cache_item['file']]);
            }
        }
        catch (DBException $e)
        {
            throw new CacheException(exception_message_db(_("empty the cache")));
        }
    }

    /**
     * Clear the addon cache files
     * This method silently fails
     *
     * @param string $addon the addon id
     *
     * @return bool
     */
    public static function clearAddon($addon)
    {
        // TODO remove redundant method calls
        $addon = Addon::cleanId($addon);
        if (!Addon::exists($addon))
        {
            return false;
        }

        try
        {
            $cache_list = DBConnection::get()->query(
                'SELECT `file`
                FROM `' . DB_PREFIX . 'cache`
                WHERE `addon_id` = :addon_id',
                DBConnection::FETCH_ALL,
                [':addon_id' => $addon]
            );
            foreach ($cache_list AS $cache_item)
            {
                try
                {
                    FileSystem::removeFile(CACHE_PATH . $cache_item['file']);
                }
                catch (FileException $e)
                {
                    Log::newEvent($e->getMessage());
                    Debug::addMessage(
                        'Cache::clearAddon failed to delete the cache file = ' . CACHE_PATH . $cache_item['file']
                    );

                    return false;
                }

                DBConnection::get()->delete("cache", "`file` = :file", [':file' => $cache_item['file']]);
            }
        }
        catch (DBException $e)
        {
            Debug::addMessage('Cache::clearAddon database error');

            return false;
        }

        return true;
    }

    /**
     * Add a database record for a cache file
     * This method will silently fail
     *
     * @param string $path  Relative to cache root
     * @param string $addon Associated add-on's id or NULL
     * @param string $props File properties (e.g. w=1,h=3)
     *
     * @return boolean
     */
    public static function createFile($path, $addon = null, $props = null)
    {
        $addon = Addon::exists($addon) ? Addon::cleanId($addon) : null;

        try
        {
            DBConnection::get()->insert(
                "cache",
                [
                    ":file"     => $path,
                    ":addon_id" => $addon,
                    ":props"    => $props
                ]
            );
        }
        catch (DBException $e)
        {
            Debug::addMessage('Cache::createFile database error');

            return false;
        }

        return true;
    }

    /**
     * Check if a cache file exists in the database, filesystem
     * This method silently fails
     *
     * @param string $filename
     *
     * @return bool
     */
    public static function fileExists($filename)
    {
        try
        {
            // TODO add an exists method
            $count_db = DBConnection::get()->count('cache', '`file` = :file', [':file' => $filename]);
        }
        catch (DBException $e)
        {
            Debug::addMessage('Cache::fileExists database error');

            return false;
        }
        if (!$count_db) return false;

        $local_path = CACHE_PATH . $filename;
        if (!FileSystem::exists($local_path)) return false;

        return true;
    }

    /**
     * Get image properties for a cacheable image
     *
     * @param int $id   the id of the file
     * @param int $size image size, see SImage::SIZE_*
     *
     * @return array
     * @throws CacheException
     */
    public static function getImage($id, $size = null)
    {
        try
        {
            $file = File::getFromID($id);
        }
        catch (FileException $e)
        {
            // image does with tha id does not exist in the database
            return [
                'url'         => IMG_LOCATION . static::NOT_FOUND_IMAGE,
                'is_approved' => true,
                'exists'      => false
            ];
        }

        $return = [
            'url'         => DOWNLOAD_LOCATION . $file->getPath(),
            'is_approved' => $file->isApproved(),
            'exists'      => true
        ];

        $cache_prefix = Cache::getCachePrefix($size);

        // image exists in cache
        $filename = $cache_prefix . $file->getFileName();
        if (static::fileExists($filename))
        {
            Debug::addMessage('true: ' . $filename);
            $return['url'] = CACHE_LOCATION . $filename;
        }
        else // create new cache by resizing the image
        {
            Debug::addMessage('false');
            $return['url'] = ROOT_LOCATION . 'image.php?size=' . $size . '&amp;pic=' . $file->getPath();
        }

        return $return;
    }

    /**
     * @param string $size
     *
     * @return string
     */
    public static function getCachePrefix($size)
    {
        return sprintf("%d--", SImage::sizeToInt($size));
    }
}
