<?php
error_reporting(E_ALL ^ E_STRICT);
if (!defined('ROOT'))
{
    define('ROOT', '../');
}
require_once(ROOT . 'config.php');

function graph_data_to_json($values, $labels, $format, $graph_id)
{
    if ($format !== 'time' && count($values) !== count($labels))
    {
        throw new Exception('Invalid data set provided.');
    }
    else if ($format == 'time' &&
        count($values[0]) !== count($labels) &&
        count($values[1]) !== count($labels)
    )
    {
        throw new Exception('Invalid data set for itme graph provided.');
    }
    if (count($labels) == 0)
    {
        throw new Exception('No data given.');
    }
    if ($format !== 'time')
    {
        foreach ($values AS $test_data)
        {
            if (!is_numeric($test_data))
            {
                throw new Exception('Non-numeric data provided.');
            }
        }
    }
    else
    {
        foreach ($values[0] AS $test_data)
        {
            if (!is_array($test_data))
            {
                throw new Exception('Non-array provided in time-axis.');
            }
        }
        foreach ($values[1] AS $test_data)
        {
            if (!is_array($test_data))
            {
                throw new Exception('Non-array provided in y-axis.');
            }
        }
    }

    // Handle caching
    if ($graph_id !== null)
    {
        $local_cache_file = CACHE_PATH . 'cache_graph_' . $graph_id . '.json';
        $remote_cache_file = CACHE_LOCATION . 'cache_graph_' . $graph_id . '.json';
        if (file_exists($local_cache_file))
        {
            $mtime = filemtime($local_cache_file);
            $time = time();
            if (($mtime + (60 * 60 * 24)) < $time)
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
    else
    {
        $rand = rand(10000, 99999);
        $local_cache_file = CACHE_PATH . 'cache_graph_' . $rand . '.json';
        $remote_cache_file = CACHE_LOCATION . 'cache_graph_' . $rand . '.json';
    }


    $json_array = array();
    if ($format == 'pie')
    {
        $json_array['cols'] = array(
            array('id' => '', 'label' => 'Name', 'pattern' => '', 'type' => 'string'),
            array('id' => '', 'label' => 'Value', 'pattern' => '', 'type' => 'number')
        );
        $json_array['rows'] = array();
        for ($i = 0; $i < count($values); $i++)
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
    else if ($format == 'time')
    {
        $xvalues = $values[0];
        $yvalues = $values[1];
        // Get the tallest line and get relatively small lines
        $max_value = 0;
        for ($i = 0; $i < count($yvalues); $i++)
        {
            if (max($yvalues[$i]) > $max_value)
            {
                $max_value = max($yvalues[$i]);
            }
        }
        $small_lines = array();
        for ($i = 0; $i < count($yvalues); $i++)
        {
            if (max($yvalues[$i]) < 0.02 * $max_value)
            {
                $small_lines[] = $i;
            }
        }

        // Generate the line labels
        $json_array['cols'] = array();
        $json_array['cols'][] = array('id' => '', 'label' => 'Date', 'pattern' => '', 'type' => 'date');
        for ($i = 0; $i < count($labels); $i++)
        {
            if (!in_array($i, $small_lines))
            {
                $json_array['cols'][] = array('id' => '', 'label' => $labels[$i], 'pattern' => '', 'type' => 'number');
            }
        }
        if (count($small_lines) != 0)
        {
            $json_array['cols'][] = array('id' => '', 'label' => 'Other', 'pattern' => '', 'type' => 'number');
        }

        // Get all x-values used in data set
        $allxvalues = array();
        for ($i = 0; $i < count($xvalues); $i++)
        {
            $allxvalues = array_merge_recursive($allxvalues, $xvalues[$i]);
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
            for ($j = 0; $j < count($labels); $j++)
            {
                $xval = $xvalues[$j];
                $yval = $yvalues[$j];

                $found = false;
                for ($k = 0; $k < count($xval); $k++)
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
            if (count($small_lines) != 0)
            {
                $row[] = array('v' => $other_count, 'f' => null);
            }

            // Sanity check
            $expected_cols = count($labels) - count($small_lines) + 1;
            if (count($small_lines) != 0)
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
    else
    {
        throw new Exception('Unsupported format!');
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
