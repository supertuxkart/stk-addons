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

$(document).ready(function() {
    "use strict";

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
