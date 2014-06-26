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
    exit(json_encode(array("error" => "action param is not defined or is empty")));
}

switch (strtolower($_POST["action"]))
{

    case "edit-role": // edit a role permissions or maybe the role name in the future
        $errors = Validate::ensureInput($_POST, array("role", "permissions"));
        if (!empty($errors))
        {
            exit(json_encode(array("error" => implode("<br>", $errors))));
        }
        if (!is_array($_POST["permissions"]))
        {
            exit(json_encode(array("error" => "The permissions param is not an array")));
        }

        try
        {
            AccessControl::setPermissions($_POST["role"], $_POST["permissions"]);
        }
        catch(AccessControlException $e)
        {
            exit(json_encode(array("error" => $e->getMessage())));
        }

        echo json_encode(array("success" => "Permissions set successfully"));
        break;

    case "get-role": // get the permission of a role
        $errors = Validate::ensureInput($_POST, array("role"));
        if (!empty($errors))
        {
            exit(json_encode(array("error" => implode("<br>", $errors))));
        }

        if (!User::hasPermissionOnRole("root"))
        {
            exit(json_encode(array("error" => "You do not have the necessary permission to get a role")));
        }
        if (!AccessControl::isRole($_POST["role"]))
        {
            exit(json_encode(array("error" => "The role is not valid")));
        }

        echo json_encode(array("success" => "", "permissions" => AccessControl::getPermissions($_POST["role"])));
        break;

    default:
        echo json_encode(array("error" => sprintf("action = %s is not recognized", h($_POST["action"]))));
        break;
}
