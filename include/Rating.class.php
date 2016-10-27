<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *           2013      Glenn De Jonghe
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
 * Class Rating to handle add-on ratings
 * @author computerfreak97, sj04736
 */
class Rating
{
    /**
     * @var float
     */
    const MIN_RATING = 0.5;

    /**
     * @var float
     */
    const MAX_RATING = 3.0;

    /**
     * @var string
     */
    private $addon_id;

    /**
     * The average rating for this addon
     * @var float
     */
    private $avg_rating = 0.0;

    /**
     * The number of ratings for this addon
     * @var int
     */
    private $count_ratings = 0;

    /**
     * Indicates if we fetched the ratings count and average for this addon
     * @var bool
     */
    private $fetched_ratings = false;

    /**
     * The user's current vote (or false if not logged in or haven't voted)
     * @var mixed A number, or false
     */
    private $user_vote = false;

    /**
     * Calculate the average rating
     *
     * @param bool $force_fetch
     */
    private function fetchRatings($force_fetch = false)
    {
        // cache results
        if ($this->fetched_ratings && !$force_fetch)
        {
            return;
        }

        try
        {
            $result = DBConnection::get()->query(
                'SELECT AVG(vote) as avg_vote, COUNT(*) as count_vote
                FROM `' . DB_PREFIX . 'votes`
                WHERE `addon_id` = :addon_id',
                DBConnection::FETCH_FIRST,
                [':addon_id' => $this->addon_id]
            );

            $this->avg_rating = (float)$result['avg_vote'];
            $this->count_ratings = (int)$result['count_vote'];
        }
        catch (DBException $e)
        {
            if (DEBUG_MODE)
            {
                trigger_error($e->getMessage());
            }

            $this->avg_rating = 0.0;
            $this->count_ratings = 0;
        }

        $this->fetched_ratings = true;
    }

    /**
     * Get the user's vote from the database
     *
     * @param int $user_id
     *
     * @throws RatingsException
     */
    private function fetchUserVote($user_id)
    {
        // cache result
        if ($this->user_vote !== false)
        {
            return;
        }

        try
        {
            $result = DBConnection::get()->query(
                "SELECT `vote`
                FROM `" . DB_PREFIX . "votes`
                WHERE `user_id` = :user_id
                AND `addon_id` = :addon_id",
                DBConnection::FETCH_FIRST,
                [
                    ':addon_id' => $this->addon_id,
                    ':user_id'  => $user_id
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );

        }
        catch (DBException $e)
        {
            throw new RatingsException(exception_message_db(_('fetch your vote')));
        }

        if (!$result)
        {
            return;
        }
        $this->user_vote = (int)$result['vote'];
    }

    /**
     * Constructor
     *
     * @param string $addon_id ID of addon to use
     */
    private function __construct($addon_id)
    {
        $this->addon_id = $addon_id;
    }

    /**
     * Factory for Rating object
     *
     * @param string $addon_id
     *
     * @return Rating
     */
    public static function get($addon_id)
    {
        return new Rating($addon_id);
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
        catch (DBException $e)
        {
            return false;
        }

        return true;
    }

    /**
     * GEt the rating template string
     *
     * @param int $user_id
     *
     * @return string
     */
    public function displayUserRating($user_id)
    {
        $current_rating = $this->getUserVote($user_id);

        return StkTemplate::get("addons/rating.tpl")
            ->assign("addon_id", $this->addon_id)
            ->assign("rating_1", $current_rating === 1)
            ->assign("rating_2", $current_rating === 2)
            ->assign("rating_3", $current_rating === 3)
            ->toString();
    }

    /**
     * Get the average rating
     *
     * @param bool $force_fetch force the fetch of the average rating
     *
     * @return int Average rating
     */
    public function getAvgRating($force_fetch = false)
    {
        $this->fetchRatings($force_fetch);

        return $this->avg_rating;
    }

    /**
     * Gets the percentage of total possible rating value
     *
     * @param bool $force_fetch force the fetch of the average rating
     *
     * @return int percent value
     */
    public function getAvgRatingPercent($force_fetch = false)
    {
        $this->fetchRatings($force_fetch);

        return (int)($this->avg_rating / static::MAX_RATING * 100);
    }

    /**
     * Get the number of ratings
     *
     * @param bool $force_fetch force the fetch of the average rating
     *
     * @return integer Number of ratings
     */
    public function getNumRatings($force_fetch = false)
    {
        $this->fetchRatings($force_fetch);

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
     * @param int $user_id
     *
     * @return mixed A number or false
     */
    public function getUserVote($user_id)
    {
        $this->fetchUserVote($user_id);

        return $this->user_vote;
    }

    /**
     * Add a vote for a user
     *
     * @param float $vote
     * @param int   $user_id
     *
     * @throws RatingsException
     * @return boolean new vote or not
     */
    public function setUserVote($user_id, $vote)
    {
        if ($vote < static::MIN_RATING || $vote > static::MAX_RATING)
        {
            throw new RatingsException(_h('The rating is out of allowed boundaries.'));
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
                    ':user_id'  => $user_id,
                    ':rating'   => (float)$vote
                ],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new RatingsException(exception_message_db(_('perform your vote')));
        }

        // reset
        $this->fetched_ratings = false;

        // Regenerate the XML files after voting
        writeXML(); // TODO optimize
    }

    /**
     * Gets the percentage of total possible rating value.
     *
     * @return int percent value
     */
    public function getUserVotePercent()
    {
        // TODO find usage
        if ($this->user_vote === false)
        {
            return 0;
        }

        $num_possible_ratings = static::MAX_RATING - static::MIN_RATING + 1;

        return intval(($this->user_vote / $num_possible_ratings) * 100);
    }
}
