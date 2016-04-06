/**
 * copyright 2013      Stephen Just <stephenjust@gmail.com>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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

(function($, ROOT_LOCATION, JSON_LOCATION) {
    "use strict";

    var json_url_rating = JSON_LOCATION + "rating.php",
        json_url = JSON_LOCATION + "addons.php",
        addon_type = $("#addon-type").val(), // once per page, the addon_id is in every panel
        $addon_body = $("#addon-body"),
        $addon_menu = $("#addon-menu"),
        $addon_sort = $("#addon-sort"),
        $search_by = $("#addon-search-by"),
        original_menu;

    function addonFormSubmit(form_identifier, callback_success) {
        onFormSubmit(form_identifier, callback_success, $addon_body, json_url, {}, "POST");
    }

    registerPagination($addon_menu, "addons-menu.php");

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
    $addon_menu.on("click", "a.addon-list", function() {
        History.pushState(null, '', this.href);
        var $this = $(this),
            addon_id = $this.data("id");

        loadContent($addon_body, ROOT_LOCATION + 'addons-panel.php', {name: addon_id, type: addon_type});
        markMenuItemAsActive($this);

        return false;
    });

    // rating clicked
    $addon_body.on("click", '.add-rating', function() {
        var $ratings_container = $("#rating-container"),
            addon_id = $("#addon-id").val(),
            rating = this.value;

        // set new rating
        $.get(json_url_rating, {"action": "set", "addon-id": addon_id, "rating": rating}, function(data) {
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

        loadContent($addon_menu, "addons-menu.php", {type: addon_type, sort: sort_type, order: sort_order}, function() {
        }, "GET");
    });

    // addon proprieties changed
    addonFormSubmit("#addon-edit-props", function(data) {
        jsonCallback(data, function() {
            // update view
            $("#addon-description").html($("#addon-edit-description").val());
            $("#addon-designer").html($("#addon-edit-designer").val());
        });
    });

    // game include versions changed
    addonFormSubmit("#addon-edit-include-versions", function(data) {
        jsonCallback(data);
    });

    // set flags
    addonFormSubmit("#addon-set-flags", function(data) {
        jsonCallback(data);
    });

    // set notes
    addonFormSubmit("#addon-set-notes", function(data) {
        jsonCallback(data);
    });

    // approve file
    $addon_body.on("click", ".btn-approve-file", function() {
        var $this = $(this),
            $parent = $this.parent(),
            file_id = $parent.data("id");

        $.post(json_url, {action: "update-approval-file", "approve": true, "file-id": file_id}, function(data) {
            jsonCallback(data, function() {
                // update view
                $this.addClass("hidden");
                $this.siblings(".btn-unapprove-file").removeClass("hidden");
                $parent.parent().removeClass("bg-danger").addClass("bg-success");
            });
        });
    });

    // unapprove file
    $addon_body.on("click", ".btn-unapprove-file", function() {
        var $this = $(this),
            $parent = $this.parent(),
            file_id = $parent.data("id");

        $.post(json_url, {action: "update-approval-file", "approve": false, "file-id": file_id}, function(data) {
            jsonCallback(data, function() {
                // update view
                $this.addClass("hidden");
                $this.siblings(".btn-approve-file").removeClass("hidden");
                $parent.parent().removeClass("bg-success").addClass("bg-danger");
            });
        });
    });

    // set icon for file
    $addon_body.on("click", ".btn-set-icon", function() {
        var $this = $(this),
            $parent = $this.parent(),
            addon_id = $("#addon-id").val(),
            file_id = $parent.data("id");

        $.post(json_url, {action: "set-icon", "file-id": file_id, "addon-id": addon_id}, function(data) {
            jsonCallback(data, function() {
                // update view
                $(".btn-set-icon").removeClass("hidden");
                $this.addClass("hidden");
                // TODO update image icon
            });
        });
    });

    // set image for file
    $addon_body.on("click", ".btn-set-image", function() {
        var $this = $(this),
            $parent = $this.parent(),
            addon_id = $("#addon-id").val(),
            file_id = $parent.data("id");

        $.post(json_url, {action: "set-image", "file-id": file_id, "addon-id": addon_id}, function(data) {
            jsonCallback(data, function() {
                // update view
                $(".btn-set-image").removeClass("hidden");
                $this.addClass("hidden");
                // TODO update image on page
            });
        });
    });

    // delete file
    $addon_body.on("click", ".btn-delete-file", function() {
        var $this = $(this),
            $parent = $this.parent(),
            addon_id = $("#addon-id").val(),
            file_id = $parent.data("id");

        modalDelete("Are you sure you want to delete this file?", function() {
            $.post(json_url, {action: "delete-file", "file-id": file_id, "addon-id": addon_id}, function(data) {
                jsonCallback(data, function() {
                    // update view
                    $parent.parent().remove();
                });
            });
        });
    });

    // delete revision
    $addon_body.on("click", ".btn-delete-revision", function() {
        var $this = $(this),
            addon_id = $("#addon-id").val(),
            rev_id = $this.data("id");

        modalDelete("Are you sure you want to delete this revision?", function() {
            $.post(json_url, {action: "delete-revision", "revision-id": rev_id, "addon-id": addon_id}, function(data) {
                jsonCallback(data, function() {
                    // update view
                    refreshPage(); // TODO
                });
            });
        });
    });

    // delete addon
    $addon_body.on("click", "#btn-delete-addon", function() {
        var addon_id = $("#addon-id").val();

        modalDelete("Are you sure you want to delete this addon?", function() {
            $.post(json_url, {action: "delete-addon", "addon-id": addon_id}, function(data) {
                jsonCallback(data, function() {
                    // update view
                    $addon_body.html("");
                });
            });
        });
    });

})(jQuery, ROOT_LOCATION, JSON_LOCATION);
