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

(function($, ROOT_LOCATION, JSON_LOCATION) {
    "use strict";

    var $user_body = $("#user-body"),
        $user_menu = $("#user-menu"),
        $user_main = $("#user-main"),
        json_url = JSON_LOCATION + "users.php",
        original_menu;

    function userFormSubmit(form_identifier, callback_success) {
        onFormSubmit(form_identifier, callback_success, $user_body, json_url, {}, "POST");
    }

    registerPagination($user_menu, "users-menu.php");

    // search form
    $("#user-search-form").submit(function(e) { // prevent form submit
        e.preventDefault();
        return false
    });
    $("#user-search-val").keyup(function() {
        var query = this.value;
        if (query.length <= 2) { // only if length is 3 or greater
            // restore original menu
            if (!_.isEmpty(original_menu)) {
                $user_menu.html(original_menu);
                original_menu = ""; // clear
            }

            return null;
        }

        $.get(SEARCH_URL, {"data-type": "user", "query": query, "return-html": true}, function(data) {
            jsonCallback(data, function(jData) {
                if (_.isEmpty(original_menu)) { // keep original menu
                    original_menu = $user_menu.html();
                }
                $user_menu.html(jData["users-html"]);

                return false;
            });
        });

        return null;
    });

    // left panel user clicked
    $user_main.on("click", 'a.user-list', function() {
        History.pushState(null, '', this.href);
        var user = getUrlVars(this.href)['user'];
        loadContent($user_body, ROOT_LOCATION + 'users-panel.php', {user: user});

        markMenuItemAsActive($(this));

        return false;
    });

    // send friend request clicked
    $user_main.on("click", ".btn-send-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        $.post(json_url, {action: "send-friend", "friend-id": id}, function(data) {
            jsonCallback(data, function() {
                // update view
                if (tab === "friends") {
                    console.log("not handled");
                } else if (tab === "profile") {
                    $this.addClass("hidden");
                    $("#profile .btn-cancel-friend").removeClass("hidden");
                }
            });
        });
    });

    // remove friend clicked
    $user_main.on("click", ".btn-remove-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        modalDelete("Are you sure you want to remove this friend?", function() {
            $.post(json_url, {action: "remove-friend", "friend-id": id}, function(data) {
                jsonCallback(data, function() {
                    // update view
                    if (tab === "friends") {
                        $parent.closest("tr").remove();
                    } else if (tab === "profile") {
                        console.log("not handled");
                    }
                });
            });
        });
    });

    // accept friend clicked
    $user_main.on("click", ".btn-accept-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        $.post(json_url, {action: "accept-friend", "friend-id": id}, function(data) {
            jsonCallback(data, function() {
                // update view
                if (tab === "friends") {
                    $this.addClass("hidden");
                    $this.siblings(".btn-decline-friend").addClass("hidden");
                    $this.siblings(".btn-remove-friend").removeClass("hidden");
                    $parent.closest("tr").removeClass("danger");
                    $parent.closest("td").prev().text("Offline");
                } else if (tab === "profile") {
                    $this.addClass("hidden");
                    $("#profile .btn-decline-friend").addClass("hidden");
                    $("#profile .btn-already-friend").removeClass("hidden");
                }
            });
        });
    });

    // decline friend clicked
    $user_main.on("click", ".btn-decline-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        $.post(json_url, {action: "decline-friend", "friend-id": id}, function(data) {
            jsonCallback(data, function() {
                // update view
                if (tab === "friends") {
                    $parent.closest("tr").remove();
                } else if (tab === "profile") {
                    $this.addClass("hidden");
                    $("#profile .btn-accept-friend").addClass("hidden");
                    $("#profile .btn-send-friend").removeClass("hidden");
                }
            });
        });
    });

    // cancel friend clicked
    $user_main.on("click", ".btn-cancel-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        $.post(json_url, {action: "cancel-friend", "friend-id": id}, function(data) {
            jsonCallback(data, function() {
                // update view
                if (tab === "friends") {
                    $parent.closest("tr").remove();
                } else if (tab === "profile") {
                    $this.addClass("hidden");
                    $("#profile .btn-send-friend").removeClass("hidden");
                }
            });
        });
    });

    // edit profile
    userFormSubmit("#user-edit-profile", function(data) {
        jsonCallback(data, function() {
            // update view
            var new_real_name = getByID("user-profile-realname").value;
            $("#user-realname").text(new_real_name);
            $("#header-realname").text("Welcome, " + new_real_name);

            var homepage = getByID("user-profile-homepage").value;
            var $homepage_row = $("#user-homepage-row");
            if (_.isEmpty(homepage)) { // homepage is empty, hide the view
                $homepage_row.addClass("hidden");
            } else { // homepage is not empty
                $homepage_row.removeClass("hidden");
                $("#user-homepage").text(homepage);
            }
        });
    });

    // edit user role and activation status
    userFormSubmit("#user-edit-role", function(data) {
        jsonCallback(data, function() {
            // update view
            $("#user-role").text(getByID("user-settings-role").value);
            var $side_user = $("#user-menu .list-group .active").first();

            if (getByID("user-settings-available").checked) { // user is active
                $side_user.removeClass("disabled");
            } else { // user is not active
                $side_user.addClass("disabled");
            }
        });
    });

    // change password form
    userFormSubmit("#user-change-password", function(data) {
        jsonCallback(data, function() {
            // clear password field
            $("#user-change-password").find("input[type=password]").val("");
        });
    });

    // DELETE account form
    userFormSubmit("#user-delete-account", function(data) {
        jsonCallback(data, function() {
            // clear password field
            $("#user-change-password").find("input[type=password]").val("");
            redirectToHomePage(5);
        });
    });

})(jQuery, ROOT_LOCATION, JSON_LOCATION);
