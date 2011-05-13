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
$page_url = $_SERVER['PHP_SELF']."?";
foreach($_GET as $key => $value)
{
    if($key != "lang") $page_url .= $key."=".$value."&amp;";
}
// Time for the language cookie to expire is 1 year in the future
$timestamp_expire = time() + 365*24*3600;
// Set language cookie if it is not set
if(!isset($_COOKIE['lang']))
{
    setcookie('lang', 'en_EN', $timestamp_expire);
}
if (isset($_GET['lang'])) { // If the user has chosen a language
    setcookie('lang', $_GET['lang'], $timestamp_expire);
    // Need to reload page to make sure translations are visible
    echo <<< EOF
<html>
    <head>
        <meta http-equiv="refresh" content="0;URL=$page_url">
    </head>
</html>
EOF;
    exit;
}
if (isset($_COOKIE['lang'])) setlocale(LC_ALL, $_COOKIE['lang'].'.UTF-8');

bindtextdomain('translations', ROOT.'locale');
textdomain('translations');
bind_textdomain_codeset('translations', 'UTF-8');
?>
