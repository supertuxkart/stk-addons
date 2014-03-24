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

require_once(INCLUDE_DIR . 'DBConnection.class.php');
require_once(INCLUDE_DIR . 'sql.php'); // FIXME
require_once(INCLUDE_DIR . 'Addon.class.php');
require_once(INCLUDE_DIR . 'statistics.php');

/**
 * Manage the newsfeed that is fed to the game
 *
 * @author Stephen
 */
class News {

    public static function refreshDynamicEntries() {
        // Get dynamic entries
        try {
            $dynamic_entries = DBConnection::get()->query(
                    'SELECT *
                     FROM `'.DB_PREFIX.'news`
                     WHERE `dynamic` = 1
                     ORDER BY `id` ASC',
                    DBConnection::FETCH_ALL);
        } catch (DBException $e) {
            return false;
        }
        
        // Dynamic newest kart display
        $new_kart = Addon::getName(stat_newest('karts'));
        $existing_id = false;
        foreach ($dynamic_entries AS $entry) {
            if (preg_match('/^Newest add-on kart: (.*)$/i',$entry['content'], $matches)) {
                if ($matches[1] != $new_kart) {
                    // Delete old record
                    try
                    {
                        $del_result = DBConnection::get()->query(
                                    'DELETE FROM `'.DB_PREFIX.'news`
                                    WHERE `id` = :entryid',
                                    DBConnection::NOTHING,
                                    array(
                                        ':entryid'  =>  $entry['id']
                                    )                                
                        );
                    }
                    catch(DBException $e)
                    {
                       echo 'Warning: failed to delete old news record.<br />'; 
                    }
                } else {
                    $existing_id = true;
                    break;
                }
            }
        }
        // Add new entry
        if ($existing_id === false && $new_kart !== false) {
            try
            {
                $ins_result = DBConnection::get()->query(
                            'INSERT INTO `'.DB_PREFIX.'news`
                            (`content`,`web_display`,`dynamic`)
                            VALUES
                            (\'Newest add-on kart: :new_kart\',1,1)',
                            DBConnection::NOTHING,
                            array(
                                ':new_kart' =>  $new_kart
                            )
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest kart news entry.<br />';                
            }
        }
        
        // Dynamic newest track display
        $new_track = Addon::getName(stat_newest('tracks'));
        $existing_id = false;
        foreach ($dynamic_entries AS $entry) {
            if (preg_match('/^Newest add-on track: (.*)$/i',$entry['content'], $matches)) {
                if ($matches[1] != $new_track) {
                    // Delete old record
                    try
                    {
                        $del_result = DBConnection::get()->query(
                                    'DELETE FROM `'.DB_PREFIX.'news`
                                    WHERE `id` = :entryid',
                                    DBConnection::NOTHING,
                                    array(
                                        ':entryid'  =>  $entry['id']
                                    )                                
                        );
                    }
                    catch(DBException $e)
                    {
                       echo 'Warning: failed to delete old news record.<br />'; 
                    }
                } else {
                    $existing_id = true;
                    break;
                }
            }
        }
        // Add new entry
        if ($existing_id === false && $new_track !== false) {
            try
            {
                $ins_result = DBConnection::get()->query(
                            'INSERT INTO `'.DB_PREFIX.'news`
                            (`content`,`web_display`,`dynamic`)
                            VALUES
                            (\'Newest add-on track: :new_track\',1,1)',
                            DBConnection::NOTHING,
                            array(
                                ':new_kart' =>  $new_track
                            )
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest track news entry.<br />';                
            }                
        }
        
        // Dynamic newest arena display
        $new_arena = Addon::getName(stat_newest('arenas'));
        $existing_id = false;
        foreach ($dynamic_entries AS $entry) {
            if (preg_match('/^Newest add-on arena: (.*)$/i',$entry['content'], $matches)) {
                if ($matches[1] != $new_arena) {
                    // Delete old record
                    try
                    {
                        $del_result = DBConnection::get()->query(
                                    'DELETE FROM `'.DB_PREFIX.'news`
                                    WHERE `id` = :entryid',
                                    DBConnection::NOTHING,
                                    array(
                                        ':entryid'  =>  $entry['id']
                                    )                                
                        );
                    }
                    catch(DBException $e)
                    {
                       echo 'Warning: failed to delete old news record.<br />'; 
                    }
                } else {
                    $existing_id = true;
                    break;
                }
            }
        }
        // Add new entry
        if ($existing_id === false && $new_arena !== false) {
            if (!$ins_result)
                echo 'Failed to insert newest arena news entry.<br />';
            try
            {
                $ins_result = DBConnection::get()->query(
                            'INSERT INTO `'.DB_PREFIX.'news`
                            (`content`,`web_display`,`dynamic`)
                            VALUES
                            (\'Newest add-on arena: :new_arena\',1,1)',
                            DBConnection::NOTHING,
                            array(
                                ':new_arena' =>  $new_arena
                            )
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest arena news entry.<br />';                
            }
        }

        // Add message for the latest blog-post
        $latest_blogpost = News::getLatestBlogPost();
        $existing_id = false;
        foreach ($dynamic_entries AS $entry) {
            if (preg_match('/^Latest post on stkblog.net: (.*)$/i',$entry['content'], $matches)) {
                if ($matches[1] != $latest_blogpost) {
                    // Delete old record
                    try
                    {
                        $del_result = DBConnection::get()->query(
                                    'DELETE FROM `'.DB_PREFIX.'news`
                                    WHERE `id` = :entryid',
                                    DBConnection::NOTHING,
                                    array(
                                        ':entryid'  =>  $entry['id']
                                    )                                
                        );
                    }
                    catch(DBException $e)
                    {
                       echo 'Warning: failed to delete old news record.<br />'; 
                    }
                } else {
                    $existing_id = true;
                    break;
                }
            }
        }
        // Add new entry
        if ($existing_id === false && $latest_blogpost !== false) {
            try
            {
                $ins_handle = DBConnection::get()->query(
                            'INSERT INTO `'.DB_PREFIX.'news`
                            (`content`,`web_display`,`dynamic`)
                            VALUES
                            (\'Latest post on stkblog.net: :latest_blogpost\',1,1)',
                            DBConnection::NOTHING,
                            array(
                                ':latest_blogpost' =>  $latest_blogpost
                            )
                );
            }
            catch(DBException $e)
            {
                echo 'Failed to insert newest blog news entry.<br />';                
            }
        }
    }
    
    private static function getLatestBlogPost() {
        $feed_url = ConfigManager::get_config('blog_feed');
        if (strlen($feed_url) == 0)
            return false;
        
        $xmlContents = file($feed_url,FILE_IGNORE_NEW_LINES);
        if (!$xmlContents)
            return false;
        
        $reader = xml_parser_create();
        if (!xml_parse_into_struct($reader,implode('',$xmlContents),$vals,$index))
        {
            echo 'XML Error: '.xml_error_string(xml_get_error_code($reader)).'<br />';
            return false;
        }
        
        $startSearch = -1;
        for ($i = 0; $i < count($vals); $i++) {
            if ($vals[$i]['tag'] == 'ITEM')
            {
                $startSearch = $i;
                break;
            }
        }
        if ($startSearch == -1)
            return false;

        $articleTitle = NULL;
        for ($i = $startSearch; $i < count($vals); $i++) {
            if ($vals[$i]['tag'] == 'TITLE') {
                $articleTitle = $vals[$i]['value'];
                break;
            }
        }
        if ($articleTitle === NULL)
            return false;

        return strip_tags($articleTitle);
    }
    
    /**
     * Get active news articles flagged as web-visible
     * @return array
     */
    public static function getWebVisible() {
        try {
            $items = DBConnection::get()->query(
                    'SELECT `content` FROM `'.DB_PREFIX.'news`
                     WHERE `active` = 1
                     AND `web_display` = 1
                     ORDER BY `date` DESC',
                    DBConnection::FETCH_ALL);
            $ret = array();
            foreach ($items AS $item) {
                $ret[] = htmlspecialchars($item['content']);
            }
            return $ret;
        } catch (DBException $e) {
            return array();
        }
    }
    
    /**
     * Get news data for posts marked active
     * @return array
     */
    public static function getActive() {
        try {
            $news = DBConnection::get()->query(
                    'SELECT `n`.*, `u`.`user` AS `author`
                     FROM `'.DB_PREFIX.'news` `n`
                     LEFT JOIN `'.DB_PREFIX.'users` `u`
                     ON (`n`.`author_id`=`u`.`id`)
                     WHERE `n`.`active` = \'1\'
                     ORDER BY `date` DESC',
                    DBConnection::FETCH_ALL);
            return $news;
        } catch (DBException $e) {
            return array();
        }
    }
    
    /**
     * Get news data for all posts
     * @return array
     */
    public static function getAll() {
        try {
            $news = DBConnection::get()->query(
                    'SELECT `n`.*, `u`.`user` AS `author`
                     FROM `'.DB_PREFIX.'news` `n`
                     LEFT JOIN `'.DB_PREFIX.'users` `u`
                     ON (`n`.`author_id`=`u`.`id`)
                     ORDER BY `date` DESC',
                    DBConnection::FETCH_ALL);
            return $news;
        } catch (DBException $e) {
            return array();
        }
    }
    
    /**
     * Create a news entry
     * @param string $message
     * @param string $condition
     * @param boolean $important
     * @param boolean $web_display
     * @throws Exception
     */
    public static function create($message, $condition, $important, $web_display) {
        try {
            if (!User::$logged_in) throw new Exception();
            DBConnection::get()->query(
                    'INSERT INTO `'.DB_PREFIX.'news`
                     (`author_id`,`content`,`condition`,`important`,`web_display`,`active`)
                     VALUES
                     (:author_id, :message, :condition, :important, :web_display, :active)',
                    DBConnection::NOTHING,
                    array(
                        ':author_id' => (int)       User::$user_id,
                        ':message' =>   (string)    $message,
                        ':condition' => (string)    $condition,
                        ':important' => (int)       $important,
                        ':web_display'=>(int)       $web_display,
                        ':active' =>                1)
                    );
            writeNewsXML();
        } catch (DBException $e) {
            throw new Exception('Database error while creating message.');
        } catch (Exception $e) {
            throw new Exception('Error while creating message.');
        }
    }
    
    /**
     * Delete news article
     * @param integer $id
     * @return boolean
     */
    public static function delete($id) {
        try {
            DBConnection::get()->query(
                    'DELETE FROM `'.DB_PREFIX.'news`
                     WHERE `id` = :id',
                    DBConnection::NOTHING,
                    array(':id' => (int) $id));
            writeNewsXML();
            return true;
        } catch (DBException $e) {
            return false;
        }
    }
}

?>
