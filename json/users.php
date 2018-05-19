<?php
/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
 *
 * This file is part of stk-addons.
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
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

header("Content-Type: application/json");
if (!isset($_POST["action"]))
{
    exit_json_error("action param is not defined or is empty");
}
if (!User::isLoggedIn())
{
    exit_json_error("You are not logged in");
}

switch ($_POST["action"])
{
    case "edit-profile":
        if (Validate::ensureNotEmpty($_POST, ["user-id"]))
        {
            exit_json_error(_h("User id  is empty"));
        }

        $user_id = (int)$_POST["user-id"];
        $homepage = isset($_POST["homepage"]) ? $_POST["homepage"] : "";
        $real_name = isset($_POST["realname"]) ? $_POST["realname"] : "";

        try
        {
            User::updateProfile($user_id, $homepage, $real_name);
        }
        catch (UserException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h("Profile updated"));
        break;

    case "edit-role":
        if (Validate::ensureNotEmpty($_POST, ["role", "user-id"]))
        {
            exit_json_error(_h("Role field is empty"));
        }

        $user_id = (int)$_POST["user-id"];
        $role = $_POST["role"];
        $available = Util::isCheckboxChecked($_POST, "available");

        try
        {
            User::updateRole($user_id, $role, $available);
        }
        catch (UserException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h("Role edited successfully"));
        break;

    case "change-password":
        if (Validate::ensureNotEmpty($_POST, ["old-pass", "new-pass", "new-pass-verify"]))
        {
            exit_json_error(_h("One or more fields are empty"));
        }

        try
        {
            User::verifyAndChangePassword(
                User::getLoggedId(),
                $_POST["old-pass"],
                $_POST["new-pass"],
                $_POST["new-pass-verify"]
            );
        }
        catch (UserException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h("Your password has been changed"));
        break;

    case "delete-account":
        if (Validate::ensureNotEmpty($_POST, ["password", "verify-phrase"]))
        {
            exit_json_error(_h("One or more fields are empty"));
        }

        // Verify phrase does not match
        if ($_POST["verify-phrase"] !== "DELETE/". User::getLoggedUserName())
        {
            exit_json_error(_h("Verify phrase does not match. Please type it as shown."));
        }

        try
        {
            User::verifyAndDelete(
                User::getLoggedId(),
                $_POST["password"]
            );
        }
        catch (UserException $e)
        {
            exit_json_error($e->getMessage());
        }

        exit_json_success(_h("Your account has been deleted. Cya."));
        break;

    case "send-friend": // send friend request
        if (Validate::ensureNotEmpty($_POST, ["friend-id"]))
        {
            exit_json_error(_h("Friend id is empty"));
        }

        try
        {
            Friend::friendRequest(User::getLoggedId(), (int)$_POST["friend-id"]);
        }
        catch (FriendException $e)
        {
            exit_json_error($e->getMessage());
        }

        User::refreshFriends();
        exit_json_success(_h("Friend request sent"));
        break;

    case "remove-friend": // remove friend
        if (Validate::ensureNotEmpty($_POST, ["friend-id"]))
        {
            exit_json_error(_h("Friend id is empty"));
        }

        try
        {
            Friend::removeFriend(User::getLoggedId(), (int)$_POST["friend-id"]);
        }
        catch (FriendException $e)
        {
            exit_json_error($e->getMessage());
        }

        User::refreshFriends();
        exit_json_success(_h("Friend removed"));
        break;

    case "accept-friend": // accept friend request
        if (Validate::ensureNotEmpty($_POST, ["friend-id"]))
        {
            exit_json_error(_h("Friend id is empty"));
        }

        try
        {
            Friend::acceptFriendRequest((int)$_POST["friend-id"], User::getLoggedId());
        }
        catch (FriendException $e)
        {
            exit_json_error($e->getMessage());
        }

        User::refreshFriends();
        exit_json_success(_h("Friend request accepted"));
        break;

    case "decline-friend": // decline friend request
        if (Validate::ensureNotEmpty($_POST, ["friend-id"]))
        {
            exit_json_error(_h("Friend id is empty"));
        }

        try
        {
            Friend::declineFriendRequest((int)$_POST["friend-id"], User::getLoggedId());
        }
        catch (FriendException $e)
        {
            exit_json_error($e->getMessage());
        }

        User::refreshFriends();
        exit_json_success(_h("Friend request declined"));
        break;

    case "cancel-friend": // cancel a friend request
        if (Validate::ensureNotEmpty($_POST, ["friend-id"]))
        {
            exit_json_error(_h("Friend id is empty"));
        }

        try
        {
            Friend::cancelFriendRequest(User::getLoggedId(), (int)$_POST["friend-id"]);
        }
        catch (FriendException $e)
        {
            exit_json_error($e->getMessage());
        }

        User::refreshFriends();
        exit_json_success(_h("Friend request canceled"));
        break;

    default:
        exit_json_error(sprintf("action = %s is not recognized", h($_POST["action"])));
        break;
}
