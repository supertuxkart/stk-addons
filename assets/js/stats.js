/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
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

(function(window, document) {
    "use strict";

    // load essential elements
    var $main_stats = $("#stats-main"); // top container

    // sort all tables
    $(".table-sort").DataTable();

    $.plot("#stat-files", [
        {"label": "Karts", data: [1843487]},
        {"label": "Tracks", data: [1808441]},
        {"label": "Karts", data: [441016]}
    ], {
        series: {
            pie: {
                show: true,
                radius: 1,
                label: {
                    show: true,
                    radius: 0.75,
//                    formatter: function(label, series) {
//                        console.log(series);
//                        return label + "\n" + series.data[0][1];
//                    },
                    background: {
                        opacity: 0.6,
                        color: '#000'
                    }
                }
            }
        },
        grid: {
            hoverable: true,
            clickable: true
        }
    });

})(window, document);
