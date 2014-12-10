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

    /**
     * Tests if there is SVG support. Maybe move it to util.js if we need it?
     *
     * @return {bool}
     */
    function isSVGSupported() {
        return !!document.createElementNS && !!document.createElementNS('http://www.w3.org/2000/svg', 'svg').createSVGRect;
    }

    // mark link as active in the top nav
    var page_parts = window.location.href.split("?"),
        current_page = page_parts[0],
        current_query = page_parts[1],
        $link = $(".navbar-nav a[href^='" + current_page + "']"),
        $closest_ul = $link.first().closest("ul"),
        has_dropdown = $closest_ul.hasClass("dropdown-menu");

    if ($link.length === 1 && !has_dropdown) { // top link
        $link.parent().addClass("active");
    } else { // multiple top links

        if (has_dropdown) { // dropdown
            $closest_ul.parent().addClass("active");

            $link.each(function() { // mark in dropdown
                if (current_query === this.href.split("?")[1]) {
                    $(this).parent().addClass("active");
                }
            });
        } else { // normal link, select first
            $link.first().parent().addClass("active");
        }
    }

    // language menu
    $('#lang-menu > a').click(function() {
        $('ul.menu-body').slideToggle('fast'); // language menu
    });

    // auto validation
    if ($.fn.bootstrapValidator) {
        $('.auto-validation').bootstrapValidator();
    }

    // svg with png fallback
    if (!isSVGSupported()) {
        $('img[src*="svg"]').attr('src', function() {
            return $(this).attr('src').replace('.svg', '.png');
        });
    }
});
