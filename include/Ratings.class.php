<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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
 * Class to handle add-on ratings
 * @author computerfreak97, sj04736
 */
class Ratings {
    private $addon_id = NULL;
    private $min_rating = 1;
    private $max_rating = 3;
    private $avg_rating = 0;
    private $count = 0;

    /**
     * The user's current vote (or false if not logged in or haven't voted)
     * @var mixed A number, or false
     */
    private $user_vote = false;
    private $user_vote_id = false;
    
    /**
     * Constructor
     * @param string $addon_id ID of addon to use
     */
    public function Ratings($addon_id) {
        $this->addon_id = $addon_id;
        
        $this->fetchAvgRating();
        $this->fetchNumRatings();
        $this->fetchUserVote();
    }
    
    /**
     * Calculate the average rating (rounded to the nearest integer)
     */
    private function fetchAvgRating() {
        $query = "SELECT avg(vote)
            FROM `".DB_PREFIX."votes`
            WHERE `addon_id` = '".$this->addon_id."'";
        $handle = sql_query($query);
        $result = mysql_fetch_assoc($handle);
        $this->avg_rating = $result['avg(vote)'];
    }
    
    /**
     * Get the average rating, rounded to the nearest integer
     * @return integer Average rating
     */
    public function getAvgRating() {
        return $this->avg_rating;
    }
    
    /**
     * Gets the percentage of total possible rating value
     * @return integer percent value
     */
    public function getAvgRatingPercent() {
        $num_possible_ratings = $this->max_rating - $this->min_rating + 1;
        return (int) ($this->avg_rating / $num_possible_ratings * 100);
    }
    
    /**
     * Get the number of ratings in the database
     */
    private function fetchNumRatings() {
        $query = "SELECT count(vote)
            FROM `".DB_PREFIX."votes`
                WHERE `addon_id` = '".$this->addon_id."'";
        $handle = sql_query($query);
        $result = mysql_fetch_assoc($handle);
        $this->count = intval($result['count(vote)']);
    }
    
    /**
     * Get the number of ratings
     * @return integer Number of ratings
     */
    public function getNumRatings() {
        return $this->count;
    }
    
    /**
     * Get the user's vote from the database
     */
    private function fetchUserVote() {
        if (!User::$logged_in)
            return;
        
        $query = "SELECT `id`,`vote`
            FROM `".DB_PREFIX."votes`
            WHERE `addon_id` = '".$this->addon_id."'
            AND `user_id` = '".$_SESSION['userid']."'";
        $handle = sql_query($query);
        
        if (mysql_num_rows($handle) == 0)
            return;
        
        $result = mysql_fetch_assoc($handle);
        $this->user_vote = $result['vote'];
        $this->user_vote_id = $result['id'];
    }
    
    /**
     * Get the user's vote - a number if there is a vote, false if not
     * @return mixed A number or false
     */
    public function getUserVote() {
        return $this->user_vote;
    }
    
    /**
     * Set the user's vote
     * @param integer $vote
     * @return boolean Success
     */
    public function setUserVote($vote) {
        // Round to integer
        $vote = intval($vote);
        
        if (!User::$logged_in)
            return false;
        
        if ($vote < $this->min_rating || $vote > $this->max_rating)
            return false;
        
        if ($this->user_vote === false) {
            $query = "INSERT INTO `".DB_PREFIX."votes`
                (`user_id`, `addon_id`, `vote`)
                VALUES
                ('".$_SESSION['userid']."', '".$this->addon_id."', ".$vote.");";
        } else {
            $query = 'UPDATE `'.DB_PREFIX.'votes`
                SET `vote` = '.$vote.'
                WHERE `id` = '.$this->user_vote_id;
        }
        $handle = sql_query($query);
        if (!$handle)
            return false;
        
        $this->fetchAvgRating();
        $this->fetchNumRatings();
        $this->fetchUserVote();
        return true;
    }
    
    /**
     * Gets the percentage of total possible rating value
     * @return integer percent value
     */
    public function getUserVotePercent() {
        if ($this->user_vote === false)
            return 0;

        $num_possible_ratings = $this->max_rating - $this->min_rating + 1;
        return intval(($this->user_vote / $num_possible_ratings) * 100);
    }
}
?>
