<?php
    session_start();
    define('ROOT','../');
    $security = "";
    include_once('../include.php');
    if (!isset($_GET['addonId']))
        die('No addon.');
    if (!User::$logged_in)
        die('Not logged in.');
    $addonId = mysql_real_escape_string(stripslashes($_GET['addonId']));
    if (isset($_GET['rating'])) {
        $rating = intval(stripslashes($_GET['rating']));
        if ($rating > 0 && $rating < 4) {
            $getExistingRatingQuery = "SELECT vote FROM `".DB_PREFIX."votes` WHERE `addon_id` = '".$addonId."' and `user_id` = '".$_SESSION['userid']."'";
            $getExistingRatingHandle = sql_query($getExistingRatingQuery);
            $existingRatingsResult = mysql_fetch_assoc($getExistingRatingHandle);
            $hasExistingRating = $existingRatingsResult['vote'];
            if (!$hasExistingRating) {
                $insertRatingQuery = "INSERT INTO `".DB_NAME.DB_PREFIX."`.`votes` (`id`, `user_id`, `addon_id`, `vote`) VALUES (NULL, '".$_SESSION['userid']."', '".$addonId."', ".$rating.");";
                $insertRatingHandle = sql_query($insertRatingQuery);
            }
        }
        switch ($rating) {
            case 1:
                echo '<div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 33%"></div></div>';
                break;
            case 2:
                echo '<div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 66%"></div></div>';
                break;
            case 3:
                echo '<div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 100%"></div></div>';
                break;
            default:
                echo 'Error.';
                break;
        }
        exit;
    }
        // get average rating
        $getAvgRatingQuery = "SELECT avg(vote) FROM `".DB_PREFIX."votes` WHERE `addon_id` = '".$addonId."'";
        $getAvgRatingHandle = sql_query($getAvgRatingQuery);
        $getRatingsResult = mysql_fetch_assoc($getAvgRatingHandle);
        $avgRating = (intval($getRatingsResult['avg(vote)'])/3)*100; //Turn the average (1-3) into a usable 33-100%

        // get number of ratings
        $getNumRatingQuery = "SELECT count(vote) FROM `".DB_PREFIX."votes` WHERE `addon_id` = '".$addonId."'";
        $getNumRatingHandle = sql_query($getNumRatingQuery);
        $numRatingsResult = mysql_fetch_assoc($getNumRatingHandle);
        $numRatings = intval($numRatingsResult['count(vote)']);

        //create the string with the number of ratings (for use in the function below)
        if ($numRatings != 1) {
            $numRatingsString = $numRatings.' Votes';
        } else {
            $numRatingsString = $numRatings.' Vote';
        }

        // get users previous rating if it exists

        $getExistingRatingQuery = "SELECT vote FROM `".DB_PREFIX."votes` WHERE `addon_id` = '".$addonId."' and `user_id` = '".$_SESSION['userid']."'";
        $getExistingRatingHandle = sql_query($getExistingRatingQuery);
        $existingRatingsResult = mysql_fetch_assoc($getExistingRatingHandle);
        $hasExistingRating = $existingRatingsResult['vote'];
        echo '<div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: '.$avgRating.'%;"></div></div><p>'.$numRatingsString.'</p>';
?>