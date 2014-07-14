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

    var $user_body = $("#user-body");
    var $user_menu = $("#user-menu");
    var $user_main = $("#user-main");
    var json_url = JSON_LOCATION + "users.php";
    var search_url = JSON_LOCATION + "search.php";
    var original_menu;

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
