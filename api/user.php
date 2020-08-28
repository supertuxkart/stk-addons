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
    XMLOutput::exitXML("Can not execute user API");
}
$action = isset($_POST['action']) ? $_POST['action'] : "";
$output = new XMLOutput();
$output->startDocument('1.0', 'UTF-8');

try
{
    switch ($action)
    {
        case 'poll':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
                $session = ClientSession::get($token, $userid);

                $output->insert($session->poll());
            }
            catch(Exception $e)
            {
                $output->addErrorElement('poll', $e->getMessage());
            }
            break;

        case 'connect':
            $password = isset($_POST['password']) ? utf8_encode($_POST['password']) : "";
            $username = isset($_POST['username']) ? utf8_encode($_POST['username']) : "";
            $save_session = isset($_POST['save-session']) ? utf8_encode($_POST['save-session']) : "";

            try
            {
                $session = ClientSession::create($username, $password, $save_session === "true");
                // Clear previous joined server if any
                $session->clearUserJoinedServer();
                $session->updateUserGeolocation();
                $achievements_string = $session->getAchievements();

                $output->startElement('connect');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('token', $session->getSessionID());
                    $output->writeAttribute('username', h($session->getUser()->getUserName()));
                    $output->writeAttribute('realname', h($session->getUser()->getRealName()));
                    $output->writeAttribute('userid', $session->getUser()->getId());
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
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
                $session = ClientSession::get($token, $userid);
                // Clear previous joined server if any
                $session->clearUserJoinedServer();
                $session->setOnline();
                $session->updateUserGeolocation();
                User::updateLoginTime($session->getUser()->getId());

                $output->startElement('saved-session');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('token', $session->getSessionID());
                    $output->writeAttribute('username', h($session->getUser()->getUserName()));
                    $output->writeAttribute('realname', h($session->getUser()->getRealName()));
                    $output->writeAttribute('userid', $session->getUser()->getId());
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('saved-session', $e->getMessage());
            }
            break;

        case 'get-friends-list':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";
            $visitingid = isset($_POST['visitingid']) ? (int)$_POST['visitingid'] : 0;

            try
            {
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
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";
            $visitingid = isset($_POST['visitingid']) ? (int)$_POST['visitingid'] : 0;

            try
            {
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
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";
            $addonid = isset($_POST['addonid']) ? $_POST['addonid'] : "";

            try
            {
                $session = ClientSession::get($token, $userid);
                $rating = Rating::get($addonid)->getUserVote($session->getUser()->getId());

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

        case 'set-addon-vote': // returns -1 if no vote found
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";
            $addonid = isset($_POST['addonid']) ? $_POST['addonid'] : "";
            $rating = isset($_POST['rating']) ? (float)$_POST['rating'] : -1.0;

            try
            {
                $session = ClientSession::get($token, $userid);
                $rating_object = Rating::get($addonid);
                $rating_object->setUserVote($session->getUser()->getId(), $rating);

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
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
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

        case 'disconnect':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
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

        case 'achieving':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";
            $achievement_ids = isset($_POST['achievementid']) ? Util::commaStringToArray($_POST['achievementid']) : [];

            try
            {
                foreach ($achievement_ids as $id)
                {
                    ClientSession::get($token, $userid)->onAchieving((int)$id);
                }
            }
            catch(Exception $e)
            {
                $output->addErrorElement('achieving', $e->getMessage());
            }
            break;

        case 'friend-request':
            $friendid = isset($_POST['friendid']) ? (int)$_POST['friendid'] : 0;
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
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
            $friendid = isset($_POST['friendid']) ? (int)$_POST['friendid'] : 0;
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
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
            $friendid = isset($_POST['friendid']) ? (int)$_POST['friendid'] : 0;
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
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
            $friendid = isset($_POST['friendid']) ? (int)$_POST['friendid'] : 0;
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
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
            $friendid = isset($_POST['friendid']) ? (int)$_POST['friendid'] : 0;
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
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
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";
            $search_string = isset($_POST['search-string']) ? $_POST['search-string'] : "";

            try
            {
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

        case 'register':
            $username = isset($_POST['username']) ? utf8_encode($_POST['username']) : "";
            $password = isset($_POST['password']) ? utf8_encode($_POST['password']) : "";
            $password_confirm = isset($_POST['password_confirm']) ? utf8_encode($_POST['password_confirm']) : "p";
            $email = isset($_POST['email']) ? utf8_encode($_POST['email']) : "";
            $realname = isset($_POST['realname']) ? utf8_encode($_POST['realname']) : $username; // use username as real name
            $terms = isset($_POST['terms']) ? utf8_encode($_POST['terms']) : "";

            try
            {
                User::register(
                    $username,
                    $password,
                    $password_confirm,
                    $email,
                    $realname,
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

        case 'recover':
            $username = isset($_POST['username']) ? utf8_encode($_POST['username']) : "";
            $email = isset($_POST['email']) ? utf8_encode($_POST['email']) : "";

            try
            {
                User::recover($username, $email);

                $output->startElement('recover');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('recover', $e->getMessage());
            }
            break;

        case 'change-password':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $current = isset($_POST['current']) ? $_POST['current'] : "";
            $new1 = isset($_POST['new1']) ? $_POST['new1'] : "";
            $new2 = isset($_POST['new2']) ? $_POST['new2'] : "";

            try
            {
                User::verifyAndChangePassword($userid, $current, $new1, $new2);

                $output->startElement('change-password');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->addErrorElement('change-password', $e->getMessage());
            }
            break;

        case 'get-ranking':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
                $session = ClientSession::get($token, $userid);

                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                $ranking = Ranking::getRanking($id);

                $output->startElement('get-ranking');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->writeAttribute('scores', $ranking['scores']);
                    $output->writeAttribute('max-scores', $ranking['max_scores']);
                    $output->writeAttribute('num-races-done', $ranking['num_races_done']);
                    $output->writeAttribute('raw-scores', $ranking['raw_scores']);
                    $output->writeAttribute('rating-deviation', $ranking['rating_deviation']);
                    $output->writeAttribute('disconnects', $ranking['disconnects']);
                    $output->writeAttribute('rank', $ranking['rank']);
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('get-ranking');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute(
                        'info',
                        h($e->getMessage())
                    );
                $output->endElement();
            }
            break;

        case 'top-players':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
                $session = ClientSession::get($token, $userid);

                $ntop = isset($_POST['ntop']) ? (int)$_POST['ntop'] : 10;
                $list = Ranking::getTopPlayersFromRanking($ntop);

                $output->startElement('top-players');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                    $output->startElement('players');
                        if (is_array($list))
                        {
                            foreach ($list as $player)
                            {
                                $output->startElement('player');
                                    $output->writeAttribute('username', $player['username']);
                                    $output->writeAttribute('scores', $player['scores']);
                                    $output->writeAttribute('max-scores', $player['max_scores']);
                                    $output->writeAttribute('num-races-done', $player['num_races_done']);
                                    $output->writeAttribute('raw-scores', $player['raw_scores']);
                                    $output->writeAttribute('rating-deviation', $player['rating_deviation']);
                                    $output->writeAttribute('disconnects', $player['disconnects']);
                                    $output->writeAttribute('rank', $player['rank']);
                                $output->endElement();
                            }
                        }
                    $output->endElement();
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('top-players');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute(
                        'info',
                        h($e->getMessage())
                    );
                $output->endElement();
            }
            break;

        case 'submit-ranking':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
                $session = ClientSession::get($token, $userid);
                $permission = AccessControl::getPermissions($session->getUser()->getRole());

                $id_for_ranked = isset($_POST['id']) ? (int)$_POST['id'] : null;
                $new_scores = isset($_POST['scores']) ? $_POST['scores'] : null;
                $new_max_scores = isset($_POST['max-scores']) ? $_POST['max-scores'] : null;
                $new_num_races_done = isset($_POST['num-races-done']) ? (int)$_POST['num-races-done'] : null;
                $new_raw_scores = isset($_POST['raw-scores']) ? $_POST['raw-scores'] : null;
                $new_rating_deviation = isset($_POST['rating-deviation']) ? $_POST['rating-deviation'] : null;
                $new_disconnects = isset($_POST['disconnects']) ? $_POST['disconnects'] : null;
                Ranking::submitRanking($permission, $id_for_ranked, $new_scores, $new_max_scores, $new_num_races_done, $new_raw_scores, $new_rating_deviation, $new_disconnects);

                $output->startElement('submit-ranking');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('submit-ranking');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute(
                        'info',
                        h($e->getMessage())
                    );
                $output->endElement();
            }
            break;

        case 'reset-ranking':
            $userid = isset($_POST['userid']) ? (int)$_POST['userid'] : 0;
            $token = isset($_POST['token']) ? $_POST['token'] : "";

            try
            {
                $session = ClientSession::get($token, $userid);
                $permission = AccessControl::getPermissions($session->getUser()->getRole());
                Ranking::resetRanking($permission);

                $output->startElement('reset-ranking');
                    $output->writeAttribute('success', 'yes');
                    $output->writeAttribute('info', '');
                $output->endElement();
            }
            catch(Exception $e)
            {
                $output->startElement('reset-ranking');
                    $output->writeAttribute('success', 'no');
                    $output->writeAttribute(
                        'info',
                        h($e->getMessage())
                    );
                $output->endElement();
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
