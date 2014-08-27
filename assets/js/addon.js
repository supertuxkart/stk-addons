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

function confirm_delete(url) {
    if (confirm("Really delete this item?")) {
        window.location = url;
    }
}

(function($, SITE_ROOT, JSON_LOCATION) {
    "use strict";

    var json_url = JSON_LOCATION + "rating.php",
        $addon_body = $("#addon-body"),
        $addon_menu = $("#addon-menu"),
        $addon_sort = $("#addon-sort"),
        $search_by = $("#addon-search-by"),
        addon_type = getUrlVars()["type"],
        original_menu;

    $('.multiselect').multiselect({});

    // search form
    $("#addon-search-val").keyup(function() {
        var query = this.value;
        if (query.length <= 2) { // only if length is 3 or greater
            // restore original menu
            if (!_.isEmpty(original_menu)) {
                $addon_menu.html(original_menu);
                original_menu = ""; // clear
            }

            return;
        }

        // flags is empty
        if ($search_by.val() === null) {
            growlError("Please select a filter for searching");
            return;
        }

        // search
        $.get(SEARCH_URL, {"data-type": "addon", "addon-type": addon_type, "query": query, "flags": $search_by.val(), "return-html": true},
            function(data) {
                jsonCallback(data, function(jData) {
                    if (_.isEmpty(original_menu)) { // keep original menu
                        original_menu = $addon_menu.html();
                    }
                    $addon_menu.html(jData["addons-html"]);

                    return false;
                });
            });
    });

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

    // rating clicked
    $addon_body.on("click", '.add-rating', function() {
        var addon_id = $("#user-rating").data("id"),
            $ratings_container = $("#rating-container"),
            rating = this.value;

        // set new rating
        $.get(json_url, {"action": "set", "addon-id": addon_id, "rating": rating}, function(data) {
            jsonCallback(data, function(jData) {
                console.log(jData["success"]);

                // update view
                $ratings_container.find(".fullstars").width(jData["width"] + "%");
                $ratings_container.find("p").html(jData["num-ratings"]);

                return false;
            }, function(jData) {
                console.log(jData["error"]);

                return false;
            });
        });
    });

    // addon sorting clicked
    $addon_sort.on("click", "button", function() {
        var $this = $(this),
            is_sortable = $this.hasClass("btn-sortable");

        // cannot press the same non sortable button twice
        if ($this.hasClass("active") && !is_sortable) {
            return;
        }

        var $siblings = $this.siblings(),
            sort_type = $this.data("type"),
            sort_order = "";

        // mark as active
        $this.addClass("active");

        // remove mark from others
        $siblings.removeClass("active");

        // button is sortable
        if (is_sortable) {
            var icon_asc = $this.data("asc"),
                icon_desc = $this.data("desc"),
                icon_add = "",
                $span = $this.find("span");

            // figure out what state we are in
            if ($span.hasClass(icon_asc)) {
                icon_add = icon_desc;
                sort_order = "desc";
            } else if ($span.hasClass(icon_desc)) {
                icon_add = icon_asc;
                sort_order = "asc";
            } else { // first time pressed, icon is neutral
                icon_add = icon_asc; // ascending by default
                sort_order = "asc"
            }

            // add new icon class
            $span.removeClass();
            $span.addClass("glyphicon " + icon_add);
        }

        loadContent($addon_menu, "addons-menu.php", {type: addon_type, sort: sort_type, order: sort_order}, function() {}, "GET");

        console.log(sort_type);
    })

})(jQuery, SITE_ROOT, JSON_LOCATION);