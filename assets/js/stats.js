/**
 * copyright 2014-2015 Daniel Butum <danibutum at gmail dot com>
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
    var $main_stats = $("#stats-main"), // top container
        cache_json = {}; // cache json data responses
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
        series     : {
            lines : { show: true },
            points: { show: false }
        },
        xaxis      : {
            show      : true,
            mode      : "time",
            timeformat: "%d %b"
        },
        yaxis      : {
            show: true
        },
        legend     : {
            margin: [-70, 0],
            sorted: null
        },
        grid       : {
            hoverable: true,
            clickable: true
        },
        tooltip    : true,
        tooltipOpts: {
            defaultTheme: false,
            content     : "<strong>%x</strong><br> %s: <strong>%y</strong>",
            xDateFormat : "%e %b, %Y"
        }
    };

    // sort all tables
    $(".table-sort").DataTable();

    // all pie charts
    $(".stats-pie-chart").each(function() {
        var $this = $(this);

        var json_file = $this.data("json");
        $.get(json_file, function(jsonData) {
            $.plot($this, jsonData, pie_options);
        });
    });

    // all time charts
    $(".stats-time-chart, .stats-time-chart-wide").each(function() {
        var $this = $(this);

        var json_file = $this.data("json");
        $.get(json_file, function(jsonData) {
            cache_json[json_file] = jsonData;
            $.plot($this, jsonData, time_options);
        });
    });

    // time limit filter selected
    $main_stats.on("click", ".stats-buttons label", function() {
        var $this = $(this),
            $stats = $this.closest(".panel").find(".stats-time-chart, .stats-time-chart-wide"),
            json_file = $stats.data("json"),
            date = $this.data("date");

        var time_limit;
        if (date === "1-year") {
            time_limit = MSECONDS_YEAR;
        } else if (date === "6-months") {
            time_limit = MSECONDS_MONTH * 6;
        } else if (date === "1-month") {
            time_limit = MSECONDS_MONTH;
        } else {
            console.error("date is not valid:", date);
            return;
        }

        var new_json_data = [],
            json_data = cache_json[json_file]; // take from cache, should always be there

        // build new data
        for (var i = 0; i < json_data.length; i++) {
            var label = json_data[i]["label"],
                data = json_data[i]["data"],
                new_data = [],
                found = false;

            // find the data that is in time interval
            for (var j = 0; j < data.length; j++) {
                var timestamp = data[j][0];

                if (isInTimeInterval(timestamp, time_limit)) {
                    new_data.push(data[j]);
                    found = true;
                }
            }

            if (found) {
                new_json_data.push({"label": label, "data": new_data})
            }
        }

        $.plot($stats, new_json_data, time_options);
    })

})(jQuery);
