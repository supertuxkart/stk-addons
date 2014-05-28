<?php
/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
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

if (!isset($_POST["action"]) || empty($_POST["action"]))
{
    echo json_encode(array("error" => "action param is not defined or is empty"));
    exit;
}

switch (strtolower($_POST["action"]))
{
    case "search":
        $errors = Validate::ensureInput($_POST, array("search-filter"));
        if(!empty($errors))
        {
            echo json_encode(array("error" => "one or more fields are empty"));
            exit;
        }

        // search also the description
        $search_description = false;
        if(isset($_POST["search-description"]) && $_POST["search-description"] === "on")
        {
            $search_description = true;
        }

        echo json_encode(Bug::search($_POST["search-title"], $_POST["search-filter"], $search_description));
        break;
    default:
        echo json_encode(array("error" => sprintf("action = %s is not recognized", $_POST["action"])));
        break;
}
