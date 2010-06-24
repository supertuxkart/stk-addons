<?php
mysql_connect(DB_HOST, DB_USERNAME, DB_PASSWORD);
mysql_select_db(DB_NAME);

function getAllFromTable(table)
{
    return mysql_query("SELECT * FROM".table);
}
function nextItem(sql_query)
{
    return mysql_fetch_array(sql_query);
}
?>
