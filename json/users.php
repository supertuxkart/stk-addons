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

if (!isset($_POST["action"]) || !isset($_POST["user-id"]) || empty($_POST["user-id"]))
{
    exit(json_encode(["error" => "action/user param is not defined or is empty"]));
}

if (!User::isLoggedIn())
{
    exit(json_encode(["error" => "You are not a logged in"]));
}

$user_id = $_POST["user-id"];
switch ($_POST["action"])
{
    case "edit-profile":
        $homepage = isset($_POST["homepage"]) ? $_POST["homepage"] : "";
        $real_name = isset($_POST["realname"]) ? $_POST["realname"] : "";

        try
        {
            User::updateProfile($user_id, $_POST["homepage"], $_POST["realname"]);
        }
        catch(UserException $e)
        {
            exit(json_encode(["error" => $e->getMessage()]));
        }

        echo json_encode(["success" => _h("Profile updated")]);
        break;

    case "edit-role":
        $errors = Validate::ensureInput($_POST, ["role"]);
        if ($errors)
        {
            exit(json_encode(["error" => _h("Role field is empty")]));
        }

        $role = $_POST["role"];
        $available = isset($_POST["available"]) ? $_POST["available"] : "";

        try
        {
            User::updateRole($user_id, $role, $available);
        }
        catch(UserException $e)
        {
            exit(json_encode(["error" => $e->getMessage()]));
        }

        echo json_encode(["success" => _h("Role edited successfully")]);
        break;

    default:
        echo json_encode(["error" => sprintf("action = %s is not recognized", h($_POST["action"]))]);
        break;
}
