<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *           2013      Glenn De Jonghe
 *           2014      Daniel Butum <danibutum at gmail dot com>
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
 * Class Rating to handle add-on ratings
 * @author computerfreak97, sj04736
 */
class Rating
{
    /**
     * @var string
     */
    private $addon_id;

    /**
     * @var float
     */
    private $min_rating = 0.5;

    /**
     * @var float
     */
    private $max_rating = 3.0;

    /**
     * @var int
     */
    private $avg_rating = 0;

    /**
     * @var int
     */
    private $count_ratings = 0;

    /**
     * The user's current vote (or false if not logged in or haven't voted)
     * @var mixed A number, or false
     */
    private $user_vote = false;

    /**
     * Calculate the average rating
     */
    private function fetchAvgRating()
    {
        try
        {
            $result = DBConnection::get()->query(
                'SELECT AVG(vote) as avg_vote
                FROM `' . DB_PREFIX . 'votes`
                WHERE `addon_id` = :addon_id',
                DBConnection::FETCH_ALL,
                [':addon_id' => $this->addon_id]
            );

            $this->avg_rating = $result[0]['avg_vote'];
        }
        catch(DBException $e)
        {
            if (DEBUG_MODE)
            {
                echo $e->getMessage();
            }

            $this->avg_rating = 0.0;
        }
    }

    /**
     * Get the number of ratings in the database
     */
    private function fetchNumRatings()
    {
        try
        {
            $result = DBConnection::get()->query(
                'SELECT count(vote)
                FROM `' . DB_PREFIX . 'votes`
                WHERE `addon_id` = :addon_id',
                DBConnection::FETCH_ALL,
                [':addon_id' => $this->addon_id]
            );
            $this->count_ratings = intval($result[0]['count(vote)']);
        }
        catch(DBException $e)
        {
            if (DEBUG_MODE)
            {
                trigger_error($e->getMessage());
            }
            $this->count_ratings = 0;
        }
    }

    /**
     * Get the user's vote from the database
     *
     * @param ClientSession|null $session
     *
     * @throws RatingsException
     */
    private function fetchUserVote($session = null)
    {
        if ($session !== null)
        {
            $userid = $session->getUserId();
        }
        else
        {
            if (!User::isLoggedIn())
            {
                return;
            }
            $userid = User::getLoggedId();
        }

        try
        {
            $result = DBConnection::get()->query(
                "SELECT `vote`
                FROM `" . DB_PREFIX . "votes`
                WHERE `user_id` = :user_id
                AND `addon_id` = :addon_id",
                DBConnection::FETCH_ALL,
                [
                    ':addon_id' => (string)$this->addon_id,
                    ':user_id'  => $userid
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );

        }
        catch(DBException $e)
        {
            throw new RatingsException(
                _h('An unexpected error occured while fetching your last vote.') . ' ' .
                _h('Please contact a website administrator if this problem persists.')
            );
        }

        if (empty($result))
        {
            return;
        }
        $this->user_vote = (int)$result[0]['vote'];
    }

    /**
     * Constructor
     *
     * @param string $addon_id ID of addon to use
     * @param bool   $fetch_everything
     */
    public function __construct($addon_id, $fetch_everything = true)
    {
        $this->addon_id = (string)$addon_id;

        if ($fetch_everything)
        {
            $this->fetchAvgRating();
            $this->fetchNumRatings();
            $this->fetchUserVote();
        }
    }

    /**
     * @return string
     */
    public function getAddonId()
    {
        return $this->addon_id;
    }

    /**
     * Delete all ratings for an add-on
     * @return boolean Success
     */
    public function delete()
    {
        try
        {
            DBConnection::get()->delete("votes", "`addon_id` = :addon_id", [':addon_id' => $this->addon_id]);
        }
        catch(DBException $e)
        {
            return false;
        }

        return true;
    }

    /**
     * @return string
     */
    public function displayUserRating()
    {
        $current_rating = $this->getUserVote();

        return StkTemplate::get("user-rating.tpl")
            ->assign("addon_id", $this->addon_id)
            ->assign("rating_1", $current_rating === 1)
            ->assign("rating_2", $current_rating === 2)
            ->assign("rating_3", $current_rating === 3)
            ->toString();
    }

    /**
     * Get the average rating
     * @return int Average rating
     */
    public function getAvgRating()
    {
        return $this->avg_rating;
    }

    /**
     * Gets the percentage of total possible rating value
     * @return int percent value
     */
    public function getAvgRatingPercent()
    {
        return (int)($this->avg_rating / $this->max_rating * 100);
    }

    /**
     * Get the number of ratings
     * @return integer Number of ratings
     */
    public function getNumRatings()
    {
        return $this->count_ratings;
    }

    /**
     * Return a string saying 'x Vote(s)'
     * @return string
     */
    public function getRatingString()
    {
        if ($this->getNumRatings() === 1)
        {
            return $this->getNumRatings() . ' Vote';

        }

        return $this->getNumRatings() . ' Votes';
    }

    /**
     * Get the user's vote - a number if there is a vote, false if not
     *
     * @param null|ClientSession $session
     *
     * @return mixed A number or false
     */
    public function getUserVote($session = null)
    {
        if ($session !== null)
        {
            $this->fetchUserVote($session);
        }

        return $this->user_vote;
    }

    /**
     *
     * @param float         $vote
     * @param ClientSession $session
     *
     * @throws RatingsException
     * @return boolean new vote or not
     */
    public function setUserVote($vote, $session = null)
    {
        if ($session !== null)
        {
            $userid = $session->getUserId();
        }
        else
        {
            if (!User::isLoggedIn())
            {
                throw new RatingsException();
            }
            $userid = User::getLoggedId();
        }

        if ($vote < $this->min_rating || $vote > $this->max_rating)
        {
            throw new RatingsException(
                _h('The rating is out of allowed boundaries.') . ' ' .
                _h('Please contact a website administrator if this problem persists.')
            );
        }

        try
        {
            DBConnection::get()->query(
                "INSERT INTO `" . DB_PREFIX . "votes`
                (`user_id`, `addon_id`, `vote`)
                VALUES (:user_id, :addon_id, :rating)
                ON DUPLICATE KEY UPDATE vote = :rating",
                DBConnection::NOTHING,
                [
                    ':addon_id' => $this->addon_id,
                    ':user_id'  => $userid,
                    ':rating'   => (float)$vote
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new RatingsException(
                _h('An unexpected error occured while performing your vote.') . ' ' .
                _h('Please contact a website administrator if this problem persists.')
            );
        }


        $this->fetchAvgRating();
        $this->fetchNumRatings();
        $this->fetchUserVote($session); // FIXME

        // Regenerate the XML files after voting
        writeAssetXML();
        writeNewsXML();
    }

    /**
     * Gets the percentage of total possible rating value
     *
     * @return int percent value
     */
    public function getUserVotePercent()
    {
        if ($this->user_vote === false)
        {
            return 0;
        }

        $num_possible_ratings = $this->max_rating - $this->min_rating + 1;

        return intval(($this->user_vote / $num_possible_ratings) * 100);
    }
}
