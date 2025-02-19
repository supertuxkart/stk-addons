<?php
/**
 * copyright 2011      Stephen Just <stephenjust@users.sourceforge.net>
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
 * Manage the newsfeed that is fed to the game
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
                FROM `{DB_VERSION}_news`
                WHERE `is_dynamic` = 1
                ORDER BY `id` ASC',
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
        {
            throw new NewsException(exception_message_db(_("fetch dynamic news entries")));
        }

        $newest_addons = Statistic::newestAddons();

        $article_title = null;

        $blog_error = false;

        // TODO cache result, maybe add to cron job
        $feed_url = Config::get(Config::FEED_BLOG);
        if (!$feed_url)
        {
            $blog_error = true;
        }

        // TODO log on failure
        $xml_content = @file_get_contents($feed_url);
        if (!$xml_content)
        {
            $blog_error = true;
        }

        $reader = xml_parser_create();
        if (!xml_parse_into_struct($reader, $xml_content, $values, $index))
        {
            StkLog::newEvent(
                'Failed to get feed. XML Error: ' . xml_error_string(xml_get_error_code($reader)),
                LogLevel::ERROR
            );

            $blog_error = true;
        }
        xml_parser_free($reader);

        $start_search = -1;
        $values_count = count($values);

        if ($blog_error !== false)
        {
            $values_count = 0;
        }

        for ($i = 0; $i < $values_count; $i++)
        {
            if ($values[$i]['tag'] === 'ITEM' || $values[$i]['tag'] === 'ENTRY')
            {
                $start_search = $i;
                break;
            }
        }

        if ($start_search !== -1)
        {
            for ($i = $start_search; $i < $values_count; $i++)
            {
                if ($values[$i]['tag'] === 'TITLE')
                {
                    $article_title = $values[$i]['value'];
                    break;
                }
            }
        }

        $news_list_message = "";
        $news_list_link = "";
        $is_in_entry = false;
        $is_tagged = false;
        $is_important = 0;

        // NOTE: if we modify the message here we also have to delete IT manually from the database
        // otherwise we will have duplicates.
        $dynamic_news = [
            [
                "new"       => $newest_addons[Addon::KART],
                "exists"    => false,
                "important" => 0,
                "message"   => "Newest add-on kart: "
            ],
            [
                "new"       => $newest_addons[Addon::TRACK],
                "exists"    => false,
                "important" => 0,
                "message"   => "Newest add-on track: "
            ],
            [
                "new"       => $newest_addons[Addon::ARENA],
                "exists"    => false,
                "important" => 0,
                "message"   => "Newest add-on arena: "
            ],
            [
                "new"       => $article_title,
                "exists"    => false,
                "important" => 0,
                "message"   => "Latest post on https://blog.supertuxkart.net: "
            ],
        ];

        for ($i = 0; $i < $values_count; $i++)
        {
            if ($values[$i]['tag'] === 'ITEM' || $values[$i]['tag'] === 'ENTRY')
            {
                if ($values[$i]['type'] === 'open')
                {
                    $is_in_entry = true;
                }
                elseif ($values[$i]['type'] === 'close')
                {
                    if ($is_tagged)
                    {
                        array_push($dynamic_news, [
                            "new" => $news_list_link,
                            "exists" => false,
                            "important" => $is_important,
                            "message" => $news_list_message
                        ]);
                    }

                    $is_in_entry = false;
                    $is_tagged = false;
                    $is_important = 0;
                }
            }
            if ($is_in_entry)
            {
                if ($values[$i]['tag'] === 'CATEGORY')
                {
                    if (isset($values[$i]['attributes']['TERM']))
                    {
                        if (Util::str_contains($values[$i]['attributes']['TERM'], 'stk_news_list'))
                        {
                            $is_tagged = true;
                            $is_important = 0;
                        }
                        elseif (Util::str_contains($values[$i]['attributes']['TERM'], 'stk_important_news_list'))
                        {
                            $is_tagged = true;
                            $is_important = 1;
                        }
                    }
                }
                elseif ($values[$i]['tag'] === 'LINK')
                {
                    if (isset($values[$i]['attributes']['REL']) && $values[$i]['attributes']['REL'] === 'alternate')
                    {
                        if (isset($values[$i]['attributes']['HREF']))
                        {
                            $news_list_link = $values[$i]['attributes']['HREF'];
                        }
                        if (isset($values[$i]['attributes']['TITLE']))
                        {
                            $news_list_message = $values[$i]['attributes']['TITLE'] . '%%%STKNEWSLIST%%%';
                        }
                    }
                }
            }
        }

        // replace/delete old entries
        foreach ($dynamic_entries as $entry)
        {
            $news_list_delete_flag = Util::str_contains($entry["content"], "%%%STKNEWSLIST%%%");
            foreach ($dynamic_news as $key => $value)
            {
                $news = $dynamic_news[$key];
                if (Util::str_starts_with($entry["content"], $news["message"]))
                {
                    $cur_news = mb_substr($entry["content"], mb_strlen($news["message"]));
                    if ($cur_news !== $news["new"])
                    {
                        // pattern matches but our value differs, so delete it, and create it below
                        static::delete($entry["id"]);
                    }
                    else
                    {
                        $dynamic_news[$key]["exists"] = true;
                    }
                    $news_list_delete_flag = false;
                    // this assumes only one match per pattern, multiple news entry can not match the same pattern
                    break;
                }
            }
            if ($news_list_delete_flag)
            {
                static::delete($entry["id"]);
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
                        ":content"       => $news["message"] . $news["new"],
                        "is_web_display" => 1,
                        "is_dynamic"     => 1,
                        "is_important"   => $news["important"]
                    ],
                    [
                        ':is_web_display' => DBConnection::PARAM_BOOL,
                        ':is_dynamic'     => DBConnection::PARAM_BOOL,
                        ':is_important'   => DBConnection::PARAM_BOOL
                    ]
                );
            }
            catch (DBException $e)
            {
                throw new NewsException(exception_message_db(_("create dynamic news entry")));
            }
        }
    }

    /**
     * Get active news articles flagged as web-visible.
     * This method will silently fail
     *
     * @return array
     */
    public static function getWebVisible()
    {
        try
        {
            $items = DBConnection::get()->query(
                'SELECT `content` FROM `{DB_VERSION}_news`
                WHERE `is_active` = 1
                AND `is_web_display` = 1
                ORDER BY `date` DESC',
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
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
     * This method will silently fail
     *
     * @return array
     */
    public static function getActive()
    {
        try
        {
            $news = DBConnection::get()->query(
                "SELECT N.*, `U`.`username` AS `author`
                FROM `{DB_VERSION}_news` N
                LEFT JOIN `{DB_VERSION}_users` U
                    ON N.`author_id` = U.`id`
                WHERE N.`is_active` = '1'
                    ORDER BY `date` DESC",
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
        {
            return [];
        }

        return $news;
    }

    /**
     * Get news data for all posts
     * This method will silently fail
     *
     * @return array
     */
    public static function getAll()
    {
        try
        {
            $news = DBConnection::get()->query(
                'SELECT N.*, U.`username` AS `author`
                FROM `{DB_VERSION}_news` N
                LEFT JOIN `{DB_VERSION}_users` U
                    ON N.`author_id` = U.`id`
                ORDER BY `date` DESC',
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
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
                throw new NewsException(
                    'Version comparison should contain three tokens, only found: ' . $count_condition_check
                );
            }

            try
            {
                Validate::versionString($condition_check[2]);
            }
            catch (ValidateException $e)
            {
                throw new NewsException($e->getMessage());
            }
        }

        try
        {
            DBConnection::get()->insert(
                'news',
                [
                    ':author_id'      => $author_id,
                    ':content'        => $message,
                    ':condition'      => $condition,
                    ':is_important'   => $important,
                    ':is_web_display' => $web_display,
                    ':is_dynamic'     => $dynamic,
                    'is_active'       => 1
                ],
                [
                    ':author_id'      => DBConnection::PARAM_INT,
                    ':is_important'   => DBConnection::PARAM_BOOL,
                    ':is_web_display' => DBConnection::PARAM_BOOL,
                    ':is_dynamic'     => DBConnection::PARAM_BOOL
                ]
            );
        }
        catch (DBException $e)
        {
            throw new NewsException(exception_message_db(_('create a news entry')));
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
        catch (DBException $e)
        {
            throw new NewsException(exception_message_db(_("delete a news entry")));
        }
    }
}
