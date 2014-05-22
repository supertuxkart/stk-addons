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

/**
 * json/image_list.php
 * This file provides a json-formatted list of all available images for an
 * addon passed through the "id" parameter.
 */
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");
AccessControl::setLevel(null);

// Quit if no ID was passed
if (!isset($_GET['id']))
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

$addon_id = $_GET['id'];
if (!Addon::exists($addon_id))
{
    header('HTTP/1.0 404 Not Found');
    exit;
}

// Addon exists, get images
$addon = new Addon($addon_id);
$license = $addon->getLicense();

// This isn't really JSON, but no reason to wrap one value in braces
echo $license;
