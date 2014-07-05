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

(function(window, document) {
    "use strict";

    var $user_body = $("#user-body");
    var json_url = JSON_LOCATION +"users.php";

    function userFormSubmit(form_identifier, callback_success) {
        onFormSubmit(form_identifier, callback_success, $user_body, json_url, {}, "POST");
    }

    // left panel user clicked
    $('a.user-list').click(function() {
        History.pushState(null, '', this.href);
        var user = getUrlVars(this.href)['user'];
        loadContent($user_body, SITE_ROOT + 'users-panel.php', {user: user});

        return false;
    });

    userFormSubmit("#user-edit-profile", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            growlError(jData["error"]);
        }
        if (jData.hasOwnProperty("success")) {
            growlSuccess(jData["success"]);

            // update view
            $("#user-realname").text($("#user-profile-realname").val());
            var homepage = $("#user-profile-homepage").val();
            var $homepage_row = $("#user-homepage-row");
            if(_.isEmpty(homepage)) { // homepage is empty, hide the view
                $homepage_row.addClass("hide");
            } else { // homepage is not empty
                $homepage_row.removeClass("hide");
                $("#user-homepage").text(homepage);
            }
        }
    })

})(window, document);
