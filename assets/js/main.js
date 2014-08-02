"use strict";

$(document).ready(function() {
    $('#lang-menu > a').click(function() {
        $('ul.menu-body').slideToggle('fast'); // language menu
    });
});
