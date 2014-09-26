<?php
/**
 * copyright 2012-2014 Stephen Just <stephenjust@users.sf.net>
 *           2014      Daniel Butum <danibutum at gmail dot com>
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
require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");

$error = [
    "code"    => (empty($_GET['e'])) ? null : (int)$_GET['e'],
    "title"   => "",
    "message" => ""
];
$tpl = StkTemplate::get('error-page.tpl');

// Send appropriate error header
switch ($error["code"])
{
    case 401:
        header('HTTP/1.0 401 Unauthorized');
        $error["title"] = _h("401 Unauthorized");
        $error["message"] = _h("You do not have permission to access this page. You will be redirected to the home page.");
        $tpl->setMetaRefresh("index.php");
        break;

    case 403:
        header('HTTP/1.1 403 Forbidden');
        $error["title"] = _h("403 - Forbidden");
        $error["message"] = _h("You're not supposed to be here. Click one of the links in the menu above to find some better content.");
        break;

    case 404:
        header('HTTP/1.1 404 Not Found');
        $error["title"] = _h("404 - Not Found");
        $error["message"] = _h("We can't find what you are looking for. The link you followed may be broken.");
        break;

    default:
        $error["title"] = _h("An Error Occurred");
        $error["message"] = _h("Something broke! We'll try to fix it as soon as we can!");
        break;
}

$tpl->assign("error", $error);
echo $tpl;
