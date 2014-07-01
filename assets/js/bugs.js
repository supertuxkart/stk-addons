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

    // load essential elements and options
    var $main_bugs = $("#bugs-main"); // the top container wrapper for the bugs
    var $content_bugs = $("#bugs-content"); // the content that always changes via ajax
    var $btn_back = $("#btn-bugs-back"), $btn_add = $("#btn-bugs-add");
    var editorOptions = {
        toolbar: {
            "font-styles": false
        }
    };
    var search_url = JSON_LOCATION + "search.php";
    var json_url = JSON_LOCATION + "bugs.php";
    var tableOptions = {
        searching  : false,
        "aaSorting": [] // Disable initial sort
    }
    var index_data_table; // hold the data table object
    var $index_bugs_table; // hold the jquery selector for the index
    var $view_comment_description;


    // begin helper functions
    function btnToggle() {
        $btn_add.toggleClass("hide");
        $btn_back.toggleClass("hide");
    }

    function bugFormSubmit(form_identifier, callback_success) {
        onFormSubmit(form_identifier, callback_success, $content_bugs, json_url, {}, "POST");
    }

    function registerEditors() {
        $view_comment_description = $("#bug-comment-description");
        $view_comment_description.wysihtml5(editorOptions);

        // init table
        $index_bugs_table = $("#bugs-table");
        index_data_table = $index_bugs_table.DataTable(tableOptions);
    }

    function registerAddPage() {
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
                    $.get(search_url, {"data-type": "addon", "search-filter": "name", "query": query}, function(data) {
                        var jData = parseJSON(data);
                        if (jData.hasOwnProperty("error")) {
                            console.error(jData["error"]);
                            return;
                        }

                        // fill display popup
                        for (var i = 0; i < jData["addons"].length; i++) {
                            matches.push({"id": jData["addons"][i]})
                        }

                        cb(matches);
                    });
                }
            }
        );
    }

    var NavigateTo = {
        index: function() {
            History.back();
            loadContent($content_bugs, BUGS_LOCATION + 'all.php', {}, function() {
                btnToggle();
                registerEditors();
            });
        },
        add  : function() {
            History.pushState({state: "add"}, '', "?add");
            loadContent($content_bugs, BUGS_LOCATION + 'add.php', {}, function() {
                btnToggle();
                registerEditors();
                registerAddPage();
            });
        },
        view : function(bug_id) {
            History.pushState({state: "view"}, '', "?bug_id=" + bug_id);
            loadContent($content_bugs, BUGS_LOCATION + 'view.php', {bug_id: bug_id}, function() {
                btnToggle();
                registerEditors();
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
            $content_bugs.html(jData["bugs-all"]);
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
    }, $main_bugs, search_url, {"data-type": "bug"}, "GET");

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
    $main_bugs.on("click", "#btn-bugs-delete", function() {
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
    $main_bugs.on("click", "#btn-bugs-close", function() {
        var $modal = $("#modal-close"), $modal_description = $("#modal-close-reason");

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
        })
    });

    // edit bug clicked
    $main_bugs.on("click", "#btn-bugs-edit", function() {
        var $modal = $("#modal-edit"),
            el_modal_title = document.getElementById("bug-title-edit"),
            $modal_description = $("#bug-description-edit"),
            el_view_title = document.getElementById("bug-view-title"),
            el_view_description = document.getElementById("bug-view-description");

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
    });

    // delete bug comment clicked
    $main_bugs.on("click", ".btn-bugs-comments-delete", function() {
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
    $main_bugs.on("click", ".btn-bugs-comments-edit", function() {
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
    $main_bugs.on("click", "table .bugs", function() {
        NavigateTo.view($(this).parent().attr("data-id"));

        return false;
    });

    // Bind to StateChange Event
    // TODO fix browser back button
    History.Adapter.bind(window, 'statechange', function() {
        var state = History.getState();
        console.log(state);
    });

    registerEditors();
    registerAddPage();

})(window, document);
