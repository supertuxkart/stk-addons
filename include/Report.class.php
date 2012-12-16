<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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

class Report 
{
    private $report_title;
    private $report_description = NULL;
    private $report_structure = array();
    
    /**
     * Constructor
     */
    public function Report($title = "STK Add-Ons Report")
    {
        $this->report_title = $title;
    }
    
    /**
     * Add a report description
     * @param string $description Report description in HTML format
     */
    public function addDescription($description)
    {
        $this->report_description = $description;
    }
    
    /**
     * Add a section to the current report
     * @param string $name Section heading
     * @return int Section id
     */
    public function addSection($name)
    {
        $this->report_structure[] = array('name' => $name, 'content' => NULL);
        end($this->report_structure);
        return key($this->report_structure);
    }
    
    public function addQuery($section,$query)
    {
        $query_result = "\t<h3>Query</h3>\n";
        $query_result .= "\t<code>".htmlspecialchars($query)."</code>\n";
        $handle = sql_query($query);
        if (!$handle) 
        {
            $this->report_structure[$section]['content'] .= $query_result.'<p>ERROR.</p>';
            return;
        }
        $count = mysql_num_rows($handle);
        $query_result .= "\t<h3>Result</h3>\n";
        $query_result .= "\t<p>($count rows returned)</p>\n";
        
        $query_result .= "\t<table cellspacing=\"0\" class=\"sortable\"><thead>\n\t\t<tr>\n";
        // List column names
        for ($i = 0; $i < mysql_num_fields($handle); $i++) {
            $field_info = mysql_fetch_field($handle, $i);
            $query_result .= "\t\t\t<th>{$field_info->name}</th>\n";
        }
        $query_result .= "\t\t</tr></thead><tbody>\n";
        
        // List results
        for ($i = 1; $i <= $count; $i++)
        {
            $result = mysql_fetch_assoc($handle);
            $query_result .= "\t\t<tr>\n";
            foreach ($result AS $current_result)
            {
                $current_result = htmlspecialchars($current_result);
                $query_result .= "\t\t\t<td>{$current_result}</td>\n";
            }
            $query_result .= "\t\t</tr>\n";
        }
        
        $query_result .= "\t</tbody></table>";
        
        $this->report_structure[$section]['content'] .= $query_result;
    }
    
    /**
     * Inserts a pie chart into the report. The query must be formed such that
     * the first column returned is a label, and the second is a numerical
     * value.
     * @param string $section
     * @param string $query
     * @param string $graphId
     * @return void
     */
    public function addPieChart($section,$query,$chartTitle,$graphId = NULL) {
        $query_result = "\t<h3>Pie Chart</h3>\n";
        $query_result .= "\t<code>".htmlspecialchars($query)."</code>\n";
        $handle = sql_query($query);
        if (!$handle) 
        {
            $this->report_structure[$section]['content'] .= $query_result.'<p>ERROR.</p>';
            return;
        }
        $count = mysql_num_rows($handle);
        $query_result .= "\t<h3>Result</h3>\n";
        $query_result .= "\t<p>($count rows returned)</p>\n";
        
        $values = array();
        $labels = array();
        for ($i = 0; $i < $count; $i++) {
            $result = mysql_fetch_array($handle);
            $labels[] = $result[0];
            $values[] = $result[1];
        }
        
        require_once(ROOT.'include/graphs.php');
	$data_file = graph_data_to_json($values, $labels, 'pie', $graphId);
	$query_result .= '<div class="pie_chart" id="'.$graphId.'">'.$chartTitle."\n".$data_file.'</div>';

        $this->report_structure[$section]['content'] .= $query_result;
    }
    
    /**
     * Inserts a graph into the report. The query must be formed such that
     * the first column returned is a label, the second is a date, and the third
     * is a numerical value. This function can handle graphs with multiple
     * lines.
     * @param type $section
     * @param type $query
     * @return type 
     */
    public function addTimeGraph($section,$query,$chartTitle,$graphId = NULL) {
        $query_result = "\t<h3>Graph</h3>\n";
        $query_result .= "\t<code>".htmlspecialchars($query)."</code>\n";
        $handle = sql_query($query);
        if (!$handle) 
        {
            $this->report_structure[$section]['content'] .= $query_result.'<p>ERROR.</p>';
            return;
        }
        $count = mysql_num_rows($handle);
        $query_result .= "\t<h3>Result</h3>\n";
        $query_result .= "\t<p>($count rows returned)</p>\n";
        if ($count == 0) {
            $this->report_structure[$section]['content'] .= $query_result;
            return;
        }

        // Load all points from database
        $points = array();
        for ($i = 0; $i < $count; $i++) {
            $result = mysql_fetch_array($handle);
            $points[] = $result;
        }
        
        // Group points by label
        $lines = array();
        foreach ($points AS $point) {
            // Hash the line label in the key to remove bad characters
            if (!array_key_exists(md5($point[0]),$lines))
                $lines[md5($point[0])] = array('label' => $point[0], 'x' => array(), 'y' => array());
            
            $lines[md5($point[0])]['x'][] = $point[1];
            $lines[md5($point[0])]['y'][] = $point[2];
        }
        $lines = array_values($lines);
        
        // Split into arrays of x-values, y-values and labels
        $xvalues = array();
        $yvalues = array();
        $labels = array();
        foreach ($lines AS $line) {
            $xvalues[] = $line['x'];
            $yvalues[] = $line['y'];
            $labels[] = $line['label'];
        }
        // Make x-values numeric
        for ($i = 0; $i < count($xvalues); $i++) {
            for ($j = 0; $j < count($xvalues[$i]); $j++)
                $xvalues[$i][$j] = strtotime($xvalues[$i][$j]);
        }
        
        require_once(ROOT.'include/graphs.php');
	$data_file = graph_data_to_json(array($xvalues, $yvalues), $labels, 'time', $graphId);
	$query_result .= '<div class="time_chart" id="'.$graphId.'">'.$chartTitle."\n".$data_file.'</div>';

        $this->report_structure[$section]['content'] .= $query_result;
    }
    
    public function __toString()
    {
        $return = "<html>\n<head>\n\t<title>{$this->report_title}</title>\n";
        $return .= "\t<script src=\"".ROOT."js/sorttable.js\" type=\"text/javascript\"></script>\n";
	$return .= "\t<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js\" type=\"text/javascript\"></script>\n";
	$return .= "\t<script src=\"https://www.google.com/jsapi\" type=\"text/javascript\"></script>\n";
	$return .= "\t<script src=\"".ROOT."js/reports.js\" type=\"text/javascript\"></script>\n";
        $return .= "\t<link href=\"".ROOT."css/report.css\" rel=\"StyleSheet\" type=\"text/css\" />\n</head>\n";
        $return .= "<body>\n\t<h1>{$this->report_title}</h1>\n";
        $return .= "\t<blockquote>{$this->report_description}</blockquote>\n";
        
        foreach ($this->report_structure AS $section)
        {
            $return .= "\t<h2>{$section['name']}</h2>\n";
            $return .= "\t<blockquote>{$section['content']}</blockquote>\n";
        }
        
        $return .= "</body>\n</html>";
        return $return;
    }
}
?>
