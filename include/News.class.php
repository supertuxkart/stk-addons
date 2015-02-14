<?php
/**
 * copyright 2011      Stephen Just <stephenjust@users.sourceforge.net>
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
 * Manage the newsfeed that is fed to the game
 *
 * @author Stephen
 */
class News
{

    /**
     * Refresh all the dynamic entries in the database
     *
     * @throws NewsException
     */
    public static function refreshDynamicEntries()
    {
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
            throw new NewsException("Failed to fetch dynamic news entries");
        }

        $newest_addons = Statistic::newestAddons();
        $dynamic_news = [
            [
                "new"     => $newest_addons[Addon::KART],
                "exists"  => false,
                "message" => "Newest add-on kart: "
            ],
            [
                "new"     => $newest_addons[Addon::TRACK],
                "exists"  => false,
                "message" => "Newest add-on track: "
            ],
            [
                "new"     => $newest_addons[Addon::ARENA],
                "exists"  => false,
                "message" => "Newest add-on arena: "
            ],
            [
                "new"     => News::getLatestBlogPost(),
                "exists"  => false,
                "message" => "Latest post on stkblog.net: "
            ],
        ];

        // replace/delete old entries
        foreach ($dynamic_entries as $entry)
        {
            foreach ($dynamic_news as $key => $value)
            {
                $news = $dynamic_news[$key];
                $pattern = sprintf("/^%s(.*)$/i", $news["message"]);
                if (preg_match($pattern, $entry["content"], $matches))
                {
                    if ($matches[1] !== $news["new"])
                    {
                        // pattern matches but our value differs, so delete it, and create it below
                        static::delete($entry["id"]);
                    }
                    else
                    {
                        $dynamic_news[$key]["exists"] = true;
                    }

                    // this assumes only one match per pattern, multiple news entry can not match the same pattern
                    break;
                }
            }
        }

        // create new entries
        foreach ($dynamic_news as $news)
        {
            // news entry already exists or the new record is invalid
            if ($news["exists"] || !$news["new"])
            {
                continue;
            }

            // insert new record
            try
            {
                DBConnection::get()->insert(
                    'news',
                    [
                        ":content"    => $news["message"] . $news["new"],
                        "web_display" => 1,
                        "dynamic"     => 1
                    ],
                    [
                        ':web_display' => DBConnection::PARAM_INT,
                        ':dynamic'     => DBConnection::PARAM_INT
                    ]
                );
            }
            catch(DBException $e)
            {
                throw new NewsException("Failed to create dynamic news entry");
            }
        }
    }

    /**
     * Get the last article title, TODO cache result, maybe add to cron job
     *
     * @return string|null
     * @throws NewsException
     */
    private static function getLatestBlogPost()
    {
        $feed_url = Config::get(Config::FEED_BLOG);
        if (!$feed_url)
        {
            return null;
        }

        $xml_content = file_get_contents($feed_url);
        if (!$xml_content)
        {
            return null;
        }

        $reader = xml_parser_create();
        if (!xml_parse_into_struct($reader, $xml_content, $values, $index))
        {
            Log::newEvent('Failed to get feed. XML Error: ' . xml_error_string(xml_get_error_code($reader)));

            return null;
        }
        xml_parser_free($reader);

        $start_search = -1;
        $values_count = count($values);
        for ($i = 0; $i < $values_count; $i++)
        {
            if ($values[$i]['tag'] === 'ITEM' || $values[$i]['tag'] === 'ENTRY')
            {
                $start_search = $i;
                break;
            }
        }

        if ($start_search === -1)
        {
            return null;
        }

        $article_title = null;
        for ($i = $start_search; $i < $values_count; $i++)
        {
            if ($values[$i]['tag'] === 'TITLE')
            {
                $article_title = $values[$i]['value'];
                break;
            }
        }

        return $article_title;
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
     * Create a news entry from the user interface
     *
     * @param int    $author_id   the user who created the news entry
     * @param string $message     the message to display
     * @param string $condition   display only on certain stk versions, TODO better document
     * @param bool   $important   create a notification while creating the news
     * @param bool   $web_display display on the website
     * @param bool   $dynamic     tells if the news entry was created by a user action or automatically
     *
     * @throws NewsException
     */
    public static function create($author_id, $message, $condition, $important, $web_display, $dynamic = false)
    {
        // Make sure no invalid version number sneaks in
        if (Util::str_icontains($condition, "stkversion"))
        {
            $condition_check = explode(" ", $condition);
            $count_condition_check = count($condition_check);

            if ($count_condition_check !== 3)
            {
                throw new NewsException('Version comparison should contain three tokens, only found: ' . $count_condition_check);
            }

            try
            {
                Validate::versionString($condition_check[2]);
            }
            catch(ValidateException $e)
            {
                throw new NewsException($e->getMessage());
            }
        }

        try
        {
            DBConnection::get()->insert(
                'news',
                [
                    ':author_id'   => $author_id,
                    ':content'     => $message,
                    ':condition'   => $condition,
                    ':important'   => $important,
                    ':web_display' => $web_display,
                    ':dynamic'     => $dynamic,
                    'active'       => 1
                ],
                [
                    ':author_id'   => DBConnection::PARAM_INT,
                    ':important'   => DBConnection::PARAM_INT,
                    ':web_display' => DBConnection::PARAM_INT,
                    ':dynamic'     => DBConnection::PARAM_INT
                ]
            );
        }
        catch(DBException $e)
        {
            throw new NewsException('Database error while creating message.');
        }
    }

    /**
     * Delete a news article
     *
     * @param int $id
     *
     * @throws NewsException
     */
    public static function delete($id)
    {
        try
        {
            DBConnection::get()->delete("news", "`id` = :id", [":id" => $id], [":id" => DBConnection::PARAM_INT]);
        }
        catch(DBException $e)
        {
            throw new NewsException("Database error while trying to delete a news article");
        }
    }
}
