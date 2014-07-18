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

(function($) {
    "use strict";

    var $user_body = $("#user-body"),
        $user_menu = $("#user-menu"),
        $user_main = $("#user-main"),
        json_url = JSON_LOCATION + "users.php",
        search_url = JSON_LOCATION + "search.php",
        original_menu;

    function userFormSubmit(form_identifier, callback_success) {
        onFormSubmit(form_identifier, callback_success, $user_body, json_url, {}, "POST");
    }

    // search form
    $("#user-search-val").keyup(function() {
        var search_term = this.value;
        if (search_term.length <= 2) { // only if length is 3 or greater
            // restore original menu
            if (!_.isEmpty(original_menu)) {
                $user_menu.html(original_menu);
                original_menu = ""; // clear
            }

            return null;
        }

        $.get(search_url, {"data-type": "user", "search-term": search_term, "return-html": true}, function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("success")) {
                if (_.isEmpty(original_menu)) { // keep original menu
                    original_menu = $user_menu.html();
                }
                $user_menu.html(jData["users-html"]);
            }
        });

        console.log(search_term);
    });

    // left panel user clicked
    $user_main.on("click", 'a.user-list', function() {
        History.pushState(null, '', this.href);
        var user = getUrlVars(this.href)['user'];
        loadContent($user_body, SITE_ROOT + 'users-panel.php', {user: user});

        return false;
    });

    // send friend request clicked
    $user_main.on("click", ".btn-send-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        console.log("Send friend clicked", id);
        $.post(json_url, {action: "send-friend", "friend-id": id}, function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                // update view
                if (tab === "friends") {
                    console.log("not handled");
                } else if (tab === "profile") {
                    $this.addClass("hide");
                    $("#profile .btn-cancel-friend").removeClass("hide");
                }
            }
        });
    });

    // remove friend clicked
    $user_main.on("click", ".btn-remove-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        console.log("Remove friend clicked", id);
        modalDelete("Are you sure you want to remove this friend?", function() {
            $.post(json_url, {action: "remove-friend", "friend-id": id}, function(data) {
                var jData = parseJSON(data);
                if (jData.hasOwnProperty("error")) {
                    growlError(jData["error"]);
                }
                if (jData.hasOwnProperty("success")) {
                    growlSuccess(jData["success"]);

                    // update view
                    if (tab === "friends") {
                        $parent.closest("tr").remove();
                    } else if (tab === "profile") {
                        console.log("not handled");
                    }
                }
            });
        });
    });

    // accept friend clicked
    $user_main.on("click", ".btn-accept-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        console.log("Accept friend clicked", id);
        $.post(json_url, {action: "accept-friend", "friend-id": id}, function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                // update view
                if (tab === "friends") {
                    $this.addClass("hide");
                    $this.siblings(".btn-remove-friend").removeClass("hide");
                    $parent.closest("tr").removeClass("danger");
                    $parent.closest("td").prev().text("Offline");
                } else if (tab === "profile") {
                    $this.addClass("hide");
                    $("#profile .btn-decline-friend").addClass("hide");
                    $("#profile .btn-already-friend").removeClass("hide");
                }
            }
        });
    });

    // decline friend clicked
    $user_main.on("click", ".btn-decline-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        console.log("Decline friend clicked", id);
        $.post(json_url, {action: "decline-friend", "friend-id": id}, function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                // update view
                if (tab === "friends") {
                    $parent.closest("tr").remove();
                } else if (tab === "profile") {
                    $this.addClass("hide");
                    $("#profile .btn-accept-friend").addClass("hide");
                    $("#profile .btn-send-friend").removeClass("hide");
                }
            }
        });
    });

    // cancel friend clicked
    $user_main.on("click", ".btn-cancel-friend", function() {
        var $this = $(this),
            $parent = $this.parent(),
            id = $parent.data("id"),
            tab = $parent.data("tab");

        console.log("Cancel friend clicked", id);
        $.post(json_url, {action: "cancel-friend", "friend-id": id}, function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                // update view
                if (tab === "friends") {
                    $parent.closest("tr").remove();
                } else if (tab === "profile") {
                    $this.addClass("hide");
                    $("#profile .btn-send-friend").removeClass("hide");
                }
            }
        });
    });

    // edit profile
    userFormSubmit("#user-edit-profile", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            growlError(jData["error"]);
        }
        if (jData.hasOwnProperty("success")) {
            growlSuccess(jData["success"]);

            // update view
            $("#user-realname").text(getByID("user-profile-realname").value);
            var homepage = getByID("user-profile-homepage").value;
            var $homepage_row = $("#user-homepage-row");
            if (_.isEmpty(homepage)) { // homepage is empty, hide the view
                $homepage_row.addClass("hide");
            } else { // homepage is not empty
                $homepage_row.removeClass("hide");
                $("#user-homepage").text(homepage);
            }
        }
    });

    // edit user role and activation status
    userFormSubmit("#user-edit-role", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            growlError(jData["error"]);
        }
        if (jData.hasOwnProperty("success")) {
            growlSuccess(jData["success"]);

            // update view
            $("#user-role").text(getByID("user-settings-role").value);
            var username = $("#user-username").text();
            var $side_user = $("span:contains({0})".format(username));
            if (getByID("user-settings-available").checked) { // user is active
                $side_user.removeClass("unavailable");
            } else { // user is not active
                $side_user.addClass("unavailable");
            }
        }
    });

    // change password form
    userFormSubmit("#user-change-password", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            growlError(jData["error"]);
        }
        if (jData.hasOwnProperty("success")) {
            growlSuccess(jData["success"]);

            // clear password field
            $("#user-change-password").find("input[type=password]").val("");
        }
    });

})(jQuery);
