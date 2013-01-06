<?php
/**
 * copyright 2013 Stephen Just <stephenjust@users.sourceforge.net>
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

define('ROOT','./');
require('include.php');
AccessControl::setLevel(NULL);

include('include/top.php');
echo '</head><body>';
include(ROOT.'include/menu.php');

$music_list_query = 'SELECT * FROM `'.DB_PREFIX.'music`
    ORDER BY `title` ASC';
$music_list_handle = sql_query($music_list_query);
if (!$music_list_handle || mysql_num_rows($music_list_handle) == 0) {
    // no items
} else {
    echo '<div id="content">';
    echo '<table border="0">';
    
    for ($i = 0; $i < mysql_num_rows($music_list_handle); $i++) {
	echo '<tr>';
	$result = mysql_fetch_assoc($music_list_handle);
	$link = '<a href="'.DOWN_LOCATION.'music/'.$result['file'].'">'.$result['file'].'</a>';
	echo "<td>{$result['title']}</td><td>{$result['artist']}</td><td>{$result['license']}</td><td>$link</td>";
	echo '</tr>';
    }
    
    echo '</table>';
    echo '</div>';
}


include('include/footer.php');
?>
