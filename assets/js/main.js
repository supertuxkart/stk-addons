"use strict";

$(document).ready(function() {

    // mark link as active
    var page_parts = window.location.href.split("?"),
        current_page = page_parts[0],
        current_query = page_parts[1],
        link = $(".navbar-nav a[href^='" + current_page + "']");

    if (link.length === 1) { // top link
        link.parent().addClass("active");
    } else { // multiple top links
        var $closest_ul = link.first().closest("ul");

        if ($closest_ul.hasClass("dropdown-menu")) { // dropdown
            $closest_ul.parent().addClass("active");

            link.each(function() { // mark in dropdown
                if (current_query === this.href.split("?")[1]) {
                    $(this).parent().addClass("active");
                }
            });
        } else { // normal link, select first
            link.first().parent().addClass("active");
        }
    }

    $('#lang-menu > a').click(function() {
        $('ul.menu-body').slideToggle('fast'); // language menu
    });
});
