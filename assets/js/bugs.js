(function(window, document) {
    "use strict";

    var $content_bugs = $("#bugs-content");

    function registerEditors() {
        var editorOptions = {
            "html": true
        };
        $("#bug-description").wysihtml5(editorOptions);
        $("#bug-comment-description").wysihtml5(editorOptions);

        $("#bugs-all").dataTable();

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
                    $.get(SITE_ROOT + 'json/search.php', {"data-type": "addon", "search-filter": "name", "query": query}, function(data) {
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
        });
    }

    function btnToggle() {
        $("#btn-bugs-add").toggleClass("hide");
        $("#btn-bugs-back").toggleClass("hide");
        registerEditors();
    }

    function bugFormSubmit(form_identifier, callback_success) {
        if (!_.isFunction(callback_success)) {
            throw "callback parameter is not a function"
        }

        $content_bugs.on("submit", form_identifier, function() {
            $.ajax({
                type   : "POST",
                url    : SITE_ROOT + "json/bugs.php",
                data   : $(form_identifier).serialize(),
                success: callback_success
            });
            return false;
        });
    }

    var NavigateTo = {
        index: function() {
            History.back();
            loadContentWithAjax("#bug-content", BUGS_LOCATION + 'all.php', {}, btnToggle);
        },
        add  : function() {
            History.pushState({state: "add"}, '', "?add");
            loadContentWithAjax("#bug-content", BUGS_LOCATION + 'add.php', {}, function() {
                btnToggle();
            });
        },
        view : function(bug_id) {
            History.pushState({state: "view"}, '', "?bug_id=" + bug_id);
            loadContentWithAjax("#bug-content", BUGS_LOCATION + 'view.php', {bug_id: bug_id}, function() {
                btnToggle();
            });
        }
    };

    $content_bugs.on("click", "#btn-bugs-add", function() { // handle higher up the level for ajax
        NavigateTo.add();

        return false;
    });

    $content_bugs.on("click", "#btn-bugs-back", function() {
        NavigateTo.index();

        return false;
    });

    $content_bugs.on("click", "table .bugs", function() {
        NavigateTo.view($(this).parent().attr("data-id"));

        return false;
    });

    // add bug
    bugFormSubmit("#bug-add-form", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            showAlert({
                container: "#alert-container",
                type     : "alert-danger",
                message  : jData["error"]
            });
            return;
        }
        if (jData.hasOwnProperty("success")) {
            showAlert({
                container: "#alert-container",
                type     : "alert-success",
                message  : jData["success"]
            });
            NavigateTo.index();
            return;
        }
    });

    // add bug comment
    bugFormSubmit("#bug-add-comment-form", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            showAlert({
                container: "#alert-container-comments",
                type     : "alert-danger",
                message  : jData["error"]
            });
            return;
        }
        if (jData.hasOwnProperty("success")) {
            showAlert({
                container: "#alert-container-comments",
                type     : "alert-success",
                message  : jData["success"]
            });

            $("#bug-comments").prepend(jData["comment"]);
            return;
        }
    });

    // search
    bugFormSubmit("#bug-search-form", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            showAlert({
                container: "#alert-container",
                type     : "alert-danger",
                message  : jData["error"]
            });
            return;
        }
        $("#bug-content").html(data);
    });


    // Bind to StateChange Event
    // TODO fix browser back button
    History.Adapter.bind(window, 'statechange', function() {
        var state = History.getState();
        console.log(state);
    });

    registerEditors();

})(window, document);
