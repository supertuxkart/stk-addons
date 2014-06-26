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
    var search_url = SITE_ROOT + 'json/search.php';
    var tableOptions = {
        searching  : false,
        "aaSorting": [] // Disable initial sort
    }
    var data_table; // hold the data table object


    // helper functions
    function registerEditors() {
        $("#bug-description").wysihtml5(editorOptions); // from add page
        $("#bug-comment-description").wysihtml5(editorOptions);

        // init table
        var $bugs_table = $("#bugs-table");
        data_table = $bugs_table.DataTable(tableOptions);

        // search
        onFormSubmit("#bug-search-form", function(data) {
            History.pushState({state: "search"}, '', "?search");

            // not in the main page
            if (!_.isEmpty(getUrlVars())) {
                console.log("not main page");
            }

            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                // update view
                data_table.destroy();
                $content_bugs.html(jData["bugs-all"])
                data_table = $bugs_table.DataTable(tableOptions);

                // show button
                btnToggle();
            }
        }, $main_bugs, search_url, {"data-type": "bug"}, "GET");

    }

    function btnToggle() {
        $btn_add.toggleClass("hide");
        $btn_back.toggleClass("hide");
    }

    function bugFormSubmit(form_identifier, callback_success) {
        onFormSubmit(form_identifier, callback_success, $content_bugs, SITE_ROOT + "json/bugs.php", {}, "POST");
    }

    var NavigateTo = {
        index: function() {
            History.back();
            loadContentWithAjax("#bugs-content", BUGS_LOCATION + 'all.php', {}, function() {
                btnToggle();
                registerEditors();
            });
        },
        add  : function() {
            History.pushState({state: "add"}, '', "?add");
            loadContentWithAjax("#bugs-content", BUGS_LOCATION + 'add.php', {}, function() {
                btnToggle();
                registerEditors();

                // add bug page,
                $("#addon-name").typeahead({
                        hint     : true,
                        highlight: true,
                        minLength: 2
                    },
                    {
                        name      : 'addon-search',
                        displayKey: "id",
                        source    : function(query, cb) {
                            var matches = [];
                            $.get(search_url, {"data-type": "addon", "search-filter": "name", "query": query}, function(data) {
                                var jData = parseJSON(data);
                                if (jData.hasOwnProperty("error")) {
                                    console.error(jData["error"]);
                                    return;
                                }

                                for (var i = 0; i < jData["addons"].length; i++) {
                                    matches.push({"id": jData["addons"][i]})
                                }

                                cb(matches);
                            });
                        }
                    }
                );
            });
        },
        view : function(bug_id) {
            History.pushState({state: "view"}, '', "?bug_id=" + bug_id);
            loadContentWithAjax("#bugs-content", BUGS_LOCATION + 'view.php', {bug_id: bug_id}, function() {
                btnToggle();
                registerEditors();
            });
        }
    };

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
            $("#bug-comment-description").html("");
        }
    });

    // close bug clicked
    $main_bugs.on("click", "#btn-bugs-close", function() {
        var $modal = $("#modal-close");

        console.info("Close bug clicked");
        $modal.modal();

        bugFormSubmit("#modal-close-form", function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                $modal.modal("hide");
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

        $modal.on("shown.bs.modal", function(e) {
            if (!$modal_description.data("wysihtml5")) { // editor does not exist
                $modal_description.wysihtml5(editorOptions);
            }
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
        var $this = $(this), $modal = $("#modal-delete");
        var id = $this.data("id");

        console.info("Delete comment clicked", id);
        $modal.data("id", id).modal(); // set the id to the modal

        return false;
    });

    // delete modal yes clicked
    $main_bugs.on("click", "#modal-delete-btn-yes", function() {
        var $modal = $("#modal-delete"),
            id = $modal.data("id");

        console.info("Delete modal btn yes clicked", id);
        $.post(SITE_ROOT + "json/bugs.php", {action: "delete-comment", "comment-id": id}, function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                growlSuccess(jData["success"]);

                // delete comment from view
                $("#c" + id).remove();

                $modal.modal('hide');
            }
        });
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
            if (!$modal_description.data("wysihtml5")) { // editor does not exist
                $modal_description.wysihtml5(editorOptions);
            }

            // update view
            $("#modal-comment-edit-id").val(id);
            $modal_description.data("wysihtml5").editor.setValue($view_description.html());
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

    // hover over close bug status
    $main_bugs.on("mouseenter", "#bug-view-status", function() {
        $("#bug-view-status").popover("toggle");
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

})(window, document);
