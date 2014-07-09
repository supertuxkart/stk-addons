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

(function($) {
    "use strict";

    // load essential elements
    var $main_stats = $("#stats-main"); // top container
    var pie_options = {
        series: {
            pie: {
                show  : true,
                radius: 1,
                label : {
                    show      : true,
                    radius    : 0.75,
                    background: {
                        opacity: 0.6,
                        color  : '#000'
                    }
                }
            }
        },
        grid  : {
            hoverable: true,
            clickable: true
        }
    };
    var time_options = {
        series: {
            lines: { show: true },
            points: { show: false }
        },
        xaxis: {
            show: true,
            mode: "time",
            timeformat: "%b %y"
        },
        yaxis: {
            show: true
        },
        legend: {
            margin: [-70, 0]
        },

        grid: {
            hoverable: true,
            clickable: true
        }
    };

    // sort all tables
    $(".table-sort").DataTable();

    // all pie charts
    $(".stats-pie-chart").each(function() {
        var $this = $(this);

        var json_file = $this.data("json");
        $.get(json_file, function(jsonData) {
            console.log(jsonData);
            $.plot($this, jsonData, pie_options);
        });
    });

    // all time charts
    $(".stats-time-chart, .stats-time-chart-wide").each(function() {
        var $this = $(this);

        var json_file = $this.data("json");
        $.get(json_file, function(jsonData) {
            console.log(jsonData);
            $.plot($this, jsonData, time_options);
        });
    });
})(jQuery);
