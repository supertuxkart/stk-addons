<?php
    session_start();
    define('ROOT','./');
    include_once('../config.php');
    include_once('sql.php');
    $addonId = mysql_real_escape_string(stripslashes($_GET['addonId']));
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
    return true;
?>