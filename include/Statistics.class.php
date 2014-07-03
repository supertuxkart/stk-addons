<?php

/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *                2014 Daniel Butum <danibutum at gmail dot com>
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
class Statistic
{
    // fake chart enumeration
    const CHART_PIE = 1;

    const CHART_TIME = 2;

    /**
     * @return array
     */
    public static function getAllowedChartTypes()
    {
        return array(static::CHART_PIE, static::CHART_TIME);
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

        $tpl = StkTemplate::get("stats-section.tpl");
        $tplData = array(
            "title"       => $section_title,
            "data"        => array(),
            "columns"     => array(),
            "description" => $description
        );

        // execute query
        try
        {
            $data = DBConnection::get()->query($select_query, DBConnection::FETCH_ALL);
        }
        catch(DBException $e)
        {
            throw new StatisticException(_h("Tried to create a section for statistics"));
        }

        if ($data) // not empty
        {
            $tplData["columns"] = array_keys($data[0]);
            $tplData["data"] = $data;
        }

        $tpl->assign("section", $tplData);

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
     * @param array  $data
     * @param string $graph_id
     *
     * @throws StatisticException
     * @return string the file
     */
    protected static function getPieJSON(array $data, $graph_id)
    {
        $cache_path = static::getCachePath($graph_id);
        $cache_location = static::getCacheLocation($graph_id);
        $columns = array_keys($data[0]);
        $count_columns = count($columns);

        // TODO add caching

        // we must have 2 columns because we have, label and data in the json call
        if ($count_columns !== 2)
        {
            throw new StatisticException(_h("The data is invalid the columns count should be 2"));
        }

        // retrieve data
        $label_index = $columns[0];
        $data_index = $columns[1];

        // build json
        $json = array();

        foreach ($data as $row)
        {
            $json[] = array("label" => (string)$row[$label_index], "data" => (int)$row[$data_index]);
        }

        // write json to file
        $status = file_put_contents($cache_path, json_encode($json));
        if ($status === false)
        {
            throw new StatisticException(_h("Failed to open json file for writing!"));
        }

        return $cache_location;
    }

    /**
     * @param array  $data
     * @param string $graph_id
     *
     * @return null
     */
    protected static function getTimeJSON(array $data, $graph_id)
    {
        return null;
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

        // query database
        try
        {
            $data = DBConnection::get()->query($select_query, DBConnection::FETCH_ALL);
        }
        catch(DBException $e)
        {
            throw new StatisticException(_h("Tried to build a chart"));
        }

        $tpl = StkTemplate::get("stats-chart.tpl");
        $tplData = array(
            "title" => $chart_title,
            "class" => "",
            "json"  => ""
        );

        switch ($chart_type)
        {
            case static::CHART_PIE:
                $tplData["class"] = "stats-pie-chart";
                $tplData["json"] = static::getPieJSON($data, $graph_id);
                break;

            case static::CHART_TIME;
                $tplData["class"] = "stats-time-chart";
                $tplData["json"] = static::getTimeJSON($data, $graph_id);
                break;

            default:
                break;
        }

        $tpl->assign("chart", $tplData);

        return $tpl->toString();
    }

    /**
     * Return the most downloaded addon of a given type
     *
     * @param string $addonType the type of addon eg: kart, track, arena etc
     * @param string $fileType
     *
     * @return null|string the id of the addon or null on empty selection
     * @throws StatisticException
     */
    public static function mostDownloadedAddon($addonType, $fileType = 'addon')
    {
        if (!Addon::isAllowedType($addonType))
        {
            throw new StatisticException(_h('Invalid addon type.'));
        }

        try
        {
            $download_counts = DBConnection::get()->query(
                'SELECT `addon_id`, SUM(`downloads`) AS `count`
                FROM `' . DB_PREFIX . 'files`
                WHERE `addon_type` = :addon_type
                AND `file_type` = :file_type
                GROUP BY `addon_id`
                ORDER BY SUM(`downloads`) DESC',
                DBConnection::FETCH_FIRST,
                array(
                    ':addon_type' => $addonType,
                    ':file_type'  => $fileType
                )
            );
        }
        catch(DBException $e)
        {
            throw new StatisticException(h(
                _('An error occurred while performing your statistic query') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        if (empty($download_counts))
        {
            return null;
        }

        return $download_counts['addon_id'];
    }

    /**
     * Return the newest addon of a given type
     *
     * @param string $addonType
     *
     * @return null|string the id of the addon or null on empty selection
     * @throws StatisticException
     */
    public static function newestAddon($addonType)
    {
        if (!Addon::isAllowedType($addonType))
        {
            throw new StatisticException(_h('Invalid addon type.'));
        }

        try
        {
            $newest_addon = DBConnection::get()->query(
                'SELECT `a`.`id`
                FROM `' . DB_PREFIX . 'addons` `a`
                LEFT JOIN `' . DB_PREFIX . $addonType . '_revs` `r`
                ON `a`.`id` = `r`.`addon_id`
                WHERE `r`.`status` & ' . F_APPROVED . '
                ORDER BY `a`.`creation_date` DESC
                LIMIT 1',
                DBConnection::FETCH_FIRST
            );
        }
        catch(DBException $e)
        {
            throw new StatisticException(h(
                _('An error occurred while performing your statistic query') . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        if (empty($newest_addon))
        {
            return null;
        }

        return $newest_addon['id'];
    }
}
