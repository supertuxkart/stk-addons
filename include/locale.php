<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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

// Get the current page address (without "lang" parameter)
$page_url = $_SERVER['REQUEST_URI'];
if (strstr($page_url,'?') === false)
	$page_url .= '?';
// Clean up the new url
$page_url = preg_replace('/lang=[a-z_]+/i',NULL,$page_url);
$page_url = preg_replace('/[&]+/i','&',$page_url);
$page_url = preg_replace('/\?&/i','?',$page_url);
// Time for the language cookie to expire is 1 year in the future
$timestamp_expire = time() + 365*24*3600;
// Set language cookie if it is not set
// Initialize cookie
if(!isset($_COOKIE['lang']))
{
    setcookie('lang', 'en_US', $timestamp_expire);
    putenv('LC_ALL=en_US.UTF-8');
    setlocale(LC_ALL, 'en_US.UTF-8');
    $_COOKIE['lang'] = 'en_US';
}
// Set cookie based on request
if (isset($_GET['lang']))
{
    setcookie('lang', $_GET['lang'], $timestamp_expire);
    putenv('LC_ALL='.$_GET['lang'].'.UTF-8');
    setlocale(LC_ALL, $_GET['lang'].'.UTF-8');
    $_COOKIE['lang'] = $_GET['lang'];
}
else
{
    putenv('LC_ALL='.$_COOKIE['lang'].'.UTF-8');
    setlocale(LC_ALL, $_COOKIE['lang'].'.UTF-8');
}

bindtextdomain('translations', ROOT.'locale');
textdomain('translations');
bind_textdomain_codeset('translations', 'UTF-8');

/**
 * Language string for the currently configured language 
 */
define('LANG', $_COOKIE['lang']);
?>
