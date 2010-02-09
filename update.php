<?php
include("include/connectMysql.php");
            $propertie_sql = mysql_query("SELECT * FROM users ");
            while($propertie = mysql_fetch_array($propertie_sql))
            {
            mysql_query("UPDATE `stkaddons_stkbase`.`users` SET `avatar` = '".$propertie['login'].".png' WHERE `users`.`id` =".$propertie['id']." LIMIT 1 ;")or die(mysql_error());
            }
            ?>
