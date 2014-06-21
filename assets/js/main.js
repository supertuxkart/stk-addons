"use strict";
var oldDiv = "";

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


// Show a twitter bootstrap alert, insert into a container
function showAlert(options) {
    if (!_.isObject(options)) {
        console.error("options is not an array");
        return;
    }

    // define options
    var options = {
        $container  : $(options.container),
        type        : options.type || "alert-info",
        message     : options.message || "The alert message is empty",
        dismiss     : options.dismiss || true,
        auto_dismiss: options.auto_dismiss || true,
        interval    : options.interval || 4000
    };

    // create alert
    var divClass = "alert " + options.type + (options.dismiss ? " alert-dismissable" : "");
    var $div = $("<div></div>").addClass(divClass);

    // add dismiss button
    if (options.dismiss) {
        $div.append('<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>');
    }

    // add message
    $div.append(options.message);

    // set timer
    if (options.auto_dismiss) {
        setTimeout(function() {
            options.$container.fadeToggle(1000, function() {
                options.$container.empty();
            });
        }, options.interval);
    }

    // add to container
    options.$container.html($div);
    options.$container.fadeIn(500); // show because of the toggle or other css options
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

function confirm_delete(url) {
    if (confirm("Really delete this item?")) {
        window.location = url;
    }
}

function loadAddon(id, page) {
    addonRequest(page, id);
}

function addonRequest(page, id, value) {
    $.post(page, {id: id, value: value},
        function(data) {
            $("#content-addon_body").html(data);
            $("#content-addon_body").scrollTop(0);
        }
    );
}
function loadDiv(newDiv) {
    newDiv = "disp" + newDiv;
    if (oldDiv !== "")    document.getElementById(oldDiv).style.display = "none";
    document.getElementById(newDiv).style.display = "block";
    oldDiv = newDiv;
    document.getElementById("content-addon_body").innerHTML = "";
    document.getElementById("content-addon_body").style.display = "none";
}

function clearPanelStatus() {
    document.getElementById('right-content_status').innerHTML = '';
}

function textLimit(field, num) {
    if (field.value.length > num) {
        field.value = field.value.substring(0, num);
    }
}

/**
 * Loads an HTML page
 * Put the content of the body tag into the current page.
 * @param url URL of the HTML page to load
 * @param storage ID of the tag that gets to hold the output
 */
function loadHTML(url, storage) {
    var storage_elem = document.getElementById(storage);
    $.get(url, function(data) {
        if (storage_elem.innerHTML === undefined) {
            storage_elem = data;
        } else {
            storage_elem.innerHTML = data;
        }
    });
}

function addRating(rating, addonId, sel_storage, disp_storage) {
    // TODO fix ratings
    loadHTML(SITE_ROOT + 'include/addRating.php?rating=' + encodeURI(rating) + '&addonId=' + encodeURI(addonId), sel_storage);
    loadHTML(SITE_ROOT + 'include/addRating.php?addonId=' + encodeURI(addonId), disp_storage);
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


$(document).ready(function() {
    $("#news-messages").newsTicker();
    $('#lang-menu > a').click(function() {
        $('ul.menu_body').slideToggle('medium');
    });

    $('a.addon-list').click(function() {
        History.pushState(null, '', this.href);
        var url = this.href;
        var addonType = getUrlVars(url)['type'];
        if (addonType === undefined) {
            url = SITE_ROOT + $(this).children('meta').attr("content").replace('&amp;', '&');
            addonType = getUrlVars(url)['type'];
        }
        var addonId = getUrlVars(url)['name']; // we use the id as a varchar in the database
        loadContentWithAjax("#right-content_body", SITE_ROOT + 'addons-panel.php', {name: addonId, type: addonType}, clearPanelStatus)

        return false;
    });

    $('.add-rating').click(function() {

    });

    $('a.manage-list').click(function() {
        History.pushState(null, '', this.href);
        var view = getUrlVars(this.href)['view'];
        loadContentWithAjax("#right-content_body", SITE_ROOT + 'manage-panel.php', {view: view}, clearPanelStatus);

        return false;
    });

    $('a.user-list').click(function() {
        History.pushState(null, '', this.href);
        var user = getUrlVars(this.href)['user'];
        loadContentWithAjax("#right-content_body", SITE_ROOT + 'users-panel.php', {user: user}, clearPanelStatus)

        return false;
    });
});

