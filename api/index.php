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
define('API', true);
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

$klein = new \Klein\Klein();
$klein->with(
    API_LOCATION . '/' . API_VERSION,
    function () use ($klein)
    {
        // user
        $klein->respond(
            ['GET', 'POST'],
            '/user/[:action]/?',
            function ($request, $response)
            {
                $_POST["action"] = $request->action;

                return Util::ob_get_require_once("client-user.php");
            }
        );

        // server
        $klein->respond(
            ['GET', 'POST'],
            '/server/[:action]/?',
            function ($request, $response)
            {
                $_POST["action"] = $request->action;

                return Util::ob_get_require_once("server.php");
            }
        );
    }
);
$klein->dispatch();
