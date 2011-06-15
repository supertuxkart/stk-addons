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

function sql_query($query)
{
    $error = true;
    $sql = mysql_query($query) or $error = false;
    if (!$error)
    {
	return false;
    }
    else
    {
	return $sql;
    }
}

function sql_get_all($table)
{
    $error = true;
    $sql = mysql_query("SELECT * FROM ".DB_PREFIX.$table) or $error = false;
    if(!$error)
    {
        return false;
    }
    else
    {
        return $sql;
    }
}

function sql_get_all_where($table, $property, $value)
{
    // Ensure parameters are arrays for consistency
    if (is_string($property))
    {
	$property = array($property);
    }
    if (is_string($value))
    {
	$value = array($value);
    }
    // Make sure there are as many values as properties
    if (count($value) !== count($property))
    {
	return false;
    }

    // Set base query structure
    $query_form = 'SELECT * FROM '.DB_PREFIX.$table.' WHERE ';
    $expression = array();

    // Loop through all properties and generate equalities
    for ($i = 0; $i < count($property); $i++)
    {
	$expression[] = "`$property[$i]` = '$value[$i]'";
    }
    $expression_string = implode(' AND ',$expression);
    
    return mysql_query($query_form.$expression_string);
}

function sql_update($table, $property_select, $value_select, $property_change, $new_value)
{
    $request = "UPDATE `".DB_NAME."`.`".DB_PREFIX.$table."`
                        SET `$property_change` =  '$new_value'
                        WHERE `".DB_PREFIX.$table."`.`$property_select` = '$value_select';";
    //echo $request;
    return mysql_query($request) or die(mysql_error());
}

function sql_remove_where($table, $property, $value)
{
    $request = "DELETE FROM ".DB_PREFIX.$table." WHERE `$property` = '$value'";
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
    $req = "INSERT INTO `".DB_PREFIX.$table."` (
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
function sql_exist($table, $property, $value)
{
    $error = true;
    $sql = mysql_query("SELECT * FROM ".DB_PREFIX.$table." WHERE `$property` = '$value'");
    $request = mysql_fetch_array($sql) or $error = false;
    return $error;
}

function get_config($config_name) {
	// Validate parameters
	if (!is_string($config_name)) {
		return NULL;
	}
	if (strlen($config_name) < 1) {
		return NULL;
	}

	$config_name = mysql_real_escape_string($config_name);

	global $config_cache;

	if (!isset($config_cache)) {
		$config_cache = array();
		$query = 'SELECT `name`, `value`
			FROM `'.DB_PREFIX.'config`';
		$handle = sql_query($query);
		if (!$handle) return NULL;

		$num_config = mysql_num_rows($handle);
		if ($num_config == 0) {
			return NULL;
		}
		for ($i = 1; $i <= $num_config; $i++) {
			$config = sql_next($handle);
			$config_cache[$config['name']] = $config['value'];
		}
	}
	if (!isset($config_cache[$config_name])) {
		return NULL;
	}
	return $config_cache[$config_name];
}

function set_config($config_name,$config_value) {
	// Validate parameters
	if (!is_string($config_name)) {
		return false;
	}
	if (strlen($config_name) < 1) {
		return false;
	}
	if (is_array($config_value)) {
		return false;
	}
	if (strlen($config_value) < 1) {
		return true; // Not changed because we can't accept null values
	}

	$config_name = mysql_real_escape_string($config_name);
	$config_value = mysql_real_escape_string($config_value);

	global $config_cache;

	// Check if value already exists in database
	if (!is_null(get_config($config_name))) {
		// Value exists
		$set_query = "UPDATE `".DB_PREFIX."config`
			SET `value` = '$config_value'
			WHERE `name` = '$config_name'";
	} else {
		// Value does not exist
		$set_query = "INSERT INTO `".DB_PREFIX."config`
			(`name`,`value`)
			VALUES ('$config_name','$config_value')";
	}
	$set_handle = sql_query($set_query);
	if (!$set_handle) return false;

	// Update cache - first, make sure the cache exists
	get_config($config_name);
	$config_cache[$config_name] = $config_value;

	return true;
}
?>
