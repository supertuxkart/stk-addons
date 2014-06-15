<?php
/**
 * copyright 2013 Stephen Just <stephenjust@users.sourceforge.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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

require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

if (!isset($_GET["data-type"]) || empty($_GET["data-type"]))
{
    exit(json_encode(array("error" => "data-type param is not defined or is empty")));
}

switch(strtolower($_GET["data-type"]))
{
    case "addon";
        $errors = Validate::ensureInput($_GET, array("search-filter", "query"));
        if (!empty($errors))
        {
            exit(json_encode(array("error" => _h("One or more fields are empty. This should never happen"))));
        }

        switch($_GET["search-filter"])
        {
            case "type":
                if (!isset($_GET['addon-type']) || !Addon::isAllowedType($_GET['addon-type']))
                {
                    exit(json_encode(array("error" => sprintf("invalid addon_type = %s is not recognized", $_POST["addon_type"]))));
                }

                $results = Addon::search($_GET['query']);

                // Populate our addon list
                $addon_list = array();
                foreach ($results as $result)
                {
                    $a = new Addon($result['id']);
                    if ($a->getType() === $_GET['type'])
                    {
                        $icon = ($_GET['type'] === 'karts') ? $a->getImage(true) : null;
                        $addon_list[] = array(
                            'id'       => $result['id'],
                            'name'     => $result['name'],
                            'featured' => $a->getStatus() & F_FEATURED,
                            'icon'     => File::getPath($icon)
                        );
                    }
                }
                echo json_encode(array("addons" => $addon_list));
                break;

            case "name": // return an array of names
                $addons = Addon::search($_GET['query']);
                $names = array();

                foreach($addons as $addon)
                {
                    $names[] = $addon["id"];
                }

                echo json_encode(array("addons" => $names));
                break;

            default:
                echo json_encode(array("error" => "search_filter unknown"));
                break;
        }

        break;

    case "bug":
        $errors = Validate::ensureInput($_GET, array("search-filter"));
        if (!empty($errors))
        {
            exit(json_encode(array("error" => _h("One or more fields are empty. This should never happen"))));
        }

        // search also the description
        $search_description = false;
        if (isset($_GET["search-description"]) && $_GET["search-description"] === "on")
        {
            $search_description = true;
        }

        $bugs = Bug::search($_GET["search-title"], $_GET["search-filter"], $search_description);
        if (empty($bugs))
        {
            echo json_encode(array("error" => _h("Nothing to search for")));
        }
        else
        {
            echo json_encode(array("bugs" => $bugs));
        }
        break;

    case "user":

        break;

    default:
        echo json_encode(array("error" => sprintf("data_type = %s is not recognized", $_POST["action"])));
        break;
}