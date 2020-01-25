<?php
/**
 * copyright 2013        Glenn De Jonghe
 *           2014 - 2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
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
                $address = isset($_POST['address']) ? (int)utf8_encode($_POST['address']) : null;
                $address_ipv6 = isset($_POST['address_ipv6']) ? $_POST['address_ipv6'] : "";
                $port = isset($_POST['port']) ? (int)utf8_encode($_POST['port']) : null;
                $private_port = isset($_POST['private_port']) ? (int)utf8_encode($_POST['private_port']) : null;
                $server_name = isset($_POST['name']) ? $_POST['name'] : "";
                $max_players = isset($_POST['max_players']) ? (int)$_POST['max_players'] : 0;
                $difficulty = isset($_POST['difficulty']) ? (int)$_POST['difficulty'] : 0;
                $game_mode = isset($_POST['game_mode']) ? (int)$_POST['game_mode'] : 0;
                $password = isset($_POST['password']) ? (int)$_POST['password'] : 0;
                $version = isset($_POST['version']) ? (int)$_POST['version'] : 1;
                $server = ClientSession::get($token, $userid)->createServer(
                    $address,
                    $address_ipv6,
                    $port,
                    $private_port,
                    $server_name,
                    $max_players,
                    $difficulty,
                    $game_mode,
                    $password,
                    $version
                );

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
        
        case 'stop': // stop a server
            try
            {
                $userid = isset($_POST['userid']) ? (int)utf8_encode($_POST['userid']) : 0;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : 0;
                $port = isset($_POST['port']) ? (int)utf8_encode($_POST['port']) : 0;

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

        case 'join-server-key':
            try
            {
                $userid = isset($_POST['userid']) ? (int)utf8_encode($_POST['userid']) : 0;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $server_id = isset($_POST['server-id']) ? (int)utf8_encode($_POST['server-id']) : 0;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : 0;
                $ipv6 = isset($_POST['address-ipv6']) ? $_POST['address-ipv6'] : "";
                $port = isset($_POST['port']) ? (int)utf8_encode($_POST['port']) : 0;
                $aes_key = isset($_POST['aes-key']) ? utf8_encode($_POST['aes-key']) : null;
                $aes_iv = isset($_POST['aes-iv']) ? utf8_encode($_POST['aes-iv']) : null;

                ClientSession::get($token, $userid)
                    ->setJoinServerKey($server_id, $address, $ipv6, $port, $aes_key, $aes_iv);

                $output->startElement('join-server-key');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('join-server-key', $e->getMessage());
            }
            break;

        case 'poll-connection-requests':
            try
            {
                $userid = isset($_POST['userid']) ? (int)utf8_encode($_POST['userid']) : 0;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : 0;
                $port = isset($_POST['port']) ? (int)utf8_encode($_POST['port']) : 0;
                $current_players = isset($_POST['current-players']) ? (int)$_POST['current-players'] : 0;
                $game_started = isset($_POST['game-started']) ? (int)$_POST['game-started'] : 0;
                $current_track = isset($_POST['current-track']) ? utf8_encode($_POST['current-track']) : "";

                $requests = ClientSession::get($token, $userid)
                    ->getServerConnectionRequests($address, $port, $current_players, $game_started, $current_track);

                $output->startElement('poll-connection-requests');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');

                    $output->startElement('users');
                        foreach ($requests as $request)
                        {
                            $output->startElement('user');
                                $output->writeAttribute("id", $request['user_id']);
                                $output->writeAttribute("username", $request['username']);
                                $output->writeAttribute("ip", $request['ip']);
                                $output->writeAttribute("ipv6", $request['ipv6']);
                                $output->writeAttribute("port", $request['port']);
                                $output->writeAttribute("aes-key", $request['aes_key']);
                                $output->writeAttribute("aes-iv", $request['aes_iv']);
                                $output->writeAttribute("country-code", $request['country_code']);
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

        case 'update-config':
            try
            {
                $userid = isset($_POST['userid']) ? (int)utf8_encode($_POST['userid']) : 0;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : 0;
                $port = isset($_POST['port']) ? (int)utf8_encode($_POST['port']) : 0;
                $new_difficulty = isset($_POST['new-difficulty']) ? (int)$_POST['new-difficulty'] : 0;
                $new_game_mode = isset($_POST['new-game-mode']) ? (int)$_POST['new-game-mode'] : 3;

                ClientSession::get($token, $userid)
                    ->updateServerConfig($address, $port, $new_difficulty, $new_game_mode);

                $output->startElement('update-config');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('update-config', $e->getMessage());
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

        case 'clear-user-joined-server':
            try
            {
                $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                ClientSession::get($token, $userid)->clearUserJoinedServer();

                $output->startElement('clear-user-joined-server');
                    $output->writeAttribute('success', 'yes');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('clear-user-joined-server', $e->getMessage());
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
