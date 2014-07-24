<?php
/**
 * copyright 2011 computerfreak97
 *           2014 Daniel Butum <danibutum at gmail dot com>
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
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");
AccessControl::setLevel(AccessControl::PERM_VIEW_BASIC_PAGE);

if (!isset($_GET['addon-id']))
{
    exit_json_error('No addon id provided');
}

if (!Addon::exists($_GET['addon-id']))
{
    exit_json_error('The addon does not exist ' . h($_GET['addon-id']));
}

$rating = new Ratings($_GET['addon-id']);

// update star ratings
if ($rating->getNumRatings() != 1)
{
    $numRatingsString = $rating->getNumRatings() . ' Votes';
}
else
{
    $numRatingsString = $rating->getNumRatings() . ' Vote';
}
$other_options = ["width" => $rating->getAvgRatingPercent(), "num-ratings" => $numRatingsString];

// set rating
if (isset($_GET['rating']))
{
    try
    {
        $rating->setUserVote($_GET['rating']);
    }
    catch(RatingsException $e)
    {
        exit_json_error($e->getMessage());
    }

    exit_json_success("Rating set", $other_options);
}

// no set rating just get the addon votes
exit_json_success("", $other_options);
