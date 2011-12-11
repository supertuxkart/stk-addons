<?php
error_reporting(E_ALL);
define('ROOT','../');
require_once(ROOT.'config.php');
require_once(JPG_ROOT.'jpgraph/jpgraph.php');
require_once(JPG_ROOT.'jpgraph/jpgraph_line.php');
require_once(JPG_ROOT.'jpgraph/jpgraph_utils.inc.php');
require_once(JPG_ROOT.'jpgraph/jpgraph_text.inc.php');

function graph_date_line($title, $xvalues, $yvalues, $labels, $graph_id = NULL, $xsize = 800, $ysize = 600) {
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
    
    $graph->SetMargin(50, 50, 20, 150);
    
    // Sort out data set inputs and add plot lines
    $datasets = array();
    $allxvalues = array();
    for ($i = 0; $i < count($xvalues); $i++) {
        // Create the plot line
        $p[$i] = new LinePlot($yvalues[$i],$xvalues[$i]);
        $p[$i]->SetLegend($labels[$i]);
        $graph->Add($p[$i]);
        
        $allxvalues = array_merge_recursive($allxvalues,$xvalues[$i]);
    }
    $allxvalues = array_unique($allxvalues,SORT_NUMERIC);
    asort($allxvalues);
    $allxvalues = array_values($allxvalues);
    
    // Add some grace to the end of the X-axis scale so that the first and last
    // data point isn't exactly at the very end or beginning of the scale
    $grace = 60*60*24*7;
    $xmin = min($allxvalues)-$grace;
    $xmax = max($allxvalues)+$grace;

    // Get ticks
    $dateUtils = new DateScaleUtils();
    list($tickPositions,$minTickPositions) = $dateUtils->GetTicksFromMinMax($xmin,$xmax);
    
    // We use an integer scale on the X-axis since the positions on the X axis
    // are assumed to be UNIX timestamps
    $graph->SetScale('intlin',0,0,$xmin,$xmax);

    // Make sure that the X-axis is always at the bottom of the scale
    // (By default the X-axis is alwys positioned at Y=0 so if the scale
    // doesn't happen to include 0 the axis will not be shown)
    $graph->xaxis->SetPos('min');
    
    // Now set the tick positions
    $graph->xaxis->SetTickPositions($tickPositions,$minTickPositions);

    // The labels should be formatted at dates with "Year-month"
    $graph->xaxis->SetLabelFormatString('d-M-y',true);
    $graph->xaxis->SetFont(FF_DV_SANSSERIF,FS_NORMAL,8);

    // Add a X-grid
    $graph->xgrid->Show();
    
    // Set legend position
    $graph->legend->Pos(0.5,0.99,"center","bottom");
    $graph->legend->SetFont(FF_DV_SANSSERIF,FS_NORMAL,6);
    
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