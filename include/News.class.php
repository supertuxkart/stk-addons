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
        $new_kart = Addon::getNameByID(Statistic::newestAddon(Addon::KART));
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
                        DBConnection::get()->delete(
                            "news",
                            "`id` = :id",
                            [":id" => $entry['id']],
                            [":id" => DBConnection::PARAM_INT]
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
        if (!$existing_id  && $new_kart)
        {
            try
            {
                DBConnection::get()->insert(
                    'news',
                    [
                        ":content"    => "Newest add-on kart: " . $new_kart,
                        "web_display" => 1,
                        "dynamic"     => 1
                    ]
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest kart news entry.<br />';
            }
        }

        // Dynamic newest track display
        $new_track = Addon::getNameByID(Statistic::newestAddon(Addon::TRACK));
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
                        DBConnection::get()->delete(
                            "news",
                            "`id` = :id",
                            [":id" => $entry['id']],
                            [":id" => DBConnection::PARAM_INT]
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
                    [
                        ":content"    => "Newest add-on track: " . $new_track,
                        "web_display" => 1,
                        "dynamic"     => 1
                    ]
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest track news entry.<br />';
            }
        }

        // Dynamic newest arena display
        $new_arena = Addon::getNameByID(Statistic::newestAddon(Addon::ARENA));
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
                        DBConnection::get()->delete(
                            "news",
                            "`id` = :id",
                            [":id" => $entry['id']],
                            [":id" => DBConnection::PARAM_INT]
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
                    [
                        ":content"    => "Newest add-on arena: " . $new_arena,
                        "web_display" => 1,
                        "dynamic"     => 1
                    ]
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
                        DBConnection::get()->delete(
                            "news",
                            "`id` = :id",
                            [":id" => $entry['id']],
                            [":id" => DBConnection::PARAM_INT]
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
                    [
                        ":content"    => "Latest post on stkblog.net: " . $latest_blogpost,
                        "web_display" => 1,
                        "dynamic"     => 1,
                    ]
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

        $xml_content = file($feed_url, FILE_IGNORE_NEW_LINES);
        if (!$xml_content)
        {
            return false;
        }

        $reader = xml_parser_create();
        if (!xml_parse_into_struct($reader, implode('', $xml_content), $vals, $index))
        {
            echo 'XML Error: ' . xml_error_string(xml_get_error_code($reader)) . '<br />';

            return false;
        }

        // TODO maybe use a more sane way to parse
        $start_search = -1;
        $vals_count = count($vals);
        for ($i = 0; $i < $vals_count; $i++)
        {
            if ($vals[$i]['tag'] === 'ITEM')
            {
                $start_search = $i;
                break;
            }
        }

        if ($start_search === -1)
        {
            return false;
        }

        $article_title = null;
        for ($i = $start_search; $i < $vals_count; $i++)
        {
            if ($vals[$i]['tag'] === 'TITLE')
            {
                $article_title = $vals[$i]['value'];
                break;
            }
        }

        if ($article_title === null)
        {
            return false;
        }

        return h($article_title);
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
        }
        catch(DBException $e)
        {
            return [];
        }

        $ret = [];
        foreach ($items as $item)
        {
            $ret[] = h($item['content']);
        }

        return $ret;
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
        }
        catch(DBException $e)
        {
            return [];
        }

        return $news;
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
        }
        catch(DBException $e)
        {
            return [];
        }

        return $news;
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
                [
                    ':author_id'   => User::getLoggedId(),
                    ':content'     => $message,
                    ':condition'   => $condition,
                    ':important'   => $important,
                    ':web_display' => $web_display,
                    'active'       => 1
                ],
                [
                    ':author_id'   => DBConnection::PARAM_INT,
                    ':important'   => DBConnection::PARAM_INT,
                    ':web_display' => DBConnection::PARAM_INT,
                ]
            );
        }
        catch(DBException $e)
        {
            throw new NewsException('Database error while creating message.');
        }
        catch(Exception $e)
        {
            throw new NewsException('Error while creating message.');
        }

        writeNewsXML();
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
            DBConnection::get()->delete("news", "`id` = :id", [":id" => $id], [":id" => DBConnection::PARAM_INT]);
        }
        catch(DBException $e)
        {
            return false;
        }

        writeNewsXML();

        return true;
    }
}