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

function parseJSON(raw_string) {
    var jData;
    try {
        jData = JSON.parse(raw_string);
    } catch (e) {
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

// Read a page's GET URL variables and return them as an associative array.
function getUrlVars(url) {
    if (url === undefined) {
        url = window.location.href;
    }

    var vars = [], hash;
    var hashes = url.slice(url.indexOf('?') + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}
