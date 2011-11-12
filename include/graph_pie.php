<?php
error_reporting(E_ALL);
define('ROOT','../');
include (ROOT.'config.php');
include (JPG_ROOT."jpgraph/jpgraph.php");
include (JPG_ROOT."jpgraph/jpgraph_pie.php");

// Sanity checks, and preparing variables
if (!isset($_GET['values']) || !isset($_GET['labels']) || !isset($_GET['title'])) {
    header('HTTP/1.0 404 Not Found');
    die('Not all necessary variables were set.');
}
$data   = explode(',',$_GET['values']);
$labels = explode(',',$_GET['labels']);
if (count($data) !== count($labels)) {
    header('HTTP/1.0 404 Not Found');
    die('Number of labels and number of values do not match.');
}
if (count($data) === 0 || count($labels) === 0) {
    header('HTTP/1.0 404 Not Found');
    die('There are no values to graph.');
}
foreach($data AS $test_data) {
    if (!is_numeric($test_data)) {
        header('HTTP/1.0 404 Not Found');
        die('Non-numeric data given.');
    }
}

// Create the Pie Graph.
$graph = new PieGraph(400,300);
$graph->SetShadow();

// Set A title for the plot
$graph->title->Set($_GET['title']);
$graph->title->SetFont(FF_DV_SANSSERIF,FS_BOLD,14);
$graph->title->SetColor('black');

// Create pie plot
$p1 = new PiePlot($data);
$p1->SetCenter(0.5,0.5);
$p1->SetSize(0.3);

// Setup the labels to be displayed
$p1->SetLabels($labels);

// This method adjust the position of the labels. This is given as fractions
// of the radius of the Pie. A value < 1 will put the center of the label
// inside the Pie and a value >= 1 will pout the center of the label outside the
// Pie. By default the label is positioned at 0.5, in the middle of each slice.
$p1->SetLabelPos(1);

// Setup the label formats and what value we want to be shown (The absolute)
// or the percentage.
$p1->SetLabelType(PIE_VALUE_PER);
$p1->value->Show();
$p1->value->SetFont(FF_DV_SANSSERIF,FS_NORMAL,9);
$p1->value->SetColor('darkgray');

// Add and stroke
$graph->Add($p1);

$graph->Stroke();

?>