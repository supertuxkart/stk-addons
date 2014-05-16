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
require_once(INCLUDE_DIR . 'SLocale.class.php');

// Get the current page address (without "lang" parameter)
$page_url = $_SERVER['REQUEST_URI'];
if (strstr($page_url, '?') === false)
{
    $page_url .= '?';
}
// Clean up the new url
$page_url = preg_replace('/lang=[a-z_]+/i', null, $page_url);
$page_url = preg_replace('/[&]+/i', '&', $page_url);
$page_url = preg_replace('/\?&/i', '?', $page_url);

// Set the locale
new SLocale();
