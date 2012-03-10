<?php
/**
 * copyright 2011
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
define('ROOT', './');
include_once('config.php');
include_once('include/ClientSession.class.php');
include_once('include/User.class.php');


ob_start();
header('Content-type: text/xml');
echo "<?xml version=\"1.0\"?>\n";

$action = isset($_GET['action']) ? $_GET['action'] : null;
try {
    switch ($action)
    {
        case 'connect':
            try {
                $user = isset($_GET['user']) ? $_GET['user'] : null;
                $password = isset($_GET['password']) ? $_GET['password'] : null;
                $session = ClientSession::create($user, $password);
                printConnectionXml($session);
            }
            catch (ClientSessionConnectException $e) {
                sendPlainMessage(403, $e->getMessage());
            }

            break;

        case 'disconnect':
            try {
                $id = isset($_GET['id']) ? $_GET['id'] : null;
                $user = isset($_GET['user']) ? $_GET['user'] : null;
                ClientSession::destroy($id, $user);
                sendPlainMessage(200, 'Connection destroyed');
            }
            catch (ClientSessionExpiredException $e)  {
                sendPlainMessage(403, $e->getMessage());
            }
            catch (Exception $e) {
                sendPlainMessage(500, $e->getMessage());
            }
            break;

        case 'refresh':
            try {
                $id = isset($_GET['id']) ? $_GET['id'] : null;
                $user = isset($_GET['user']) ? $_GET['user'] : null;
                $session = ClientSession::get($id, $user);
                $session->regenerate();
                printConnectionXml($session);
                break;
            }
            catch (ClientSessionExpiredException $e) {
                sendPlainMessage(403, $e->getMessage());
            }
            break;

        default:
            sendPlainMessage(400, "I don't know what you want from me");
            break;
    }
}
catch (Exception $e) {
    sendPlainMessage(500, $e->getMessage());
}

function sendPlainMessage($status, $msg)
{
    ob_clean();
    header('Content-type: text/plain', true, $status);
    echo $msg;
}

function printConnectionXml(ClientSession $session)
{
    if ($session instanceof ClientSessionUser) {
        printf('<connection id="%s" user="%d" registered="true" />',
                $session->getSessionId(), $session->getUserId());
    }
    else {
        printf('<connection id="%s" registered="false" />',
                $session->getSessionId());
    }
}

ob_end_flush();
?>