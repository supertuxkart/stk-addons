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

if (empty($_GET["data-type"]))
{
    exit_json_error("data-type param is not defined or is empty");
}

switch ($_GET["data-type"])
{
    case "addon";
        $errors = Validate::ensureNotEmpty($_GET, ["addon-type", "query", "flags"]);
        if ($errors)
        {
            exit_json_error(_h("One or more fields are empty. This should never happen"));
        }

        $return_html = isset($_GET["return-html"]) ? true : false;
        if (!isset($_GET["query"]))
        {
            $_GET["query"] = "";
        }

        $addons = [];
        try
        {
            $addons = Addon::search($_GET["query"], $_GET["addon-type"], $_GET["flags"]);
        }
        catch(AddonException $e)
        {
            exit_json_error($e->getMessage());
        }

        $template_addons = Addon::filterMenuTemplate($addons, $_GET["addon-type"]);

        if ($return_html)
        {
            $addons_html = StkTemplate::get("addons/menu.tpl")
                ->assign("addons", $template_addons)
                ->assign("pagination", "")
                ->toString();
            exit_json_success("", ["addons-html" => $addons_html]);
        }

        exit_json_success("", ["addons" => $template_addons]);
        break;

        break;

    case "bug":
        $errors = Validate::ensureNotEmpty($_GET, ["search-filter"]);
        if ($errors)
        {
            exit_json_error(_h("One or more fields are empty. This should never happen"));
        }

        if (!isset($_GET["query"]))
        {
            $_GET["query"] = "";
        }

        // search also the description
        $search_description = Util::isCheckboxChecked($_GET, "search-description");

        $bugs = [];
        try
        {
            $bugs = Bug::search($_GET["query"], $_GET["search-filter"], $search_description);
        }
        catch(BugException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success("", ["bugs-all" => StkTemplate::get('bugs/all.tpl')->assign("bugs", ["items" => $bugs])->toString()]);
        break;

    case "user":
        $errors = Validate::ensureNotEmpty($_GET, ["query"]);
        if ($errors)
        {
            exit_json_error(_h("One or more fields are empty. This should never happen"));
        }

        $return_html = isset($_GET["return-html"]) ? true : false;
        $users = [];
        try
        {
            $users = User::search($_GET["query"]);
        }
        catch(BugException $e)
        {
            exit_json_error($e->getMessage());
        }

        $template_users = User::filterMenuTemplate($users);

        if ($return_html)
        {
            $users_html = StkTemplate::get("users/menu.tpl")
                ->assign("img_location", IMG_LOCATION)
                ->assign("users", $template_users)
                ->assign("pagination", "")
                ->toString();
            exit_json_success("", ["users-html" => $users_html]);
        }

        exit_json_success("", ["users" => $template_users]);
        break;

    default:
        exit_json_error(sprintf("data_type = %s is not recognized", h($_POST["action"])));
        break;
}
