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
 * json/addon_search.php
 * This file provides a json-formatted list of addons found by search term.
 */

require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

if (!isset($_GET['type']) || !Addon::isAllowedType($_GET['type']))
{
    header('HTTP/1.0 404 Not Found');
    echo 'Invalid addon type!';
    exit;
}

// Get the list of addons matching our search
if (!isset($_GET['search']))
{
    $init_results = Addon::getAddonList($_GET['type'], true);
    $results = array();
    foreach ($init_results as $init_result)
    {
        $results[]['id'] = $init_result;
    }
}
else
{
    $results = Addon::search($_GET['search']);
}
// Populate our addon list
$addon_list = array();
foreach ($results AS $result)
{
    $a = new Addon($result['id']);
    if ($a->getType() == $_GET['type'])
    {
        $icon = ($_GET['type'] == 'karts') ? $a->getImage(true) : null;
        $addon_list[] = array(
            'id'       => $result['id'],
            'name'     => Addon::getName($result['id']),
            'featured' => $a->getStatus() & F_FEATURED,
            'icon'     => File::getPath($icon)
        );
    }
}

echo json_encode($addon_list);
