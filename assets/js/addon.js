/**
 * copyright 2013 Stephen Just <stephenjust@gmail.com>
 *           2014 Daniel Butum <danibutum at gmail dot com>
 *
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */

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
    if (oldDiv !== "")    getByID(oldDiv).style.display = "none";
    getByID(newDiv).style.display = "block";
    oldDiv = newDiv;
    getByID("content-addon_body").innerHTML = "";
    getByID("content-addon_body").style.display = "none";
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


(function($) {
    "use strict";

    var $addon_body = $("#addon-body");

    // left panel user addon clicked
    $('a.addon-list').click(function() {
        History.pushState(null, '', this.href);
        var url = this.href;
        var addonType = getUrlVars(url)['type'];
        if (addonType === undefined) {
            url = SITE_ROOT + $(this).children('meta').attr("content").replace('&amp;', '&');
            addonType = getUrlVars(url)['type'];
        }

        var addonId = getUrlVars(url)['name']; // we use the id as a varchar in the database
        loadContent($addon_body, SITE_ROOT + 'addons-panel.php', {name: addonId, type: addonType});

        return false;
    });

    $('.add-rating').click(function() {

    });

})(jQuery);