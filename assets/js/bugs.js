(function(window, document) {
    "use strict";

    function btnToggle() {
        $("#btn-bugs-add").toggleClass("hide");
        $("#btn-bugs-back").toggleClass("hide");
    }

    $("#btn-bugs-add").click(function() {
        History.pushState({state: "add"}, '', "?add");
        loadContentWithAjax("#bug-content", BUGS_LOCATION + 'add.php', {}, btnToggle);

        return false;
    });

    $("#btn-bugs-back").click(function() {
        History.back();
        loadContentWithAjax("#bug-content", BUGS_LOCATION + 'all.php', {}, btnToggle);

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

    $("#content-bugs").on("click", "table .bugs", function() {
        var bug_id = $(this).parent().attr("data-id");
        History.pushState({state: "view"}, '', "?bug_id=" + bug_id);
        loadContentWithAjax("#bug-content", BUGS_LOCATION + 'view.php', {bug_id: bug_id}, btnToggle)

        return false;
    });

    $("#bug-description").wysihtml5();

    // Bind to StateChange Event
    // TODO fix browser back button
    History.Adapter.bind(window, 'statechange',function() {
        var state = History.getState();
        console.log(state);
    });

})(window, document);
