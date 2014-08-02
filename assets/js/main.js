"use strict";

$(document).ready(function() {

    // mark link as active
    var page_parts = window.location.href.split("?"),
        current_page = page_parts[0],
        current_query = page_parts[1],
        link = $(".navbar-nav a[href^='" + current_page + "']");

    if (link.length === 1) { // top link
        link.parent().addClass("active");
    } else { // dropdown
        link.closest("ul").parent().addClass("active");

        link.each(function() { // mark in dropdown
            if (current_query === this.href.split("?")[1]) {
                $(this).parent().addClass("active");
            }
        });
    }

    $('#lang-menu > a').click(function() {
        $('ul.menu-body').slideToggle('fast'); // language menu
    });
});
