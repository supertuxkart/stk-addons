<?php
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

function graph_data_to_json($values, $labels, $format, $graph_id)
{
    // for time chart
    $count_x_axis_values = $count_y_axis_values = 0;
    $x_axis_values = $y_axis_values = array();

    // for pie chart
    $count_values = 0;

    // common
    $count_labels = count($labels);

    // validate
    if (!$count_labels)
    {
        throw new Exception('No data given.');
    }

    if ($format === "time") // time chart
    {
        // init
        $x_axis_values = $values[0];
        $y_axis_values = $values[1];
        $count_x_axis_values = count($x_axis_values);
        $count_y_axis_values = count($y_axis_values);

        // 0 index is x axis (time axis) and 1 index is y axis
        if ($count_x_axis_values !== $count_labels && $count_y_axis_values !== $count_labels)
        {
            throw new Exception('Invalid data set for time graph provided.');
        }

        foreach ($x_axis_values as $value) // x axis aka time
        {
            if (!is_array($value))
            {
                throw new Exception('Non-array provided in time-axis.');
            }
        }
        foreach ($y_axis_values as $value) // y axis
        {
            if (!is_array($value))
            {
                throw new Exception('Non-array provided in y-axis.');
            }
        }
    }
    elseif($format === "pie") // pie chart
    {
        // init
        $count_values = count($values);

        // labels must be the same as values
        if ($count_values !== $count_labels)
        {
            throw new Exception('Invalid data set provided. The number of labels is different than the number of values');
        }

        foreach ($values as $value)
        {
            if (!is_numeric($value))
            {
                throw new Exception('Non-numeric data provided.');
            }
        }
    }
    else
    {
        throw new Exception(sprintf("Format %s is not recognized", $format));
    }

    // Handle caching, define paths
    if ($graph_id)
    {
        $local_cache_file = CACHE_PATH . 'cache_graph_' . $graph_id . '.json';
        $remote_cache_file = CACHE_LOCATION . 'cache_graph_' . $graph_id . '.json';
        if (file_exists($local_cache_file))
        {
            $modified_time = filemtime($local_cache_file);
            $current_time = time();

            // file is one day old
            // calculate if we need to refresh, by the modified time
            if (($modified_time + Util::SECONDS_IN_A_DAY) < $current_time)
            {
                // Refresh plot
                unlink($local_cache_file);
            }
            else
            {
                return $remote_cache_file;
            }
        }
    }
    else // generate new file
    {
        $rand = rand(10000, 99999);
        $local_cache_file = CACHE_PATH . 'cache_graph_' . $rand . '.json';
        $remote_cache_file = CACHE_LOCATION . 'cache_graph_' . $rand . '.json';
    }

    $json_array = array();
    if ($format === 'pie') // pie chart
    {
        $json_array['cols'] = array(
            array('id' => '', 'label' => 'Name', 'pattern' => '', 'type' => 'string'),
            array('id' => '', 'label' => 'Value', 'pattern' => '', 'type' => 'number')
        );
        $json_array['rows'] = array();

        for ($i = 0; $i < $count_values; $i++)
        {
            $json_array['rows'][] = array(
                'c' => array(
                    array(
                        'v' => $labels[$i],
                        'f' => null
                    ),
                    array(
                        'v' => (double)$values[$i],
                        'f' => null
                    )
                )
            );
        }
    }
    else if ($format === 'time') // time chart
    {
        // Get the tallest line and get relatively small lines
        $max_value = 0;
        for ($i = 0; $i < $count_y_axis_values; $i++)
        {
            if (max($y_axis_values[$i]) > $max_value)
            {
                $max_value = max($y_axis_values[$i]);
            }
        }
        $small_lines = array();
        for ($i = 0; $i < $count_y_axis_values; $i++)
        {
            if (max($y_axis_values[$i]) < 0.02 * $max_value)
            {
                $small_lines[] = $i;
            }
        }
        $count_small_lines = count($small_lines);

        // Generate the line labels
        $json_array['cols'] = array();
        $json_array['cols'][] = array('id' => '', 'label' => 'Date', 'pattern' => '', 'type' => 'date');
        for ($i = 0; $i < $count_labels; $i++)
        {
            if (!in_array($i, $small_lines))
            {
                $json_array['cols'][] = array('id' => '', 'label' => $labels[$i], 'pattern' => '', 'type' => 'number');
            }
        }
        if ($count_small_lines !== 0)
        {
            $json_array['cols'][] = array('id' => '', 'label' => 'Other', 'pattern' => '', 'type' => 'number');
        }

        // Get all x-values used in data set
        $allxvalues = array();
        for ($i = 0; $i < $count_x_axis_values; $i++)
        {
            $allxvalues = array_merge_recursive($allxvalues, $x_axis_values[$i]);
        }
        $allxvalues = array_values(array_unique($allxvalues, SORT_NUMERIC));
        asort($allxvalues);

        $json_array['rows'] = array();

        // Iterate through each possible x-value
        for ($i = 0; $i < count($allxvalues); $i++)
        {
            $other_count = 0;
            $row = array();
            $date_str = 'new Date(' . ($allxvalues[$i] * 1000) . ')';
            $row[] = array('v' => $date_str, 'f' => null);

            // Insert data for each line
            for ($j = 0; $j < $count_labels; $j++)
            {
                $xval = $x_axis_values[$j];
                $yval = $y_axis_values[$j];

                $found = false;
                $count_xval = count($xval);
                for ($k = 0; $k < $count_xval; $k++)
                {
                    if ($xval[$k] == $allxvalues[$i])
                    {
                        $found = true;
                        if (!in_array($j, $small_lines))
                        {
                            $row[] = array('v' => $yval[$k], 'f' => null);
                        }
                        else
                        {
                            // Is part of "other" line
                            $other_count = $other_count + $yval[$k];
                        }
                        break;
                    }
                }
                if (!$found && !in_array($j, $small_lines))
                {
                    $row[] = array('v' => 0, 'f' => null);
                }
            }

            // Insert data for "other" line
            if ($count_small_lines !== 0)
            {
                $row[] = array('v' => $other_count, 'f' => null);
            }

            // Sanity check
            $expected_cols = $count_labels - $count_small_lines + 1;
            if ($count_small_lines != 0)
            {
                $expected_cols++;
            }

            $found_cols = count($row);
            if ($expected_cols != $found_cols)
            {
                echo '<pre>';
                print_r($row);
                print_r($labels);
                print_r($small_lines);
                echo '</pre>';
                throw new Exception("Expected to get $expected_cols columns, got $found_cols!");
            }

            $json_array['rows'][] = array('c' => $row);
        }
    }

    // Encode values to json
    $json_string = json_encode($json_array);
    $json_string = $graph_id . '(' . $json_string . ')';
    $json_string = preg_replace('/"(new Date\([0-9,]+\))"/', '$1', $json_string);

    // Write json to file
    $handle = fopen($local_cache_file, 'w');
    if (!$handle)
    {
        throw new Exception('Failed to open json file for writing!');
    }
    fwrite($handle, $json_string);
    fclose($handle);

    return $remote_cache_file;
}
