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

require_once(INCLUDE_PATH . 'graphs.php');

/**
 * Class Report
 */
class Report
{
    /**
     * @var string
     */
    private $report_title;

    /**
     * @var null
     */
    private $report_description = null;

    /**
     * @var array
     */
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
     *
     * @param string $description Report description in HTML format
     */
    public function addDescription($description)
    {
        $this->report_description = $description;
    }

    /**
     * Add a section to the current report
     *
     * @param string $name Section heading
     *
     * @return int Section id
     */
    public function addSection($name)
    {
        $this->report_structure[] = array('name' => $name, 'content' => null);

        // return the last index added to the array
        return Util::array_last_key($this->report_structure);
    }

    /**
     * Inserts a graph into the report. The query must be formed such that
     * the first column returned is a label, the second is a date, and the third
     * is a numerical value. This function can handle graphs with multiple
     * lines.
     *
     * @param string      $section
     * @param string      $query
     * @param string      $chartTitle
     * @param string|null $graphId
     */
    public function addTimeGraph($section, $query, $chartTitle, $graphId = null)
    {
        $db = DBConnection::get();

        $query_result = "\t<h3>Graph</h3>\n";
        $query_result .= "\t<code>" . htmlspecialchars($query) . "</code>\n";

        // retrieve from the database
        try
        {
            $points = $db->query($query, DBConnection::FETCH_ALL);
            $count = count($points);
        }
        catch(DBException $e)
        {
            $this->report_structure[$section]['content'] .= $query_result . '<p>ERROR.</p>';

            return;
        }

        // build view
        $query_result .= "\t<h3>Result</h3>\n";
        $query_result .= "\t<p>($count rows returned)</p>\n";
        if ($count === 0)
        {
            $this->report_structure[$section]['content'] .= $query_result;

            return;
        }

        // get columns
        $col_names = array_keys($points[0]);

        // Group points by label
        $lines = [];
        foreach ($points as $point)
        {
            // group by different types, aka different lines on the time plot

            // Hash the line label in the key to remove bad characters
            $label = $point[$col_names[0]];
            $x = $point[$col_names[1]];
            $y = $point[$col_names[2]];
            $key = md5($label);

            // create key for the first time
            if (!array_key_exists($key, $lines))
            {
                $lines[$key] = [
                    'label' => $label,
                    'x'     => [],
                    'y'     => []
                ];
            }

            // fill the x and y values
            $lines[$key]['x'][] = $x;
            $lines[$key]['y'][] = $y;
        }

        // get rid of the hash values for some reason and make the indices to be numbers
        $lines = array_values($lines);
        //var_dump($lines[0]['x']);

        // combine all of them
        // Split into arrays of x-values, y-values and labels
        $x_values = [];
        $y_values = [];
        $labels = [];
        foreach ($lines AS $line)
        {
            $x_values[] = $line['x']; // array of arrays
            $y_values[] = $line['y']; // array of arrays
            $labels[] = $line['label']; // array of labels
        }

        // Make x-values numeric
        $count_x_values = count($x_values);
        for ($i = 0; $i < $count_x_values; $i++)
        {
            $count_xi_values = count($x_values[$i]);
            for ($j = 0; $j < $count_xi_values; $j++)
            {
                $x_values[$i][$j] = strtotime($x_values[$i][$j]);
            }
        }

        //var_dump($x_values);

        $data_file = graph_data_to_json($x_values, $y_values, $labels, $graphId);
        $query_result .= '<div class="time_chart" id="' . $graphId . '">' . $chartTitle . "\n" . $data_file . '</div>';

        $this->report_structure[$section]['content'] .= $query_result;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $return = "<html>\n<head>\n\t<title>{$this->report_title}</title>\n";
        $return .= '<meta http-equiv="content-type" content="text/html; charset=utf-8">';
        $return .= "\t<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js\" type=\"text/javascript\"></script>\n";
        $return .= "\t<script src=\"https://www.google.com/jsapi\" type=\"text/javascript\"></script>\n";
        $return .= "\t<script src=\"" . JS_LOCATION . "reports.js\" type=\"text/javascript\"></script>\n";
        $return .= "\t<link href=\"" . CSS_LOCATION . "report.css\" rel=\"StyleSheet\" type=\"text/css\" />\n</head>\n";
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
