<?php
/**
 * copyright 2013        Glenn De Jonghe
 *           2014 - 2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
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

if (!API_MODE)
{
    XMLOutput::exitXML("Can not execute server API");
}
$action = isset($_POST['action']) ? $_POST['action'] : null;
$output = new XMLOutput();
$output->startDocument('1.0', 'UTF-8');

try
{
    switch ($action)
    {
        case 'create': // create a server
            try
            {
                $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $server_name = isset($_POST['name']) ? utf8_encode($_POST['name']) : "";
                $max_players = isset($_POST['max_players']) ? (int)$_POST['max_players'] : 0;

                $server = ClientSession::get($token, $userid)->createServer(0, 0, 0, $server_name, $max_players);

                $output->startElement('create');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->insert($server->asXML());
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('create', $e->getMessage());
            }
            break;

        case 'set':
            try
            {
                $userid = isset($_POST['userid']) ? utf8_encode($_POST['userid']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : null;
                $private_port = isset($_POST['private_port']) ? utf8_encode($_POST['private_port']) : null;
                $port = isset($_POST['port']) ? utf8_encode($_POST['port']) : null;

                ClientSession::get($token, $userid)->setPublicAddress($address, $port, $private_port);

                $output->startElement('set');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('set', $e->getMessage());
            }
            break;

        case 'start': // start a server
            try
            {
                $userid = isset($_POST['userid']) ? (int)utf8_encode($_POST['userid']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : null;
                $port = isset($_POST['port']) ? utf8_encode($_POST['port']) : null;
                $private_port = isset($_POST['private_port']) ? utf8_encode($_POST['private_port']) : null;
                $max_players = isset($_POST['max_players']) ? utf8_encode($_POST['max_players']) : null;
                $server_name = isset($_POST['server_name']) ? utf8_encode($_POST['server_name']) : "Temporary name";

                ClientSession::get($token, $userid)->createServer(
                    $address,
                    $port,
                    $private_port,
                    $server_name,
                    $max_players
                );

                $output->startElement('start');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('start', $e->getMessage());
            }
            break;

        case 'stop': // stop a server
            try
            {
                $userid = isset($_POST['userid']) ? utf8_encode($_POST['userid']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : null;
                $port = isset($_POST['port']) ? utf8_encode($_POST['port']) : null;

                ClientSession::get($token, $userid)->stopServer($address, $port);

                $output->startElement('stop');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('stop', $e->getMessage());
            }
            break;

        case 'unset':
            try
            {
                $userid = isset($_POST['userid']) ? utf8_encode($_POST['userid']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;

                ClientSession::get($token, $userid)->unsetPublicAddress();

                $output->startElement('unset');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('unset', $e->getMessage());
            }
            break;

        case 'get': // get the client info
            try
            {
                $userid = isset($_POST['userid']) ? utf8_encode($_POST['userid']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $peer_id = isset($_POST['peer_id']) ? utf8_encode($_POST['peer_id']) : null;

                $session = ClientSession::get($token, $userid);
                $result = $session->getPeerAddress($peer_id);

                $output->startElement('get');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('ip', $result['ip']);
                    $output->writeAttribute('port', $result['port']);
                    $output->writeAttribute('private_port', $result['private_port']);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('get', $e->getMessage());
            }
            break;

        case 'get-all': // get all the servers list
            try
            {
                $servers_xml = Server::getServersAsXML();

                $output->startElement('get-all');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->insert($servers_xml);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('get-all', $e->getMessage());
            }
            break;

        case 'quick-join':
            try
            {
                $userid = isset($_POST['userid']) ? (int)utf8_encode($_POST['userid']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;

                $result = ClientSession::get($token, $userid)->quickJoin();

                $output->startElement('quick-join');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('hostid', $result['host_id']);
                    $output->writeAttribute('ip', $result['ip']);
                    $output->writeAttribute('port', $result['port']);
                    $output->writeAttribute('private_port', $result['private_port']);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('quick-join', $e->getMessage());
            }
            break;

        case 'request-connection':
            try
            {
                $userid = isset($_POST['userid']) ? (int)utf8_encode($_POST['userid']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $server_id = isset($_POST['server_id']) ? (int)utf8_encode($_POST['server_id']) : null;

                ClientSession::get($token, $userid)->requestServerConnection($server_id);

                $output->startElement('request-connection');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('serverid', $server_id);
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('request-connection', $e->getMessage());
            }
            break;

        case 'poll-connection-requests':
            try
            {
                $userid = isset($_POST['userid']) ? (int)utf8_encode($_POST['userid']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : null;
                $port = isset($_POST['port']) ? utf8_encode($_POST['port']) : null;

                $requests = ClientSession::get($token, $userid)->getServerConnectionRequests($address, $port);

                $output->startElement('poll-connection-requests');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');

                    $output->startElement('users');
                        foreach ($requests as $request)
                        {
                            $output->startElement('user');
                                $output->writeAttribute("id", $request['userid']);
                            $output->endElement();
                        }
                    $output->endElement();

                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('poll-connection-requests', $e->getMessage());
            }
            break;

        case 'vote': // vote on a server
            try
            {
                $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $host_id = isset($_POST['hostid']) ? (int)$_POST['hostid'] : 0;
                $vote = isset($_POST['vote']) ? (int)$_POST['vote'] : 0;

                $new_rating = ClientSession::get($token, $userid)->setHostVote($host_id, $vote);

                $output->startElement('vote');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('new-rating', $new_rating);
                    $output->writeAttribute('hostid', $host_id);
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('vote', $e->getMessage());
            }
            break;

        default:
            $output->addErrorElement('request', 'Invalid action. Action = ' . h($_POST['action']));
            break;
    }
}
catch(Exception $e)
{
    $output->addErrorElement('request', 'An unexpected error occurred. Please contact a website administrator.');
}

$output->endDocument();
$output->printToScreen();
