(function (window, document) {



    function btnToggle() {
        $("#btn-bugs-add").toggleClass("hide");
        $("#btn-bugs-back").toggleClass("hide");
    }

    $("#btn-bugs-add").click(function () {
        loadContentWithAjax("#bug-content", siteRoot + 'bugs/add.php');
        btnToggle();
        return false;
    });

    $("#btn-bugs-back").click(function () {
        loadContentWithAjax("#bug-content", siteRoot + 'bugs/all.php');
        btnToggle();
        return false;
    });

    // search forms
    var $bug_search = $("#bug-search");
    $bug_search.submit(function () {
        $.ajax({
            type   : "POST",
            url    : siteRoot + "json/bugs.php",
            data   : $bug_search.serialize(),
            success: function (data) {
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

    $("#content-bugs").on("click", "table .bugs", function () {
        loadContentWithAjax("#bug-content", encodeURI(siteRoot + 'bugs/view.php?bug_id=' + $(this).attr("data-id")))
        btnToggle();
        return false;
    });

})(window, document);
