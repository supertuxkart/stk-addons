<?php
define('API', 1);
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