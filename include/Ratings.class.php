<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
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
     * @param ClientSession $session
     */
    public function Ratings($addon_id, $session = NULL) {
        $this->addon_id = $addon_id;
        
        $this->fetchAvgRating();
        $this->fetchNumRatings();
        $this->fetchUserVote($session);
    }

    /**
     * Delete all ratings for an add-on
     * @return boolean Success
     */
    public function delete() {
        $query = 'DELETE FROM `'.DB_PREFIX.'votes`
            WHERE `addon_id` = \''.$this->addon_id.'\'';
        $handle = sql_query($query);
        if (!$handle)
            return false;
        return true;
    }

    public function displayUserRating() {
	$current_rating = $this->getUserVote();
	$sel_1 = ($current_rating == 1) ? 'checked' : NULL;
	$sel_2 = ($current_rating == 2) ? 'checked' : NULL;
	$sel_3 = ($current_rating == 3) ? 'checked' : NULL;
	$string = '<span id="user-rating">';
	$string .= '<input type="radio" name="rating" id="rating-1" onclick="addRating(1,\''.$this->addon_id.'\',\'user-rating\',\'rating-container\');" '.$sel_1.'/><label for="rating-1"><div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 33%"></div></div></label><br />'; // 1 star
	$string .= '<input type="radio" name="rating" id="rating-2" onclick="addRating(2,\''.$this->addon_id.'\',\'user-rating\',\'rating-container\');" '.$sel_2.'/><label for="rating-2"><div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 66%"></div></div></label><br />'; // 2 stars
	$string .= '<input type="radio" name="rating" id="rating-3" onclick="addRating(3,\''.$this->addon_id.'\',\'user-rating\',\'rating-container\');" '.$sel_3.'/><label for="rating-3"><div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 100%"></div></div></label>'; // 3 stars
	$string .= '</span>';
	return $string;
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
        return (int) ($this->avg_rating / $this->max_rating * 100);
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
     * Return a string saying 'x Vote(s)'
     * @return string
     */
    public function getRatingString() {
        if ($this->getNumRatings() != 1) {
            return $this->getNumRatings().' Votes';
        } else {
            return $this->getNumRatings().' Vote';
        }
    }
    
    /**
     * Get the user's vote from the database
     * @param ClientSession $session
     */
    private function fetchUserVote($session = NULL) {
	if ($session !== NULL) {
	    $userid = $session->getUserId();
	} else {
	    if (!User::$logged_in)
		return;
	    $userid = $_SESSION['userid'];
	}
        
        $query = "SELECT `id`,`vote`
            FROM `".DB_PREFIX."votes`
            WHERE `addon_id` = '$this->addon_id'
            AND `user_id` = '$userid'";
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
     * Set the user's vote (when using client)
     * @param integer $vote
     * @param ClientSession $session
     * @return boolean Success
     */
    public function setClientVote($vote, $session) {
        // Round to integer
        $vote = intval($vote);
        
        if ($vote < $this->min_rating || $vote > $this->max_rating)
            return false;
        
        if ($this->user_vote === false) {
            $query = "INSERT INTO `".DB_PREFIX."votes`
                (`user_id`, `addon_id`, `vote`)
                VALUES
                ('".$session->getUserId()."', '".$this->addon_id."', ".$vote.");";
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
