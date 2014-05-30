(function(window, document) {
    "use strict";

    var $content_bugs = $("#content-bugs");

    function btnToggle() {
        $("#btn-bugs-add").toggleClass("hide");
        $("#btn-bugs-back").toggleClass("hide");
    }

    $("#bug-description").wysihtml5();
    $content_bugs.on("click", "#btn-bugs-add", function() { // handle higher up the level for ajax
        History.pushState({state: "add"}, '', "?add");
        loadContentWithAjax("#bug-content", BUGS_LOCATION + 'add.php', {}, function() {
            btnToggle();
            $("#bug-description").wysihtml5();
        });

        return false;
    });

    $content_bugs.on("click", "#btn-bugs-back", function() {
        History.back();
        loadContentWithAjax("#bug-content", BUGS_LOCATION + 'all.php', {}, btnToggle);

        return false;
    });

    $content_bugs.on("click", "table .bugs", function() {
        var bug_id = $(this).parent().attr("data-id");
        History.pushState({state: "view"}, '', "?bug_id=" + bug_id);
        loadContentWithAjax("#bug-content", BUGS_LOCATION + 'view.php', {bug_id: bug_id}, btnToggle)

        return false;
    });

    // search forms
    var $bug_search = $("#bug-search");
    $bug_search.submit(function() {
        $.ajax({
            type   : "POST",
            url    : SITE_ROOT + "json/bugs.php",
            data   : $bug_search.serialize(),
            success: function(data) {
                var jData = JSON.parse(data);
                if ("error" in jData || _.isEmpty(jData)) {
                    showAlert({
                        container: "#alert-container",
                        type     : "alert-danger",
                        message  : '<strong>Error:</strong> Nothing to search for'
                    });
                    return;
                }
                $("#bug-content").html(data);
            }
        });

        return false;
    });

    // Bind to StateChange Event
    // TODO fix browser back button
    History.Adapter.bind(window, 'statechange', function() {
        var state = History.getState();
        console.log(state);
    });

})(window, document);
