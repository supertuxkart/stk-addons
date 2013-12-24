<?php
/* copyright 2009 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
 This file is part of stkaddons.                                 
                                                                              
 stkaddons is free software: you can redistribute it and/or      
 modify it under the terms of the GNU General Public License as published by  
 the Free Software Foundation, either version 3 of the License, or (at your   
 option) any later version.                                                   
                                                                              
 stkaddons is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for    
 more details.                                                                
                                                                              
 You should have received a copy of the GNU General Public License along with 
 stkaddons.  If not, see <http://www.gnu.org/licenses/>.   */
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: index.php
Version: 1
Licence: GPLv3
Description: index page

***************************************************************************/
define('ROOT','./');
require('include.php');
//AccessControl::setLevel(NULL);

Template::setFile('index.tpl');
// I18N: Website meta description
Template::$meta_desc = htmlspecialchars(_('This is the official SuperTuxKart add-on repository. It contains extra karts and tracks for the SuperTuxKart game.'));

$tpl = array();
// I18N: Index page title
$tpl['title'] = htmlspecialchars(_('SuperTuxKart Add-ons'));

// Display index menu

$index_menu = array(
    array('href' => File::rewrite('addons.php?type=karts'),
	'label' => htmlspecialchars(_('Karts')),
	'type' => 'karts'),
    array('href' => File::rewrite('addons.php?type=tracks'),
	'label' => htmlspecialchars(_('Tracks')),
	'type' => 'tracks'),
    array('href' => File::rewrite('addons.php?type=arenas'),
	'label' => htmlspecialchars(_('Arenas')),
	'type' => 'arenas'),
    array('href' => 'http://trac.stkaddons.net',
	'label' => 'Help',
	'type' => 'help')
);
$tpl['index_menu'] = $index_menu;
/* commented out by Glenn
// Display news messages
$news_messages = array();
// Note most downloaded track and kart
$pop_kart = stat_most_downloaded('karts');
$pop_track = stat_most_downloaded('tracks');
$news_messages[] = sprintf(htmlspecialchars(_('The most downloaded kart is %s.')),Addon::getName($pop_kart));
$news_messages[] = sprintf(htmlspecialchars(_('The most downloaded track is %s.')),Addon::getName($pop_track));

$newsSql = 'SELECT * FROM `'.DB_PREFIX.'news`
    WHERE `active` = 1
    AND `web_display` = 1
    ORDER BY `date` DESC';
$handle = sql_query($newsSql);
for ($result = sql_next($handle); $result; $result = sql_next($handle)) {
    $news_messages[] = htmlentities($result['content']);
}
$tpl['news_messages'] = $news_messages;*/

Template::assignments($tpl);

Template::display();
?>
