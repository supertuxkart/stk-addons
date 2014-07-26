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
    exit_json_error("data-type param is not defined or is empty");
}

switch ($_GET["data-type"])
{
    case "addon";
        $errors = Validate::ensureInput($_GET, ["search-filter", "query"]);
        if ($errors)
        {
            exit_json_error(_h("One or more fields are empty. This should never happen"));
        }

        switch ($_GET["search-filter"])
        {
            case "type":
                if (!isset($_GET['addon-type']) || !Addon::isAllowedType($_GET['addon-type']))
                {
                    exit_json_error(sprintf("invalid addon_type = %s is not recognized", $_POST["addon_type"]));
                }

                $results = Addon::search($_GET['query']);

                // Populate our addon list
                $addon_list = [];
                foreach ($results as $result)
                {
                    $a = Addon::get($result['id']);
                    if ($a->getType() === $_GET['type'])
                    {
                        $icon = ($_GET['type'] === Addon::KART) ? $a->getImage(true) : null;
                        $addon_list[] = [
                            'id'       => $result['id'],
                            'name'     => $result['name'],
                            'featured' => Addon::isFeatured($a->getStatus()),
                            'icon'     => File::getPath($icon)
                        ];
                    }
                }
                exit_json_success("", ["addons" => $addon_list]);
                break;

            case "name": // return an array of names
                $addons = Addon::search($_GET['query']);
                $names = [];

                foreach ($addons as $addon)
                {
                    $names[] = $addon["id"];
                }

                exit_json_success("", ["addons" => $names]);
                break;

            default:
                exit_json_error("search_filter unknown");
                break;
        }
        break;

    case "bug":
        $errors = Validate::ensureInput($_GET, ["search-filter"]);
        if ($errors)
        {
            exit_json_error(_h("One or more fields are empty. This should never happen"));
        }

        // search also the description
        $search_description = false;
        if (isset($_GET["search-description"]) && Util::getCheckboxInt($_GET["search-description"]))
        {
            $search_description = true;
        }

        $bugs = [];
        try
        {
            $bugs = Bug::search($_GET["search-title"], $_GET["search-filter"], $search_description);
        }
        catch(BugException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success("", ["bugs-all" => StkTemplate::get('bugs-all.tpl')->assign("bugs", ["items" => $bugs])->toString()]);
        break;

    case "user":
        $errors = Validate::ensureInput($_GET, ["search-term"]);
        if ($errors)
        {
            exit_json_error(_h("One or more fields are empty. This should never happen"));
        }

        $return_html = isset($_GET["return-html"]) ? true : false;
        $users = [];
        try
        {
            $users = User::search($_GET["search-term"]);
        }
        catch(BugException $e)
        {
            exit_json_error($e->getMessage());
        }

        $templateUsers = [];
        foreach ($users as $user)
        {
            // Make sure that the user is active, or the viewer has permission to
            // manage this type of user
            if ($user->isActive() || User::hasPermissionOnRole($user->getRole()))
            {
                $templateUsers[] = [
                    'username' => $user->getUserName(),
                    'active'   => $user->isActive()
                ];
            }
        }

        if ($return_html)
        {
            $users_html = StkTemplate::get("user-menu.tpl")
                ->assign("img_location", IMG_LOCATION)
                ->assign("menu_users", $templateUsers)
                ->assign("pagination", "")
                ->toString();
            exit_json_success("", ["users-html" => $users_html]);
        }

        exit_json_success("", ["users" => $templateUsers]);
        break;

    default:
        exit_json_error(sprintf("data_type = %s is not recognized", h($_POST["action"])));
        break;
}
