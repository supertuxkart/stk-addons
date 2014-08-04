<?php
/**
 * copyright 2013 Glenn De Jonghe
 *           2014 Daniel Butum <danibutum at gmail dot com>
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
if (!defined("API"))
{

    exit("Can not execute");
}
$action = isset($_POST['action']) ? $_POST['action'] : null;
$output = new XMLOutput();
$output->startDocument('1.0', 'UTF-8');

try
{
    switch ($action)
    {
        case 'set':
            try
            {
                $id = isset($_POST['id']) ? utf8_encode($_POST['id']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : null;
                $private_port = isset($_POST['private_port']) ? utf8_encode($_POST['private_port']) : null;
                $port = isset($_POST['port']) ? utf8_encode($_POST['port']) : null;
                ClientSession::setPublicAddress($id, $token, $address, $port, $private_port);

                $output->startElement('address-management');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('address-management', $e->getMessage());
            }
            break;

        case 'start-server':
            try
            {
                $id = isset($_POST['id']) ? utf8_encode($_POST['id']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : null;
                $port = isset($_POST['port']) ? utf8_encode($_POST['port']) : null;
                $private_port = isset($_POST['private_port']) ? utf8_encode($_POST['private_port']) : null;
                $max_players = isset($_POST['max_players']) ? utf8_encode($_POST['max_players']) : null;

                ClientSession::get($token, $id)->createServer(
                    $address,
                    $port,
                    $private_port,
                    "Temporary name",
                    $max_players
                );

                $output->startElement('start-server');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('start-server', $e->getMessage());
            }
            break;

        case 'stop-server':
            try
            {
                $id = isset($_POST['id']) ? utf8_encode($_POST['id']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : null;
                $port = isset($_POST['port']) ? utf8_encode($_POST['port']) : null;

                ClientSession::get($token, $id)->stopServer($address, $port);

                $output->startElement('stop-server');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('stop-server', $e->getMessage());
            }
            break;

        case 'unset':
            try
            {
                $id = isset($_POST['id']) ? utf8_encode($_POST['id']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;

                ClientSession::unsetPublicAddress($id, $token);

                $output->startElement('address-management');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('address-management', $e->getMessage());
            }
            break;

        case 'get':
            try
            {
                $id = isset($_POST['id']) ? utf8_encode($_POST['id']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $peer_id = isset($_POST['peer_id']) ? utf8_encode($_POST['peer_id']) : null;

                $session = ClientSession::get($token, $id);
                $result = $session->getPeerAddress($peer_id);

                $output->startElement('get-public-ip');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('ip', $result['ip']);
                    $output->writeAttribute('port', $result['port']);
                    $output->writeAttribute('private_port', $result['private_port']);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('get-public-ip', $e->getMessage());
            }
            break;

        case 'quick-join':
            try
            {
                $id = isset($_POST['id']) ? utf8_encode($_POST['id']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;

                $result = ClientSession::get($token, $id)->quickJoin();

                $output->startElement('quick-join');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('hostid', $result['hostid']);
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
                $id = isset($_POST['id']) ? utf8_encode($_POST['id']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $server_id = isset($_POST['server_id']) ? utf8_encode($_POST['server_id']) : null;

                ClientSession::get($token, $id)->requestServerConnection($server_id);

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
                $id = isset($_POST['id']) ? utf8_encode($_POST['id']) : null;
                $token = isset($_POST['token']) ? utf8_encode($_POST['token']) : null;
                $address = isset($_POST['address']) ? utf8_encode($_POST['address']) : null;
                $port = isset($_POST['port']) ? utf8_encode($_POST['port']) : null;

                $requests = ClientSession::get($token, $id)->getServerConnectionRequests($address, $port);

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

        default:
            $output->addErrorElement('request', _('Invalid action.') . ' Action = ' . h($_POST['action']));
            break;
    }
}
catch(Exception $e)
{
    $output->addErrorElement('request', _('An unexptected error occured.') . ' ' . _('Please contact a website administrator.'));
}

$output->endDocument();
$output->printToScreen();
