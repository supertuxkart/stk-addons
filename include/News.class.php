<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
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

require_once(INCLUDE_DIR . 'DBConnection.class.php');
require_once(INCLUDE_DIR . 'Addon.class.php');
require_once(INCLUDE_DIR . 'Statistic.class.php');

/**
 * Manage the newsfeed that is fed to the game
 *
 * @author Stephen
 */
class News
{

    /**
     * Refresh all the dynamic entries in the database
     *
     * @return null|bool
     */
    public static function refreshDynamicEntries()
    {
        // TODO throw exceptions instead of echoing to the user
        // Get dynamic entries
        try
        {
            $dynamic_entries = DBConnection::get()->query(
                'SELECT *
                FROM `' . DB_PREFIX . 'news`
                WHERE `dynamic` = 1
                ORDER BY `id` ASC',
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            return false;
        }

        // Dynamic newest kart display
        $new_kart = Addon::getName(Statistic::newestAddon('karts'));
        $existing_id = false;
        foreach ($dynamic_entries as $entry)
        {
            if (preg_match('/^Newest add-on kart: (.*)$/i', $entry['content'], $matches))
            {
                if ($matches[1] !== $new_kart)
                {
                    // Delete old record
                    try
                    {
                        DBConnection::get()->query(
                            'DELETE FROM `' . DB_PREFIX . 'news`
                            WHERE `id` = :id',
                            DBConnection::NOTHING,
                            array(
                                ":id" => $entry['id']
                            )
                        );
                    }
                    catch(DBException $e)
                    {
                        echo 'Warning: failed to delete old news record.<br />';
                    }
                }
                else
                {
                    $existing_id = true;
                    break;
                }
            }
        }

        // Add new entry
        if ($existing_id === false && $new_kart !== false)
        {
            try
            {
                DBConnection::get()->insert(
                    'news',
                    array(
                        "content"     => "Newest add-on kart: " . $new_kart,
                        "web_display" => 1,
                        "dynamic"     => 1,
                    )
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest kart news entry.<br />';
            }
        }

        // Dynamic newest track display
        $new_track = Addon::getName(Statistic::newestAddon('tracks'));
        $existing_id = false;
        foreach ($dynamic_entries as $entry)
        {
            if (preg_match('/^Newest add-on track: (.*)$/i', $entry['content'], $matches))
            {
                if ($matches[1] !== $new_track)
                {
                    // Delete old record
                    try
                    {
                        DBConnection::get()->query(
                            'DELETE FROM `' . DB_PREFIX . 'news`
                            WHERE `id` = :id',
                            DBConnection::NOTHING,
                            array(
                                ":id" => $entry['id']
                            )
                        );
                    }
                    catch(DBException $e)
                    {
                        echo 'Warning: failed to delete old news record.<br />';
                    }
                }
                else
                {
                    $existing_id = true;
                    break;
                }
            }
        }

        // Add new entry
        if ($existing_id === false && $new_track !== false)
        {
            try
            {
                DBConnection::get()->insert(
                    'news',
                    array(
                        "content"     => "Newest add-on track: " . $new_track,
                        "web_display" => 1,
                        "dynamic"     => 1,
                    )
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest track news entry.<br />';
            }
        }

        // Dynamic newest arena display
        $new_arena = Addon::getName(Statistic::newestAddon('arenas'));
        $existing_id = false;
        foreach ($dynamic_entries as $entry)
        {
            if (preg_match('/^Newest add-on arena: (.*)$/i', $entry['content'], $matches))
            {
                if ($matches[1] !== $new_arena)
                {
                    // Delete old record
                    try
                    {
                        DBConnection::get()->query(
                            'DELETE FROM `' . DB_PREFIX . 'news`
                            WHERE `id` = :id',
                            DBConnection::NOTHING,
                            array(
                                ":id" => $entry['id']
                            )
                        );
                    }
                    catch(DBException $e)
                    {
                        echo 'Warning: failed to delete old news record.<br />';
                    }
                }
                else
                {
                    $existing_id = true;
                    break;
                }
            }
        }

        // Add new entry
        if ($existing_id === false && $new_arena !== false)
        {
            try
            {
                DBConnection::get()->insert(
                    'news',
                    array(
                        "content"     => "Newest add-on arena: " . $new_arena,
                        "web_display" => 1,
                        "dynamic"     => 1,
                    )
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest kart news entry.<br />';
            }
        }

        // Add message for the latest blog-post
        $latest_blogpost = News::getLatestBlogPost();
        $existing_id = false;
        foreach ($dynamic_entries as $entry)
        {
            if (preg_match('/^Latest post on stkblog.net: (.*)$/i', $entry['content'], $matches))
            {
                if ($matches[1] !== $latest_blogpost)
                {
                    // Delete old record
                    try
                    {
                        DBConnection::get()->query(
                            'DELETE FROM `' . DB_PREFIX . 'news`
                            WHERE `id` = :id',
                            DBConnection::NOTHING,
                            array(
                                ":id" => $entry['id']
                            )
                        );
                    }
                    catch(DBException $e)
                    {
                        echo 'Warning: failed to delete old news record.<br />';
                    }
                }
                else
                {
                    $existing_id = true;
                    break;
                }
            }
        }

        // Add new entry
        if ($existing_id === false && $latest_blogpost !== false)
        {
            try
            {
                DBConnection::get()->insert(
                    'news',
                    array(
                        "content"     => "Latest post on stkblog.net: " . $latest_blogpost,
                        "web_display" => 1,
                        "dynamic"     => 1,
                    )
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest kart news entry.<br />';
            }
        }

        return null;
    }

    /**
     * Get the last article title
     *
     * @return string|bool
     */
    private static function getLatestBlogPost()
    {
        $feed_url = ConfigManager::getConfig('blog_feed');
        if (empty($feed_url))
        {
            return false;
        }

        $xmlContents = file($feed_url, FILE_IGNORE_NEW_LINES);
        if (!$xmlContents)
        {
            return false;
        }

        $reader = xml_parser_create();
        if (!xml_parse_into_struct($reader, implode('', $xmlContents), $vals, $index))
        {
            echo 'XML Error: ' . xml_error_string(xml_get_error_code($reader)) . '<br />';

            return false;
        }

        // TODO maybe use a more sane way to parse
        $startSearch = -1;
        $vals_count = count($vals);
        for ($i = 0; $i < $vals_count; $i++)
        {
            if ($vals[$i]['tag'] === 'ITEM')
            {
                $startSearch = $i;
                break;
            }
        }
        if ($startSearch === -1)
        {
            return false;
        }

        $articleTitle = null;
        for ($i = $startSearch; $i < $vals_count; $i++)
        {
            if ($vals[$i]['tag'] === 'TITLE')
            {
                $articleTitle = $vals[$i]['value'];
                break;
            }
        }
        if ($articleTitle === null)
        {
            return false;
        }

        return strip_tags($articleTitle);
    }

    /**
     * Get active news articles flagged as web-visible
     *
     * @return array
     */
    public static function getWebVisible()
    {
        try
        {
            $items = DBConnection::get()->query(
                'SELECT `content` FROM `' . DB_PREFIX . 'news`
                WHERE `active` = 1
                AND `web_display` = 1
                ORDER BY `date` DESC',
                DBConnection::FETCH_ALL
            );
            $ret = array();
            foreach ($items as $item)
            {
                $ret[] = htmlspecialchars($item['content']);
            }

            return $ret;
        }
        catch(DBException $e)
        {
            return array();
        }
    }

    /**
     * Get news data for posts marked active
     *
     * @return array
     */
    public static function getActive()
    {
        try
        {
            $news = DBConnection::get()->query(
                'SELECT `n`.*, `u`.`user` AS `author`
                FROM `' . DB_PREFIX . 'news` `n`
                LEFT JOIN `' . DB_PREFIX . 'users` `u`
                ON (`n`.`author_id`=`u`.`id`)
                WHERE `n`.`active` = \'1\'
                ORDER BY `date` DESC',
                DBConnection::FETCH_ALL
            );

            return $news;
        }
        catch(DBException $e)
        {
            return array();
        }
    }

    /**
     * Get news data for all posts
     *
     * @return array
     */
    public static function getAll()
    {
        try
        {
            $news = DBConnection::get()->query(
                'SELECT `n`.*, `u`.`user` AS `author`
                FROM `' . DB_PREFIX . 'news` `n`
                LEFT JOIN `' . DB_PREFIX . 'users` `u`
                ON (`n`.`author_id`=`u`.`id`)
                ORDER BY `date` DESC',
                DBConnection::FETCH_ALL
            );

            return $news;
        }
        catch(DBException $e)
        {
            return array();
        }
    }

    /**
     * Create a news entry
     *
     * @param string  $message
     * @param string  $condition
     * @param boolean $important
     * @param boolean $web_display
     *
     * @throws NewsException
     */
    public static function create($message, $condition, $important, $web_display)
    {
        try
        {
            if (!User::isLoggedIn())
            {
                throw new Exception();
            }
            DBConnection::get()->insert(
                'news',
                array(
                    'author_id'   => (int)User::getId(),
                    'content'     => (string)$message,
                    'condition'   => (string)$condition,
                    'important'   => (int)$important,
                    'web_display' => (int)$web_display,
                    'active'      => 1
                )
            );
            writeNewsXML();
        }
        catch(DBException $e)
        {
            throw new NewsException('Database error while creating message.');
        }
        catch(Exception $e)
        {
            throw new NewsException('Error while creating message.');
        }
    }

    /**
     * Delete a news article
     *
     * @param int $id
     *
     * @return bool
     */
    public static function delete($id)
    {
        try
        {
            DBConnection::get()->query(
                'DELETE FROM `' . DB_PREFIX . 'news`
                WHERE `id` = :id',
                DBConnection::NOTHING,
                array(':id' => (int)$id)
            );
            writeNewsXML();

            return true;
        }
        catch(DBException $e)
        {
            return false;
        }
    }
}