<?php
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

// time chart
function graph_data_to_json($x_axis_values, $y_axis_values, $labels, $graph_id)
{
    // $x_axis_values array of arrays
    // $y_axis_values array of arrays
    $count_labels = count($labels);

    // validate
    if (!$count_labels || !$graph_id)
    {
        throw new Exception('No data given.');
    }

    // init
    $count_x_axis_values = count($x_axis_values);
    $count_y_axis_values = count($y_axis_values);
    $json_array = [
        "cols" => [],
        "rows" => []
    ];

    if ($count_x_axis_values !== $count_labels && $count_y_axis_values !== $count_labels)
    {
        throw new Exception('Invalid data set for time graph provided.');
    }

    // x axis aka time
    foreach ($x_axis_values as $value)
    {
        if (!is_array($value))
        {
            throw new Exception('Non-array provided in time-axis.');
        }
    }
    // y axis
    foreach ($y_axis_values as $value)
    {
        if (!is_array($value))
        {
            throw new Exception('Non-array provided in y-axis.');
        }
    }

    // Handle caching, define paths
    $local_cache_file = CACHE_PATH . 'cache_graph_' . $graph_id . '.json';
    $remote_cache_file = CACHE_LOCATION . 'cache_graph_' . $graph_id . '.json';
    //    if (file_exists($local_cache_file))
    //    {
    //        $modified_time = filemtime($local_cache_file);
    //        $current_time = time();
    //
    //        // file is one day old
    //        // calculate if we need to refresh, by the modified time
    //        if (($modified_time + Util::SECONDS_IN_A_DAY) < $current_time)
    //        {
    //            // Refresh plot
    //            unlink($local_cache_file);
    //        }
    //        else
    //        {
    //            return $remote_cache_file;
    //        }
    //    }

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

    // Generate the line labels aka columns
    $json_array['cols'][] = [
        'id'      => '',
        'label'   => 'Date',
        'pattern' => '',
        'type'    => 'date'
    ];
    for ($i = 0; $i < $count_labels; $i++)
    {
        // do not include the small lines into the labels
        if (!in_array($i, $small_lines))
        {
            $json_array['cols'][] = ['id' => '', 'label' => $labels[$i], 'pattern' => '', 'type' => 'number'];
        }
    }
    // we have small lines
    if ($count_small_lines)
    {
        $json_array['cols'][] = ['id' => '', 'label' => 'Other', 'pattern' => '', 'type' => 'number'];
    }

    // Get all x-values used in data set, aka time
    $all_x_values = [];
    for ($i = 0; $i < $count_x_axis_values; $i++)
    {
        $all_x_values = array_merge_recursive($all_x_values, $x_axis_values[$i]);
    }
    $all_x_values = array_values(array_unique($all_x_values, SORT_NUMERIC));
    asort($all_x_values);
    var_dump($all_x_values);

    // Iterate through each possible x-value
    $count_all_x_values = count($all_x_values);
    for ($i = 0; $i < $count_all_x_values; $i++)
    {
        $other_count = 0;
        $row = [];
        $date_str = 'new Date(' . ($all_x_values[$i] * 1000) . ')';
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
                if ($xval[$k] == $all_x_values[$i])
                {
                    $found = true;

                    // check if part of "other"
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
        if ($count_small_lines !== 0)
        {
            $expected_cols++;
        }

        if ($expected_cols !== count($row))
        {
            echo '<pre>';
            print_r($row);
            print_r($labels);
            print_r($small_lines);
            echo '</pre>';
            throw new Exception("Expected to get $expected_cols columns, got $found_cols!");
        }

        $json_array['rows'][] = ['c' => $row];
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
