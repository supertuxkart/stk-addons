/**
 * copyright 2014-2015 Daniel Butum <danibutum at gmail dot com>
 *
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */
"use strict";

// define time constants
var MSECONDS_MINUTE = 60000,
    MSECONDS_HOUR = 3600000,
    MSECONDS_DAY = 86400000,
    MSECONDS_WEEK = 604800000,
    MSECONDS_MONTH = 2592000000,
    MSECONDS_YEAR = 31536000000;

var SEARCH_URL = JSON_LOCATION + "search.php";
var EMPTY_FUNCTION = function() {};

/**
 * Register the default pagination on a page
 *
 * @param {jQuery} $container jQuery object
 * @param {string} url the php page that handles pagination
 */
function registerPagination($container, url) {
    // limit changed
    $container.on("change", ".stk-pagination .limit select", function() {
        var limit = this.value,
            url_vars = getUrlVars();

        if (!parseInt(url_vars["p"])) {
            url_vars["p"] = 1;
        }

        url_vars["l"] = limit;
        loadContent($container, url, url_vars, EMPTY_FUNCTION, "GET");
    });

    // page button clicked
    $container.on("click", ".stk-pagination ul.pagination a", function() {
        var url_vars = getUrlVars(this.href),
            page = url_vars["p"],
            limit = url_vars["l"];

        if (!parseInt(page)) { // is not a valid button
            return false;
        }

        if (!limit || !parseInt(limit)) {
            url_vars["l"] = 10;
        }

        loadContent($container, url, url_vars, EMPTY_FUNCTION, "GET");

        return false;
    });
}

/**
 * Mark a left menu item as active
 * @param {jQuery} $item
 */
function markMenuItemAsActive($item) {
    $item.siblings().removeClass("active");
    $item.addClass("active");
}

/**
 * Check if time is in certain time interval
 *
 * @param {number} time the time to check in milliseconds
 * @param {number} elapsed_time the interval
 *
 * @return {bool}
 */
function isInTimeInterval(time, elapsed_time) {
    var current_time = (new Date()).getTime(),
        elapsed = current_time - time;

    return elapsed < elapsed_time;
}

/**
 * Load the content of the url into an element
 *
 * @param {jQuery} $content jQuery object that will contain the page result
 * @param {string} url the url to get
 * @param {object} params containing GET or POST params
 * @param {function} [callback] function that is called after the content was loaded
 * @param {string} [request_type] the type of request, GET or POST, default is GET
 */
function loadContent($content, url, params, callback, request_type) {
    request_type = request_type || "GET";
    callback = callback || EMPTY_FUNCTION;

    // define callback
    function onCompleteCallback(response, status, xhr) {
        if (status === "error") {
            console.error("Error on loadContent");
            console.error(response, status, xhr);
            $content.html("Sorry there was an error " + xhr.status + " " + xhr.statusText);
        } else {
            callback();
        }
    }

    if (request_type === "GET") {
        $content.load(url + "?" + $.param(params), onCompleteCallback);
    } else if (request_type === "POST") {
        $content.load(url, params, onCompleteCallback);
    } else {
        console.error("request_type: ", request_type);
        console.error("request type is invalid")
    }
}

/**
 * Same as the onFormSubmit function but this stops the form from submitting and calls the provided callback instead
 *
 * @param {string} form_identifier string representing the form unique identifier, usually id, eg: #main-bugs
 * @param {function} callback function that is called instead of the form submit
 * @param {jQuery} $container a jQuery object representing a parent of the form
 */
function onFormSubmitPrevent(form_identifier, callback, $container) {
    $container.off("submit", form_identifier);
    $container.on("submit", form_identifier, function(e) {
        e.preventDefault();
        callback();
    });
}

/**
 * Handle event when there is form submit
 *
 * @param {string} form_identifier string representing the form unique identifier, usually id, eg: #main-bugs
 * @param {function} callback_success function that is called on form submit success
 * @param {jQuery} $container a jQuery object representing a parent of the form
 * @param {string} url the url to submit to
 * @param {object} [data_type] additional parameters to add to the request
 * @param {string} [request_method] POST or GET, default is GET
 */
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
                console.error(xhr, ajaxOptions, thrownError);
            }
        }).fail(function() {
            console.error("onFormSubmit post request failed");
        });

        return false;
    });
}

/**
 * Handle the response from the json/ part of the website,
 * On success/error growl (displays) the message to the user page via a popup.
 * If the return of any of the callback functions is false then that message will not be displayed (aka growl)
 *
 * @param {string} data
 * @param {function} [success_callback]
 * @param {function} [error_callback]
 */
function jsonCallback(data, success_callback, error_callback) {
    var jData = parseJSON(data), growl_success = true, growl_error = true;
    if (jData.hasOwnProperty("success")) {
        if (success_callback) {
            growl_success = (success_callback(jData) !== false);
        }
        if (growl_success) {
            growlSuccess(jData["success"]);
        }
    }
    if (jData.hasOwnProperty("error")) {
        if (error_callback) {
            growl_error = (error_callback(jData) !== false);
        }
        if (growl_error) {
            growlError(jData["error"]);
        }
    }
}

/**
 * Alias for getElementById
 *
 * @param {string} id the element id
 *
 * @return {Element} html element
 */
function getByID(id) {
    return document.getElementById(id);
}

/**
 * Parse a json string
 *
 * @param {string} raw_string the json string
 *
 * @return {object} the parsed json data, or empty object if there was an error, and message written to the console
 */
function parseJSON(raw_string) {
    var jData = {}; // silently fail on the client side

    try {
        jData = JSON.parse(raw_string);
    } catch (e) {
        console.error("Parson JSON error: ", e);
        console.error("Raw string: ", raw_string);
    }

    return jData;
}

/**
 * Display a error message popup
 *
 * @param {string} message the message to the user
 */
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

/**
 * Display a success message popup
 *
 * @param {string} message the message to the user
 */
function growlSuccess(message) {
    $.growl({
        title   : "Success",
        icon    : "glyphicon glyphicon-ok-sign",
        position: {
            from : "top",
            align: "center"
        },
        z_index : 9999,
        type    : "success",
        message : message
    });
}

/**
 * Display a modal with delete button and confirmation message
 *
 * @param {string} message the message to the user
 * @param {function} [yes_callback]  that is called when the user answer yes to the modal
 * @param {function} [no_callback]  that is called when the user answers no to the modal
 */
function modalDelete(message, yes_callback, no_callback) {
    yes_callback = yes_callback || EMPTY_FUNCTION;
    no_callback = no_callback || EMPTY_FUNCTION;

    bootbox.dialog({
        title  : "Delete",
        message: message,
        buttons: {
            danger: {
                label    : "Yes!",
                className: "btn-danger",
                callback : yes_callback
            },
            main  : {
                label    : "No",
                className: "btn-primary",
                callback : no_callback
            }
        }
    });
}

/**
 * Redirect the current page with delay
 *
 * @param {string} url the destination
 * @param {float|int} [seconds] delay in redirection, default is 0
 */
function redirectTo(url, seconds) {
    seconds = seconds || 0;

    var timeout = setTimeout(function() {
        window.location = url;
        clearTimeout(timeout);
    }, seconds * 1000);
}

/**
 * Refresh the current page
 */
function refreshPage() {
    redirectTo(window.location.href, 0);
}

/**
 * Check if it is a wysiwyg5 editor
 *
 * @param {jQuery} $editor_container should contain the editor
 *
 * @return wysiwyg5 editor or null if not an editor
 */
function isEditor($editor_container) {
    return $editor_container.data("wysihtml5");
}

/**
 * Update the value of a wysiwyg5 editor
 *
 * @param {jQuery} $editor_container
 * @param {string} value
 */
function editorUpdate($editor_container, value) {
    $editor_container.data("wysihtml5").editor.setValue(value);
}

/**
 * Init a wysiwyg5 editor only once
 *
 * @param {jQuery} $editor_container jQuery object representing the container
 * @param {object} editor_options options for the wysiwyg5
 *
 * @return {null|wysihtml5} if editor already exists, the wysiwyg5 editor otherwise
 */
function editorInit($editor_container, editor_options) {
    if (!isEditor($editor_container)) { // editor does not exist
        return $editor_container.wysihtml5(editor_options);
    }

    return null;
}

/**
 * Read a page's GET URL variables and return them as an hash map
 *
 * @param {string} [url] default is the current page
 *
 * @return {object} hash map of all vars
 */
function getUrlVars(url) { // TODO fix usage of getUrlVars because if we activate the .htaccess some features might not work
    url = url || window.location.href;

    var vars = {}, hash, slice_start = url.indexOf('?');

    // url does not have any GET params
    if (slice_start === -1) {
        return vars;
    }

    var hashes = url.slice(slice_start + 1).split('&');
    for (var i = 0; i < hashes.length; i++) {
        hash = hashes[i].split('=');
        vars[hash[0]] = hash[1];
    }

    return vars;
}

// Extend string. Eg "{0} is {1}".format("JS", "nice") will output "JS is nice"
if (!String.prototype.format) {
    String.prototype.format = function() {
        var args = arguments;
        return this.replace(/\{\{|\}\}|\{(\d+)\}/g, function(curlyBrack, index) {
            return ((curlyBrack == "{{") ? "{" : ((curlyBrack == "}}") ? "}" : args[index]));
        });
    };
}
