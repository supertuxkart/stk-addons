"use strict";
var oldDiv = "";

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

$(document).ready(function() {
    $("#news-messages").newsTicker();
    $('#lang-menu > a').click(function() {
        $('ul.menu_body').slideToggle('medium');
    });

    var $right_body = $("#right-content_body");

    $('a.addon-list').click(function() {
        History.pushState(null, '', this.href);
        var url = this.href;
        var addonType = getUrlVars(url)['type'];
        if (addonType === undefined) {
            url = SITE_ROOT + $(this).children('meta').attr("content").replace('&amp;', '&');
            addonType = getUrlVars(url)['type'];
        }

        var addonId = getUrlVars(url)['name']; // we use the id as a varchar in the database
        loadContent($right_body, SITE_ROOT + 'addons-panel.php', {name: addonId, type: addonType}, clearPanelStatus)

        return false;
    });

    $('.add-rating').click(function() {

    });


    $('a.user-list').click(function() {
        History.pushState(null, '', this.href);
        var user = getUrlVars(this.href)['user'];
        loadContent($right_body, SITE_ROOT + 'users-panel.php', {user: user}, clearPanelStatus);

        return false;
    });
});

