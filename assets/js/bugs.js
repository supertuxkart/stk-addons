(function(window, document) {
    "use strict";

    var $content_bugs = $("#bugs-content");

    function btnToggle() {
        $("#btn-bugs-add").toggleClass("hide");
        $("#btn-bugs-back").toggleClass("hide");
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
                $("#bug-description").wysihtml5();
            });
        },
        view : function(bug_id) {
            History.pushState({state: "view"}, '', "?bug_id=" + bug_id);
            loadContentWithAjax("#bug-content", BUGS_LOCATION + 'view.php', {bug_id: bug_id}, btnToggle)
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

    // add
    bugFormSubmit("#bug-add-form", function(data) {
        var jData = JSON.parse(data);
        console.log(jData);
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

    // search
    bugFormSubmit("#bug-search-form", function(data) {
        var jData = JSON.parse(data);
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

    $("#bug-description").wysihtml5();

})(window, document);
