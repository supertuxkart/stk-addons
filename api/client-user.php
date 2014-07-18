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
define('API', 1);
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

$action = isset($_POST['action']) ? $_POST['action'] : "";
$output = new XMLOutput();
$output->startDocument('1.0', 'UTF-8');

try
{
    switch ($action)
    {
        case 'poll':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                $session = ClientSession::get($token, $userid);

                $output->insert($session->poll());
            }
            catch(Exception $e)
            {
                $output->addErrorElement('poll', $e->getMessage());
            }
            break;

        case 'connect':
            try
            {
                $password = isset($_POST['password']) ? utf8_encode($_POST['password']) : "";
                $username = isset($_POST['username']) ? utf8_encode($_POST['username']) : "";
                $save_session = isset($_POST['save-session']) ? utf8_encode($_POST['save-session']) : "";

                $session = ClientSession::create($username, $password, $save_session == "true");
                $achievements_string = $session->getAchievements();

                $output->startElement('connect');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('token', $session->getSessionID());
                    $output->writeAttribute('username', h($session->getUsername()));
                    $output->writeAttribute('userid', $session->getUserID());
                    if ($achievements_string)
                    {
                        $output->writeAttribute('achieved', $achievements_string);
                    }
                    $output->writeAttribute('info', '');
                $output->endElement();

            }
            catch(Exception $e)
            {
                $output->addErrorElement('connect', $e->getMessage());
            }
            break;

        case 'saved-session':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                $session = ClientSession::get($token, $userid);
                $session->setOnline();
                User::updateLoginTime($session->getUserID());

                $output->startElement('saved-session');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('token', $session->getSessionID());
                    $output->writeAttribute('username', h($session->getUsername()));
                    $output->writeAttribute('userid', $session->getUserID());
                    $output->writeAttribute('info', '');
                $output->endElement();

            }
            catch(Exception $e)
            {
                $output->addErrorElement('saved-session', $e->getMessage());
            }
            break;

        case 'get_server_list':
            try
            {
                $servers_xml = Server::getServersAsXML();

                $output->startElement('get_servers_list');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->insert($servers_xml);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('get_servers_list', $e->getMessage());
            }
            break;

        case 'get-friends-list':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $visitingid = isset($_POST['visitingid']) ? $_POST['visitingid'] : 0;

                $session = ClientSession::get($token, $userid);
                $friends_xml = $session->getFriendsOf($visitingid);

                $output->startElement('get-friends-list');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('visitingid', $visitingid);
                    $output->insert($friends_xml);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('get-friends-list', $e->getMessage());
            }
            break;

        case 'get-achievements':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $visitingid = isset($_POST['visitingid']) ? $_POST['visitingid'] : 0;

                $session = ClientSession::get($token, $userid);
                $achievements_string = $session->getAchievements($visitingid);

                $output->startElement('get-achievements');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('visitingid', $visitingid);
                    if ($achievements_string)
                    {
                        $output->writeAttribute('achieved', $achievements_string);
                    }
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('get-achievements', $e->getMessage());
            }
            break;

        case 'get-addon-vote':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $addonid = isset($_POST['addonid']) ? $_POST['addonid'] : "";

                $rating_object = new Ratings($addonid, false);
                $rating = $rating_object->getUserVote(ClientSession::get($token, $userid));

                $output->startElement('get-addon-vote');
                    $output->writeAttribute('success', 'yes');
                    if ($rating === false)
                    {
                        $output->writeAttribute('voted', "no");
                        $output->writeAttribute('rating', -1);
                    }
                    else
                    {
                        $output->writeAttribute('voted', "yes");
                        $output->writeAttribute('rating', $rating);
                    }
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('get-addon-vote', $e->getMessage());
            }
            break;

        case 'set-addon-vote': //returns -1 if no vote found
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $addonid = isset($_POST['addonid']) ? $_POST['addonid'] : "";
                $rating = isset($_POST['rating']) ? $_POST['rating'] : -1.0;

                $rating_object = new Ratings($addonid, false);
                $rating_object->setUserVote($rating, ClientSession::get($token, $userid));

                $output->startElement('set-addon-vote');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('new-average', $rating_object->getAvgRating());
                    $output->writeAttribute('new-number', $rating_object->getNumRatings());
                    $output->writeAttribute('addon-id', $rating_object->getAddonId());
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('set-addon-vote', $e->getMessage());
            }
            break;

        case 'client-quit':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                ClientSession::get($token, $userid)->clientQuit();

                $output->startElement('client-quit');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('client-quit', $e->getMessage());
            }
            break;

        case 'host-vote':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $hostid = isset($_POST['hostid']) ? $_POST['hostid'] : 0;
                $vote = isset($_POST['vote']) ? $_POST['vote'] : 0;

                // TODO change hostVote because it returns void
                $new_rating = ClientSession::get($token, $userid)->hostVote($hostid, $vote);

                $output->startElement('host-vote');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('new-rating', $new_rating);
                    $output->writeAttribute('hostid', $hostid);
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('host-vote', $e->getMessage());
            }
            break;

        case 'achieving':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $achievementid = isset($_POST['achievementid']) ? $_POST['achievementid'] : 0;

                ClientSession::get($token, $userid)->onAchieving($achievementid);
            }
            catch(Exception $e)
            {
                $output->addErrorElement('achieving', $e->getMessage());
            }
            break;

        case 'friend-request':
            try
            {
                $friendid = isset($_POST['friendid']) ? $_POST['friendid'] : 0;
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                $session = ClientSession::get($token, $userid);
                Friend::friendRequest($userid, $friendid);

                $output->startElement('friend-request');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('friend-request');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute(
                        'info',
                        h($e->getMessage())
                    );
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            break;

        case 'accept-friend-request':
            try
            {
                $friendid = isset($_POST['friendid']) ? $_POST['friendid'] : 0;
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                $session = ClientSession::get($token, $userid);
                Friend::acceptFriendRequest($friendid, $userid);

                $output->startElement('accept-friend-request');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('accept-friend-request');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute(
                        'info',
                        h($e->getMessage())
                    );
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            break;

        case 'decline-friend-request':
            try
            {
                $friendid = isset($_POST['friendid']) ? $_POST['friendid'] : 0;
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                $session = ClientSession::get($token, $userid);
                Friend::declineFriendRequest($friendid, $userid);

                $output->startElement('decline-friend-request');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('decline-friend-request');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute('info', h($e->getMessage()));
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            break;

        case 'cancel-friend-request':
            try
            {
                $friendid = isset($_POST['friendid']) ? $_POST['friendid'] : 0;
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                $session = ClientSession::get($token, $userid);
                Friend::cancelFriendRequest($userid, $friendid);

                $output->startElement('cancel-friend-request');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('cancel-friend-request');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute('info', h($e->getMessage()));
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            break;

        case 'remove-friend':
            try
            {
                $friendid = isset($_POST['friendid']) ? $_POST['friendid'] : 0;
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                $session = ClientSession::get($token, $userid);
                Friend::removeFriend($userid, $friendid);

                $output->startElement('remove-friend');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('remove-friend');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute('info', h($e->getMessage()));
                    $output->writeAttribute('friendid', $friendid);
                $output->endElement();
            }
            break;

        case 'user-search':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $search_string = isset($_POST['search-string']) ? $_POST['search-string'] : "";

                $session = ClientSession::get($token, $userid);

                $output->startElement('user-search');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('search-string', $search_string);
                    $output->insert(User::searchUsersAsXML($search_string));
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('user-search', $e->getMessage());
            }
            break;

        case 'disconnect':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";

                ClientSession::get($token, $userid)->destroy();

                $output->startElement('disconnect');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('disconnect', $e->getMessage());
            }
            break;

        case 'create_server':
            try
            {
                $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
                $token = isset($_POST['token']) ? $_POST['token'] : "";
                $server_name = isset($_POST['name']) ? utf8_encode($_POST['name']) : "";
                $max_players = isset($_POST['max_players']) ? (int)$_POST['max_players'] : 0;

                $server = ClientSession::get($token, $userid)->createServer(0, 0, 0, $server_name, $max_players);

                $output->startElement('server_creation');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->insert($server->asXML());
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('server_creation', $e->getMessage());
            }
            break;

        case 'register':
            try
            {
                $username = isset($_POST['username']) ? utf8_encode($_POST['username']) : "";
                $password = isset($_POST['password']) ? utf8_encode($_POST['password']) : "";
                $password_confirm = isset($_POST['password_confirm']) ? utf8_encode($_POST['password_confirm']) : "p";
                $email = isset($_POST['email']) ? utf8_encode($_POST['email']) : "";
                $terms = isset($_POST['terms']) ? utf8_encode($_POST['terms']) : "";

                User::register(
                    $username,
                    $password,
                    $password_confirm,
                    $email,
                    $username,
                    $terms
                );

                $output->startElement('registration');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('registration', $e->getMessage());
            }
            break;

        case 'recovery':
            try
            {
                $username = isset($_POST['username']) ? utf8_encode($_POST['username']) : "";
                $email = isset($_POST['email']) ? utf8_encode($_POST['email']) : "";

                User::recover($username, $email);

                $output->startElement('recovery');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('recovery', $e->getMessage());
            }
            break;

        case 'change_password':
            try
            {
                $userid = isset($_POST['userid']) ? $_POST['userid'] : 0;
                $current = isset($_POST['current']) ? $_POST['current'] : "";
                $new1 = isset($_POST['new1']) ? $_POST['new1'] : "";
                $new2 = isset($_POST['new2']) ? $_POST['new2'] : "";

                User::verifyAndChangePassword($current, $new1, $new2, $userid);

                $output->startElement('change_password');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();

            }
            catch(Exception $e)
            {
                $output->addErrorElement('change_password', $e->getMessage());
            }
            break;

        default:
            $output->addErrorElement('request', _('Invalid action.'));
            break;
    }
}
catch(Exception $e)
{
    $output->addErrorElement('request', _('An unexpected error occurred.') . ' ' . _('Please contact a website administrator.'));
}

$output->endDocument();
$output->printToScreen();
