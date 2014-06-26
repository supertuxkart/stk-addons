"use strict";

// Load the content of selector with some url
function loadContentWithAjax(selector, url_to_load, url_get_params, callback, callback_before) {
    var $selector = $(selector);
    url_get_params = url_get_params || {};

    if (_.isFunction(callback_before)) {
        callback_before();
    }

    $.get(url_to_load, url_get_params,function(data) {
        $selector.html(data);
        if (_.isFunction(callback)) {
            callback(data);
        }
    }).fail(function(e) {
        console.error("loadContentWithAjax failed");
        console.error(e);
    });
}

function onFormSubmit(form_identifier, callback_success, $container, url, data_type, request_method) {
    if (!_.isFunction(callback_success)) {
        throw "callback parameter is not a function";
    }

    // make defaults
    request_method = request_method || "POST";
    data_type = data_type || {};

    // unregister previous event handler
    $container.off("submit", form_identifier);

    $container.on("submit", form_identifier, function() {
        // put all values in array
        var data = $(form_identifier).serializeArray();

        // populate with our data type
        $.each(data_type, function(name, value) {
            data.push({name: name, value: value});
        });

        $.ajax({
            type   : request_method,
            url    : url,
            data   : $.param(data),
            success: callback_success,
            error  : function(xhr, ajaxOptions, thrownError) {
                console.error("Error onFormSubmit");
                console.error(xhr.status, ajaxOptions, thrownError);
            }
        }).fail(function() {
            console.error("onFormSubmit post request failed");
        });

        return false;
    });
}


function parseJSON(raw_string) {
    var jData;
    try {
        jData = JSON.parse(raw_string);
    } catch (e) {
        // silently fail on the client side
        jData = {};

        console.error("Parson JSON error: ", e);
        console.error("Raw string: ", raw_string);
    }

    return jData;
}

function growlError(message) {
    $.growl({
        title   : "Error",
        icon    : "glyphicon glyphicon-warning-sign",
        position: {
            from : "top",
            align: "center"
        },
        z_index : 9999,
        type    : "danger",
        message : message
    });
}
function growlSuccess(messsage) {
    $.growl({
        title   : "Success",
        icon    : "glyphicon glyphicon-ok-sign",
        position: {
            from : "top",
            align: "center"
        },
        z_index : 9999,
        type    : "success",
        message : messsage
    });
}

function modalDelete(message, yes_callback, no_callback) {
    bootbox.dialog({
        title   : "Delete",
        message : message,
        buttons : {
            danger: {
                label    : "Yes!",
                className: "btn-danger",
                callback : function() {
                    if (_.isFunction(yes_callback)) {
                        yes_callback();
                    }
                }
            },
            main  : {
                label    : "No",
                className: "btn-primary",
                callback : function() {
                    if (_.isFunction(no_callback)) {
                        no_callback();
                    }
                }
            }
        }
    });
}

// Read a page's GET URL variables and return them as an associative array.
function getUrlVars(url) {
    url = url || window.location.href;

    var vars = {}, hash, slice_start = url.indexOf('?');

    // url does not have any GET params
    if(slice_start === -1) {
        return vars;
    }

    var hashes = url.slice(slice_start + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars[hash[0]] = hash[1];
    }

    return vars;
}
