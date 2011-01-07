<?php
/*
copyright 2010 Lucas Baudin <xapantu@gmail.com>                   
                                                                          
This file is part of stkaddons

stkaddons is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

stkaddons is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of       
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
*/

mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
mysql_select_db(DB_NAME) or die(mysql_error());

function sql_get_all($table)
{
    return mysql_query("SELECT * FROM ".$table);
}

function sql_get_all_where($table, $property, $value)
{
    return mysql_query("SELECT * FROM ".$table." WHERE `$property` = '$value'");
}

function sql_update($table, $property_select, $value_select, $property_change, $new_value)
{
    $request = "UPDATE `".DB_NAME."`.`".$table."`
                        SET `$property_change` =  '$new_value'
                        WHERE `".$table."`.`$property_select` = $value_select;";
    //echo $request;
    return mysql_query($request) or die(mysql_error());
}

function sql_remove_where($table, $property, $value)
{
    $request = "DELETE FROM ".$table." WHERE `$property` = '$value'";
    //echo $request;
    return mysql_query($request) or die(mysql_error());
}

function sql_insert($table, $properties, $values)
{
    $field = "";
    $first= true;
    foreach($properties as $propertie)
    {
        if(!$first)
        {
            $field .= ", ";
        }
        $field .= "`$propertie`";
        $first = false;
    }
    $first= true;
    $field_ = "";
    foreach($values as $value)
    {
        if(!$first)
        {
            $field_ .= ", ";
        }
        $field_ .= "'$value'";
        $first = false;
    }
    $req = "INSERT INTO `".DB_NAME."`.`".DB_PREFIX.$table."` (
                        $field) VALUES($field_)";
    return mysql_query($req) or die(mysql_error());
}

function sql_next($sql_query)
{
    $exist = true;
    $array = mysql_fetch_array($sql_query) or $exist = false;
    if($exist)
    {
        return $array;
    }
    else
    {
        return false;
    }
}
?>
