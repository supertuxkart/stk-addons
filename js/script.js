var oldElSub = "";
var oldSub = "";
var oldRoot = "";
var oldDiv = "";

$(document).ready(function () {
    $("#news-messages").newsTicker();
    $('#lang-menu > a').click(function () {
        $('ul.menu_body').slideToggle('medium');
    });

    $('a.addon-list').click(function () {
        history.pushState({path: this.path}, '', this.href);
        var url = this.href;
        var addonType = getUrlVars(url)['type'];
        if (addonType === undefined) {
            url = siteRoot + $(this).children('meta').attr("content").replace('&amp;', '&');
            addonType = getUrlVars(url)['type'];
        }
        var addonId = getUrlVars(url)['name'];
        loadFrame(addonId, siteRoot + 'addons-panel.php?type=' + addonType);
        clearPanelStatus();
        return false;
    });

    $('a.manage-list').click(function () {
        history.pushState({path: this.path}, '', this.href);
        var url = this.href;
        var view = getUrlVars(url)['view'];
        loadFrame(view, siteRoot + 'manage-panel.php');
        clearPanelStatus();
        return false;
    });

    $('a.user-list').click(function () {
        history.pushState({path: this.path}, '', this.href);
        var url = this.href;
        var user = getUrlVars(url)['user'];
        loadFrame(user, siteRoot + 'users-panel.php');
        clearPanelStatus();
        return false;
    });
});

function confirm_delete(url) {
    if (confirm("Really delete this item?")) {
        window.location = url;
    }
}

function loadAddon(id, page) {
    addonRequest(page, id);
}

function loadFrame(id, page, value) {
    var panelDiv = document.getElementById('right-content_body');
    panelDiv.innerHTML = '<div id="loading"></div>';
    $.get(page, {id: id, value: value},
        function (data) {
            $("#right-content_body").html(data);
            $("#right-content_body").scrollTop(0);
        }
    );
}

function addonRequest(page, id, value) {
    $.post(page, {id: id, value: value},
        function (data) {
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
    var div = document.getElementById('right-content_status');
    div.innerHTML = '';
}

function textLimit(field, num) {
    if (field.value.length > num) {
        field.value = field.value.substring(0, num);
    }
}

function addRating(rating, addonId, sel_storage, disp_storage) {
    loadHTML(siteRoot + 'include/addRating.php?rating=' + encodeURI(rating) + '&addonId=' + encodeURI(addonId), sel_storage);
    loadHTML(siteRoot + 'include/addRating.php?addonId=' + encodeURI(addonId), disp_storage);
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
