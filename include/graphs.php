<?php
error_reporting(E_ALL ^ E_STRICT);
if (!defined('ROOT')) define('ROOT','../');
require_once(ROOT.'config.php');
require_once(JPG_ROOT.'jpgraph/jpgraph.php');

function graph_data_to_json($values, $labels, $format, $graph_id) {
    if (count($values) !== count($labels))
        throw new Exception('Invalid data set provided.');
    if (count($values) == 0)
        throw new Exception('No data given.');
    foreach($values AS $test_data) {
        if (!is_numeric($test_data)) {
            throw new Exception('Non-numeric data provided.');
        }
    }
    
    // Handle caching
    if ($graph_id !== NULL) {
        $local_cache_file = CACHE_DIR.'cache_graph_'.$graph_id.'.json';
        $remote_cache_file = CACHE_DL.'cache_graph_'.$graph_id.'.json';
        if (file_exists($local_cache_file)) {
            $mtime = filemtime($local_cache_file);
            $time = time();
            if (($mtime + (60*60*24)) < $time) {
                // Refresh plot
                unlink($local_cache_file);
            }
            else return $remote_cache_file;
        }
    } else {
        $rand = rand(10000,99999);
        $local_cache_file = CACHE_DIR.'cache_graph_'.$rand.'.json';
        $remote_cache_file = CACHE_DL.'cache_graph_'.$rand.'.json';
    }
    
    
    $json_array = array();
    if ($format == 'pie') {
	$json_array['cols'] = array(
	    array('id' => '', 'label' => 'Name', 'pattern' => '', 'type' => 'string'),
	    array('id' => '', 'label' => 'Value', 'pattern' => '', 'type' => 'number')
	);
	$json_array['rows'] = array();
	for ($i = 0; $i < count($values); $i++) {
	    $json_array['rows'][] = array(
		'c' => array(
			array(
			    'v' => $labels[$i],
			    'f' => null
			),
			array(
			    'v' => (double) $values[$i],
			    'f' => null
			)
		    )
		);
	}
    } else {
	throw new Exception('Unsupported format!');
    }
    
    // Encode values to json
    $json_string = json_encode($json_array);
    $json_string = 'data('.$json_string.')';
    // Write json to file
    $handle = fopen($local_cache_file, 'w');
    if (!$handle)
	throw new Exception('Failed to open json file for writing!');
    fwrite($handle, $json_string);
    fclose($handle);
    return $remote_cache_file;
}


/**
 * Create a graph with dates along the x-axis
 * @param string $title
 * @param array $xvalues
 * @param array $yvalues
 * @param array $labels
 * @param string $graph_id
 * @param integer $xsize
 * @param integer $ysize
 * @return string 
 */
function graph_date_line($title, $xvalues, $yvalues, $labels, $graph_id = NULL, $xsize = 800, $ysize = 600) {
    require_once(JPG_ROOT.'jpgraph/jpgraph_line.php');
    require_once(JPG_ROOT.'jpgraph/jpgraph_utils.inc.php');
    require_once(JPG_ROOT.'jpgraph/jpgraph_text.inc.php');
    
    // List of line styles to use
    $line_styles = array('dashed','solid');

    if (!is_int($xsize) || !is_int($ysize))
        throw new Exception('Invalid graph dimensions given.');
    if (count($xvalues) !== count($yvalues) || count($xvalues) !== count($labels))
        throw new Exception('Invalid data sets provided.');
    if (count($xvalues) == 0)
        throw new Exception('No data given.');

    if ($graph_id !== NULL) {
        $local_cache_file = CACHE_DIR.'cache_graph_'.$graph_id.'.png';
        $remote_cache_file = CACHE_DL.'cache_graph_'.$graph_id.'.png';
        if (file_exists($local_cache_file)) {
            $mtime = filemtime($local_cache_file);
            $time = time();
            if (($mtime + (60*60*24)) < $time) {
                // Refresh plot
                unlink($local_cache_file);
            }
            else return $remote_cache_file;
        }
    } else {
        $rand = rand(10000,99999);
        $local_cache_file = CACHE_DIR.'cache_graph_'.$rand.'.png';
        $remote_cache_file = CACHE_DL.'cache_graph_'.$rand.'.png';
    }
    
    // Create the Graph object
    $graph = new Graph($xsize,$ysize);
    
    // Set the graph title
    $graph->title->Set($title);
    $graph->title->SetFont(FF_DV_SANSSERIF,FS_BOLD,14);
    $graph->title->SetColor('#000000');
    
    $graph->SetMargin(50, 50, 20, 150);
    
    // Get the tallest line and get relatively small lines
    $max_value = 0;
    for ($i = 0; $i < count($yvalues); $i++) {
        if (max($yvalues[$i]) > $max_value)
            $max_value = max($yvalues[$i]);
    }
    $small_lines = array();
    for ($i = 0; $i < count($yvalues); $i++) {
        if (max($yvalues[$i]) < 0.01 * $max_value)
            $small_lines[] = $i;
    }
    
    // Sort out data set inputs and add plot lines which aren't 'small'
    $datasets = array();
    $allxvalues = array();
    for ($i = 0; $i < count($xvalues); $i++) {
        $allxvalues = array_merge_recursive($allxvalues,$xvalues[$i]);

        // Skip small lines
        if (in_array($i,$small_lines))
            continue;

        // Create the plot line
        $p[$i] = new LinePlot($yvalues[$i],$xvalues[$i]);
        $p[$i]->SetStyle($line_styles[1]);
        $p[$i]->SetLegend($labels[$i]);
        $graph->Add($p[$i]);
    }
    $allxvalues = array_unique($allxvalues,SORT_NUMERIC);
    asort($allxvalues);
    $allxvalues = array_values($allxvalues);
    
    // Combine small lines
    if (count($small_lines) > 0) {
        $other_x = array();
        $other_y = array();
        // Loop through all x-values
        for ($j = 0; $j < count($allxvalues); $j++) {
            $sum = 0;
            // Check if there is a point on each line for this x-value
            foreach ($small_lines AS $i) {
                for ($k = 0; $k < count($xvalues[$i]); $k++) {
                    if($xvalues[$i][$k] == $allxvalues[$j])
                        $sum += $yvalues[$i][$k];
                }
            }
            // Add the point to the graph
            $other_x[] = $allxvalues[$j];
            $other_y[] = $sum;
        }
        // Create the plot line for other
        $other = new LinePlot($other_y,$other_x);
        $other->SetStyle($line_styles[0]); // dashed
        $other->SetLegend('Other');
        $graph->Add($other);
    }
    
    // Add some grace to the end of the X-axis scale so that the first and last
    // data point isn't exactly at the very end or beginning of the scale
    $grace = 60*60*24*7;
    $xmin = min($allxvalues)-$grace;
    $xmax = max($allxvalues)+$grace;

    // Get ticks
    $dateUtils = new DateScaleUtils();
    list($tickPositions,$minTickPositions) = $dateUtils->GetTicksFromMinMax($xmin,$xmax,DSUTILS_MONTH);
    
    // We use an integer scale on the X-axis since the positions on the X axis
    // are assumed to be UNIX timestamps
    $graph->SetScale('intlin',0,0,$xmin,$xmax);

    // Make sure that the X-axis is always at the bottom of the scale
    // (By default the X-axis is alwys positioned at Y=0 so if the scale
    // doesn't happen to include 0 the axis will not be shown)
    $graph->xaxis->SetPos('min');
    
    // Now set the tick positions
    $graph->xaxis->SetTickPositions($tickPositions,$minTickPositions);
    $graph->xaxis->SetLabelAngle(45);

    // The labels should be formatted at dates with "Year-month"
    $graph->xaxis->SetLabelFormatString('d-M-y',true);
    $graph->xaxis->SetFont(FF_DV_SANSSERIF,FS_NORMAL,8);
    $graph->xaxis->SetColor('#000000');
    $graph->xaxis->title->Set("Date");
    $graph->xaxis->title->SetFont(FF_DV_SANSSERIF,FS_BOLD,10);
    $graph->xaxis->title->SetColor('#000000');
    
    $graph->yaxis->SetColor('#000000');

    // Add a X-grid
    $graph->xgrid->Show();
    
    // Set legend position
    $graph->legend->Pos(0.5,0.99,"center","bottom");
    $graph->legend->SetFont(FF_DV_SANSSERIF,FS_NORMAL,7);
    
    $genLbl = new Text("Generated on: ".date('d-m-Y H:i:s')); 
    $genLbl->SetPos(0.99,0.99,"right","bottom");
    $genLbl->SetColor("red"); 
    $genLbl->Show();
    $graph->addText($genLbl);

    // Output graph
    $graph->Stroke($local_cache_file);
    return $remote_cache_file;
}
?>