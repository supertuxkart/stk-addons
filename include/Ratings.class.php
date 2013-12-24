<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *                Glenn De Jonghe
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

require_once(ROOT. 'include/ClientSession.class.php');
require_once(ROOT. 'include/DBConnection.class.php');
require_once(ROOT. 'include/exceptions.php');
require_once(ROOT. 'include/XMLOutput.class.php');
require_once(ROOT. 'include/sql.php'); //FIXME
require_once(ROOT. 'include/xmlWrite.php');

class RatingsException extends Exception {}

class Ratings {
    private $addon_id = NULL;
    private $min_rating = 0.5;
    private $max_rating = 3.0;
    private $avg_rating = 0;
    private $count = 0;

    /**
     * The user's current vote (or false if not logged in or haven't voted)
     * @var mixed A number, or false
     */
    private $user_vote = false;
    
    /**
     * Constructor
     * @param string $addon_id ID of addon to use
     * @param ClientSession $session
     */
    public function Ratings($addon_id, $fetch_everything = true) {
        $this->addon_id = $addon_id;
        if ($fetch_everything) {
            $this->fetchAvgRating();
            $this->fetchNumRatings();
            $this->fetchUserVote();
        }
    }

    /**
     * 
     * @return string
     */
    public function getAddonId(){
        return $this->addon_id;
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
     * Calculate the average rating
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
     * Get the average rating
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
        try{
        if ($session !== NULL) {
            $userid = $session->getUserId();
        } else {
            if (!User::$logged_in)
                throw new DBException();
            $userid = $_SESSION['userid'];
        }

        $result = DBConnection::get()->query
        (
            "SELECT `vote` 
            FROM `".DB_PREFIX."votes`
            WHERE `user_id` = :user_id
            AND `addon_id` = :addon_id",
            DBConnection::FETCH_ALL,
            array
            (
                ':addon_id'     => (string) $this->addon_id,
                ':user_id'      => (int) $userid
            )
        );
        
        }catch (DBException $e){
            throw new RatingsException(
                _('An unexpected error occured while fetching your last vote.') . ' ' .
                _('Please contact a website administrator if this problem persists.'));
        }
        
        if (count($result) == 0)
            return;
        $this->user_vote = $result[0]['vote'];
    }
    
    /**
     * Get the user's vote - a number if there is a vote, false if not
     * @return mixed A number or false
     */
    public function getUserVote($session = NULL) {
        if ($session !== NULL) {
            $this->fetchUserVote($session);
        }
        return $this->user_vote;
    }
    
    /**
     * 
     * @param float $vote
     * @param ClientSession $session
     * @throws RatingsException
     * @return boolean new vote or not
     */
    public function setUserVote($vote, $session) {
        if ($session !== NULL) {
            $userid = $session->getUserId();
        } else {
        if (!User::$logged_in)
                throw new DBException();
            $userid = $_SESSION['userid'];
        }
        
        if ($vote < $this->min_rating || $vote > $this->max_rating)
            throw new RatingsException(
                _('The rating is out of allowed boundaries.') . ' ' .
                _('Please contact a website administrator if this problem persists.'));
        
        try{
            $count = DBConnection::get()->query
            (
                "INSERT INTO `" . DB_PREFIX ."votes`
                (`user_id`, `addon_id`, `vote`)
                VALUES (:user_id, :addon_id, :rating)
                ON DUPLICATE KEY UPDATE vote = :rating",
                DBConnection::ROW_COUNT,
                array
                (
                    ':addon_id'     => (string) $this->addon_id,
                    ':user_id'      => (int) $userid,
                    ':rating'       => (float) $vote
                )
            );
            if ($count === 1)
                $new_vote = true;
            elseif ($count === 2)
                $new_vote = false;
            else
                throw new DBException();
        }catch (DBException $e){
            throw new RatingsException(
                _('An unexpected error occured while performing your vote.') . ' ' .
                _('Please contact a website administrator if this problem persists.'));
        }
        
        
        $this->fetchAvgRating();
        $this->fetchNumRatings();
        $this->fetchUserVote($session); // FIXME

	// Regenerate the XML files after voting
	writeAssetXML();
	writeNewsXML();	
        
        return $new_vote;
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
