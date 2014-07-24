/**
 * copyright 2013 Stephen Just <stephenjust@gmail.com>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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

var oldDiv = "";

function confirm_delete(url) {
    if (confirm("Really delete this item?")) {
        window.location = url;
    }
}

function loadAddon(id, page) {
    addonRequest(page, id);
}

function addonRequest(page, id, value) {
    $.post(page, {id: id, value: value},
        function(data) {
            $("#content-addon_body").html(data);
            $("#content-addon_body").scrollTop(0);
        }
    );
}
function loadDiv(newDiv) {
    newDiv = "disp" + newDiv;
    if (oldDiv !== "")    getByID(oldDiv).style.display = "none";
    getByID(newDiv).style.display = "block";
    oldDiv = newDiv;
    getByID("content-addon_body").innerHTML = "";
    getByID("content-addon_body").style.display = "none";
}

function textLimit(field, num) {
    if (field.value.length > num) {
        field.value = field.value.substring(0, num);
    }
}

(function($) {
    "use strict";

    var json_url = JSON_LOCATION + "rating.php",
        $addon_body = $("#addon-body"),
        $ratings_container = $("#rating-container"), // the container where you see the vote
        $user_rating = $("#user-rating"); // the vote container


    // left panel user addon clicked
    $('a.addon-list').click(function() {
        History.pushState(null, '', this.href);
        var url = this.href;
        var addonType = getUrlVars(url)['type'];
        if (addonType === undefined) {
            url = SITE_ROOT + $(this).children('meta').attr("content").replace('&amp;', '&');
            addonType = getUrlVars(url)['type'];
        }

        var addonId = getUrlVars(url)['name']; // we use the id as a varchar in the database
        loadContent($addon_body, SITE_ROOT + 'addons-panel.php', {name: addonId, type: addonType});

        return false;
    });

    $addon_body.on("click", '.add-rating', function() {
        var addon_id = $user_rating.data("id"),
            rating = this.value;

        // set new rating
        $.get(json_url, {"addon-id": addon_id, "rating": rating}, function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                console.log(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                console.log(jData["success"]);

                // update view
                $ratings_container.find(".fullstars").width(jData["width"] + "%");
                $ratings_container.find("p").html(jData["num-ratings"]);
            }
        });
    });

})(jQuery);