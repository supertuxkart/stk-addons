(function(window, document) {
    "use strict";

    var $manage_body = $("#manage-body");
    var json_url = SITE_ROOT + "json/manage.php";

    function manageFormSubmit(form_identifier, callback_success) {
        onFormSubmit(form_identifier, callback_success, $manage_body, json_url);
    }

    // left panel item clicked
    $('a.manage-list').click(function() {
        History.pushState(null, '', this.href);
        var view = getUrlVars(this.href)['view'];
        loadContentWithAjax("#manage-body", SITE_ROOT + 'manage-panel.php', {view: view});

        return false;
    });

    // role clicked
    $manage_body.on("click", "#manage-roles-roles button", function() {
        var $this = $(this);
        var $siblings = $this.siblings();

        // mark as active
        $this.addClass("active");

        // remove mark from others
        $siblings.removeClass("active");

        // update form role
        var role = $this.text();
        $("#manage-roles-permission-role").val(role);

        // update role checkboxes
        $.post(json_url, {action: "get-role", role: role}, function(data) {
            var jData = parseJSON(data);
            if (jData.hasOwnProperty("error")) {
                growlError(jData["error"]);
            }
            if (jData.hasOwnProperty("success")) {
                var permissions = jData["permissions"];
                var $checkboxes = $(".manage-roles-permission-checkbox");
                console.log(permissions);

                // update permissions checkboxes
                $checkboxes.each(function() {
                    this.checked = false; // uncheck

                    // role has permissions
                    if (_.contains(permissions, this.value)) {
                        this.checked = true;
                    }
                });
            }
        });
    });

    // role permission submitted
    manageFormSubmit("#manage-roles-permission-form", function(data) {
        var jData = parseJSON(data);
        if (jData.hasOwnProperty("error")) {
            growlError(jData["error"]);
        }
        if (jData.hasOwnProperty("success")) {
            growlSuccess(jData["success"]);
        }
    });

})(window, document);
