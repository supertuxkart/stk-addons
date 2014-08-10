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

(function($, document) {
    "use strict";

    // load essential elements and options
    var $bugs_main = $("#bugs-main"), // the top container wrapper for the bugs
        $bugs_body = $("#bugs-body"), // the content that always changes via ajax
        $btn_back = $("#btn-bugs-back"),
        $btn_add = $("#btn-bugs-add"),
        editorOptions = {
            toolbar: {
                "font-styles": false
            }
        },
        json_url = JSON_LOCATION + "bugs.php",
        tableOptions = {
            searching  : false, // disable default table sorting
            "aaSorting": [] // Disable initial sort
        },
        index_data_table, // hold the data table object
        $index_bugs_table, // hold the jquery selector for the index
        $view_comment_description;


    // begin helper functions
    function btnToggle() {
        $btn_add.toggleClass("hide");
        $btn_back.toggleClass("hide");
    }

    function bugFormSubmit(form_identifier, callback_success) {
        onFormSubmit(form_identifier, callback_success, $bugs_body, json_url, {}, "POST");
    }

    // load page index
    function onPageIndex() {
        $index_bugs_table = $("#bugs-table");
        index_data_table = $index_bugs_table.DataTable(tableOptions);
    }

    function onPageView() {
        $view_comment_description = $("#bug-comment-description");
        $view_comment_description.wysihtml5(editorOptions);
    }

    // load page add
    function onPageAdd() {
        $("#bug-description").wysihtml5(editorOptions);

        // add bug page,
        $("#addon-name").typeahead({
                hint     : true,
                highlight: true,
                minLength: 1
            },
            {
                name      : 'addon-search',
                displayKey: "id",
                source    : function(query, cb) {
                    var matches = [];

                    // search
                    $.get(SEARCH_URL, {"data-type": "addon", "addon-type": "all", "query": query, "flags": ["name"]}, function(data) {
                        var jData = parseJSON(data);
                        if (jData.hasOwnProperty("error")) {
                            console.error(jData["error"]);
                            return;
                        }
                        if (jData.hasOwnProperty("success")) {
                            // fill display popup
                            var addons = jData["addons"];
                            for (var i = 0; i < addons.length; i++) {
                                matches.push({"id": addons[i]["name"]})
                            }

                            cb(matches);
                        }
                    });
                }
            }
        );
    }

    var NavigateTo = {
        index: function() {
            History.back();
            loadContent($bugs_body, BUGS_LOCATION + 'all.php', {}, function() {
                btnToggle();
                onPageIndex();
            });
        },
        add  : function() {
            History.pushState({state: "add"}, '', "?add");
            loadContent($bugs_body, BUGS_LOCATION + 'add.php', {}, function() {
                btnToggle();
                onPageAdd();
            });
        },
        view : function(bug_id) {
            History.pushState({state: "view"}, '', "?bug_id=" + bug_id);
            loadContent($bugs_body, BUGS_LOCATION + 'view.php', {bug_id: bug_id}, function() {
                btnToggle();
                onPageView();
            });
        }
    };
    // end helper functions

    // navigate add button clicked
    $btn_add.on("click", function() { // handle higher up the level for ajax
        NavigateTo.add();

        return false;
    });

    // navigate back button clicked
    $btn_back.on("click", function() {
        NavigateTo.index();

        return false;
    });

    // search clicked
    onFormSubmit("#bug-search-form", function(data) {
        History.pushState({state: "search"}, '', "?search");

        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            growlError(jData["error"]);
        }
        if (jData.hasOwnProperty("success")) {
            // update view
            index_data_table.destroy(true);

            // replace html
            $bugs_body.html(jData["bugs-all"]);
            $index_bugs_table = $("#bugs-table"); // reinstate data

            // FIXME possible delay may cause datatable not to initialize

            // new datatable
            index_data_table = $index_bugs_table.DataTable(tableOptions);

            // toggle buttons only when on main page, if we are already on another page
            // the back button is already shown
            if($btn_back.hasClass("hide")) {
                btnToggle();
            }
        }
    }, $bugs_main, SEARCH_URL, {"data-type": "bug"}, "GET");

    // add bug form
    bugFormSubmit("#bug-add-form", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            growlError(jData["error"]);
        }
        if (jData.hasOwnProperty("success")) {
            growlSuccess(jData["success"]);
            NavigateTo.index();
        }
    });

    // add bug comment form
    bugFormSubmit("#bug-add-comment-form", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            growlError(jData["error"]);
        }
        if (jData.hasOwnProperty("success")) {
            growlSuccess(jData["success"]);

            $("#bug-comments").prepend(jData["comment"]);
            editorUpdate($view_comment_description, "");
        }
    });

    // delete bug clicked
    $bugs_main.on("click", "#btn-bugs-delete", function() {
        var id = $("#bug-id").val();

        console.log("Delete bug clicked", id);
        modalDelete("Are you sure you want to delete this bug?", function() {
            $.post(json_url, {action: "delete", "bug-id": id}, function(data) {
                var jData = parseJSON(data);
                if (jData.hasOwnProperty("error")) {
                    growlError(jData["error"]);
                }
                if (jData.hasOwnProperty("success")) {
                    growlSuccess(jData["success"]);

                    bootbox.hideAll();

                    NavigateTo.index();
                }
            });
        });

        return false;
    });

    // close bug clicked
    $bugs_main.on("click", "#btn-bugs-close", function() {
        var $modal = $("#modal-close"),
            $modal_description = $("#modal-close-reason");

        console.info("Close bug clicked");
        $modal.modal();

        $modal.on("shown.bs.modal", function() {
            editorInit($modal_description, editorOptions);
        });

        bugFormSubmit("#modal-close-form", function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                $modal.modal("hide");

                // refresh page by redirect
                redirectTo();
            }
        });

        return false;
    });

    // edit bug clicked
    $bugs_main.on("click", "#btn-bugs-edit", function() {
        var $modal = $("#modal-edit"),
            el_modal_title = getByID("bug-title-edit"),
            $modal_description = $("#bug-description-edit"),
            el_view_title = getByID("bug-view-title"),
            el_view_description = getByID("bug-view-description");

        console.info("Edit bug clicked");
        $modal.modal();

        $modal.on("shown.bs.modal", function() {
            editorInit($modal_description, editorOptions);
        });

        bugFormSubmit("#modal-edit-form", function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                // update view
                el_view_title.innerHTML = el_modal_title.value;
                el_view_description.innerHTML = $modal_description.val();

                $modal.modal('hide');
            }
        });

        return false;
    });

    // delete bug comment clicked
    $bugs_main.on("click", ".btn-bugs-comments-delete", function() {
        var $this = $(this);
        var id = $this.data("id");

        console.info("Delete comment clicked", id);
        modalDelete("Are you sure you want to delete this comment?", function() {
            $.post(json_url, {action: "delete-comment", "comment-id": id}, function(data) {
                var jData = parseJSON(data);
                if (jData.hasOwnProperty("error")) {
                    growlError(jData["error"]);
                }
                if (jData.hasOwnProperty("success")) {
                    growlSuccess(jData["success"]);

                    // delete comment from view
                    $("#c" + id).remove();

                    bootbox.hideAll();
                }
            });
        });

        return false;
    });

    // edit bug comment clicked
    $bugs_main.on("click", ".btn-bugs-comments-edit", function() {
        var $this = $(this),
            $modal = $("#modal-comment-edit"),
            id = $this.data("id"),
            $modal_description = $("#bug-comment-edit-description"),
            $view_description = $("#c" + id + " .panel-body");

        console.info("Edit comment clicked", id);
        $modal.modal();

        $modal.on("shown.bs.modal", function(e) {
            editorInit($modal_description, editorOptions);

            // update view
            $("#modal-comment-edit-id").val(id);
            editorUpdate($modal_description, $view_description.html());
        });

        bugFormSubmit("#modal-comment-edit-form", function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                // update view
                $view_description.html($modal_description.val());

                $modal.modal("hide");
            }
        });

        return false;
    });

    // clicked on a bug in the table
    $bugs_main.on("click", "table .bugs", function() {
        NavigateTo.view($(this).parent().data("id"));

        return false;
    });

    // Bind to StateChange Event
    // TODO fix browser back button
    History.Adapter.bind(window, 'statechange', function() {
        var state = History.getState();
        console.log(state);
    });

    onPageAdd();
    onPageIndex();
    onPageView();

})(jQuery, document);
