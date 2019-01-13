<?php
/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class Statistic
 */
class Statistic
{
    // fake chart enumeration
    /**
     * @var int
     */
    const CHART_PIE = 1;

    /**
     * @var int
     */
    const CHART_TIME = 2;

    /**
     * @return array
     */
    public static function getAllowedChartTypes()
    {
        return [static::CHART_PIE, static::CHART_TIME];
    }

    /**
     * Get the selection html, with the table, title and description
     *
     * @param string $select_query
     * @param string $section_title
     * @param string $description
     *
     * @return string
     * @throws StatisticException
     */
    public static function getSection($select_query, $section_title = "", $description = "")
    {
        if (!$select_query)
        {
            throw new StatisticException(_h("The query is empty"));
        }

        $tpl = StkTemplate::get("stats/section.tpl");
        $tpl_data = [
            "title"       => $section_title,
            "data"        => [],
            "columns"     => [],
            "description" => $description
        ];

        // execute query
        try
        {
            $data = DBConnection::get()->query($select_query, DBConnection::FETCH_ALL);
        }
        catch (DBException $e)
        {
            Debug::addException($e);
            throw new StatisticException(exception_message_db(_("create a section for statistics")));
        }

        if ($data) // not empty
        {
            $tpl_data["columns"] = array_keys($data[0]);
            $tpl_data["data"] = $data;
        }

        $tpl->assign("section", $tpl_data);

        return $tpl->toString();
    }

    /**
     * Get the path of a cache file
     *
     * @param string $graph_id
     *
     * @return string
     */
    public static function getCachePath($graph_id)
    {
        return CACHE_PATH . "cache_graph_v2_" . $graph_id . ".json";
    }

    /**
     * Get the url location of a cache file
     *
     * @param string $graph_id
     *
     * @return string
     */
    public static function getCacheLocation($graph_id)
    {
        return CACHE_LOCATION . "cache_graph_v2_" . $graph_id . ".json";
    }

    /**
     * @param array $data
     * @param array $columns
     * @param int   $count_columns
     *
     * @throws StatisticException
     * @return array
     */
    protected static function getPieJSON(array $data, array $columns, $count_columns)
    {
        // we must have 2 columns because we have, label and data in the json call
        if ($count_columns !== 2)
        {
            throw new StatisticException(_h("The data is invalid the columns count should be 2"));
        }

        // retrieve data
        $label_index = $columns[0];
        $data_index = $columns[1];

        // build json
        $json = [];

        foreach ($data as $row)
        {
            $json[] = ["label" => (string)$row[$label_index], "data" => (int)$row[$data_index]];
        }

        return $json;
    }

    /**
     * @param array $data
     * @param array $columns
     * @param int   $count_columns
     *
     * @throws StatisticException
     * @return array
     */
    protected static function getTimeJSON(array $data, array $columns, $count_columns)
    {
        // we must have 3 columns, label, x axis column(time), y axis column
        if ($count_columns !== 3)
        {
            throw new StatisticException(_h("The data is invalid the columns count should be 3"));
        }

        // retrieve data
        $label_index = $columns[0];
        $x_index = $columns[1];
        $y_index = $columns[2];

        // build json
        $max_y_lines = [];
        $small_lines = []; // array of labels of each small line

        // calculate maximum
        foreach ($data as $point)
        {
            $label = $point[$label_index];
            $y = (int)$point[$y_index];

            // create first time
            if (!isset($max_y_lines[$label]))
            {
                $max_y_lines[$label] = 0;
            }

            // get maximum on each line
            $max_y_lines[$label] = max($max_y_lines[$label], $y);
        }

        // get maximum of y in all lines
        $max_y_value = max(array_values($max_y_lines));

        // identify small lines
        foreach ($max_y_lines as $label => $max_y_line)
        {
            // a small line has less than 2% of the max general value
            if ($max_y_line < 0.02 * $max_y_value)
            {
                $small_lines[] = $label;
            }
        }

        $json = [];
        $other_line = [
            "label" => "Other",
            "data"  => []
        ];
        $other_data = []; // map from x value to y value

        // group by points
        foreach ($data as $point)
        {
            $label = $point[$label_index];

            // javascript uses milliseconds for unix timestamp instead of seconds(reason for 1000)
            $x = strtotime($point[$x_index]) * 1000;
            $y = (int)$point[$y_index];

            // check if in "other"
            if (in_array($label, $small_lines))
            {
                // create x value for the first time
                if (!isset($other_data[$x]))
                {
                    $other_data[$x] = [$x, $y];
                }
                else // aggregate data, aka add all the y values for all the small lines
                {
                    $other_x = $other_data[$x][0];
                    $other_y = $other_data[$x][1];
                    $other_data[$x] = [$other_x, $other_y + $y];
                }
            }
            else // regular category
            {
                // create first time
                if (!isset($json[$label]))
                {
                    $json[$label] = [
                        "label" => $label,
                        "data"  => []
                    ];
                }

                $json[$label]["data"][] = [$x, $y];
            }
        }

        // degroup
        $json = array_values($json);

        // add other line
        if ($other_data) // not empty
        {
            $other_line["data"] = array_values($other_data);
            $json[] = $other_line;
        }

        return $json;
    }

    /**
     * Get the plot html. If the chart type is PIE then the query must value 2 columns.
     * One the label and one the value.
     *
     * @param string $select_query
     * @param int    $chart_type
     * @param string $chart_title
     * @param string $graph_id
     *
     * @return string
     * @throws StatisticException
     */
    public static function getChart($select_query, $chart_type, $chart_title, $graph_id)
    {
        if (!in_array($chart_type, static::getAllowedChartTypes()))
        {
            throw new StatisticException(_h("The chart type is invalid"));
        }

        // init
        $tpl = StkTemplate::get("stats/chart.tpl");
        $tpl_data = [
            "title"        => $chart_title,
            "class"        => ($chart_type === static::CHART_PIE) ? "stats-pie-chart" : "stats-time-chart-wide",
            "json"         => static::getCacheLocation($graph_id),
            "show_buttons" => ($chart_type === static::CHART_TIME) ? true : false
        ];
        $cache_path = static::getCachePath($graph_id);

        // cache file
        $is_cached = false;
        if (FileSystem::exists($cache_path))
        {
            $modified_time = FileSystem::fileModificationTime($cache_path, false);
            if (!Util::isOldEnough($modified_time, Util::SECONDS_IN_A_DAY)) // cache is not old
            {
                $is_cached = true;
            }
        }

        if (!$is_cached)
        {
            // query database
            try
            {
                $data = DBConnection::get()->query($select_query, DBConnection::FETCH_ALL);
            }
            catch (DBException $e)
            {
                throw new StatisticException(exception_message_db(_("select the data, to build a chart")));
            }

            if (!$data)
            {
                return "<div class='alert alert-info'>No data available for chart</div>";
            }

            $columns = array_keys($data[0]);
            $count_columns = count($columns);

            // check chart type
            switch ($chart_type)
            {
                case static::CHART_PIE:
                    $data = static::getPieJSON($data, $columns, $count_columns);
                    break;

                case static::CHART_TIME:
                    $data = static::getTimeJSON($data, $columns, $count_columns);
                    break;

                default:
                    break;
            }

            // write json to file
            $status = file_put_contents($cache_path, json_encode($data));
            if ($status === false)
            {
                throw new StatisticException(_h("Failed to open json file for writing!"));
            }
        }

        $tpl->assign("chart", $tpl_data);

        return $tpl->toString();
    }

    /**
     * Return the most downloaded addon of a given type
     *
     * @param int $addon_type the type of addon
     * @param int $file_type
     *
     * @return null|string the name of the addon or null on empty selection
     * @throws StatisticException
     */
    public static function mostDownloadedAddon($addon_type, $file_type = File::ADDON)
    {
        if (!Addon::isAllowedType($addon_type))
        {
            throw new StatisticException(_h('Invalid addon type.'));
        }

        try
        {
            $download_counts = DBConnection::get()->query(
                'SELECT A.name as addon_name
                FROM `{DB_VERSION}_files` F
                INNER JOIN `{DB_VERSION}_addons` A
                    ON F.`addon_id` =  A.`id`
                WHERE A.`type` = :addon_type
                AND F.`type` = :file_type
                GROUP BY `addon_id`
                ORDER BY SUM(`downloads`) DESC',
                DBConnection::FETCH_FIRST,
                [
                    ':addon_type' => $addon_type,
                    ':file_type'  => $file_type
                ]
            );
        }
        catch (DBException $e)
        {
            throw new StatisticException(exception_message_db(_('get the most downloaded addons')));
        }

        if (!$download_counts)
        {
            return null;
        }

        return $download_counts['addon_name'];
    }

    /**
     * Return the newest addon of the specified type
     *
     *  @param int $addon_type
     *
     * @return string addon name
     * @throws StatisticException
     */
    public static function getNewestAddonOfType(int $addon_type): string
    {
        if (!Addon::isAllowedType($addon_type))
        {
            throw new StatisticException(sprintf("Addon of type = %d is not allowed", $addon_type));
        }

        try
        {
            $data = DBConnection::get()->query(
                "SELECT A.name AS name
                FROM `{DB_VERSION}_addons` A
                LEFT JOIN `{DB_VERSION}_addon_revisions` R
                    ON A.id = R.addon_id
                WHERE R.status & " . F_APPROVED . " AND A.type = :addon_type
                ORDER BY A.creation_date DESC 
                LIMIT 1",
                DBConnection::FETCH_FIRST,
                [':addon_type' => $addon_type],
                [':addon_type' => DBConnection::PARAM_INT]
            );

            return $data['name'];
        }
        catch (DBException $e)
        {
            throw new StatisticException(exception_message_db(_('get the newest addon')));
        }
    }

    /**
     * Return the newest addon of all types
     *
     * @return array with the key the addon type and the value is addon name
     * @throws StatisticException
     */
    public static function newestAddons()
    {
        // TODO this is very inefficient, instead of doing 3 queries do just one
        // but this is called so rarely it does not even matter
        $return = [
            Addon::TRACK => static::getNewestAddonOfType(Addon::TRACK),
            Addon::ARENA => static::getNewestAddonOfType(Addon::ARENA),
            Addon::KART  => static::getNewestAddonOfType(Addon::KART),
        ];

        return $return;
    }

    /**
     * The number of online users
     *
     * @return int
     * @throws StatisticException
     */
    public static function onlineClientUsers()
    {
        try
        {
            $count = DBConnection::get()->count("client_sessions", '`is_online` = 1');
        }
        catch (DBException $e)
        {
            throw new StatisticException(exception_message_db(_('count the number of online users')));
        }

        return $count;
    }
}
