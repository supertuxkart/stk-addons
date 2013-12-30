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
require_once(INCLUDE_DIR . 'Music.class.php');

AccessControl::setLevel(NULL);

include('include/top.php');
echo '</head><body>';
include(ROOT.'include/menu.php');

$music_tracks = Music::getAllByTitle();
if (count($music_tracks) === 0) {
    include('include/footer.php');
    exit;
}

echo '<div id="content">';
echo '<table border="0">';

foreach ($music_tracks AS $track) {
    echo '<tr>';
    $link = '<a href="'.DOWN_LOCATION.'music/'.$track->getFile().'">'.$track->getFile().'</a>';
    echo "<td>{$track->getTitle()}</td><td>{$track->getArtist()}</td><td>{$track->getLicense()}</td><td>$link</td>";
    echo '</tr>';
}

echo '</table>';
echo '</div>';

include('include/footer.php');
?>
