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
     * @param $section
     * @param $query
     */
    public function addQuery($section, $query)
    {
        $db = DBConnection::get();

        $query_result = "\t<h3>Query</h3>\n";
        $query_result .= "\t<code>" . h($query) . "</code>\n";
        try
        {
            $result = $db->query($query, DBConnection::FETCH_ALL);
            $count = count($result);
        }
        catch(DBException $e)
        {
            $this->report_structure[$section]['content'] .= $query_result . '<p>ERROR.</p>';

            return;
        }
        $query_result .= "\t<h3>Result</h3>\n";
        $query_result .= "\t<p>($count rows returned)</p>\n";

        // Don't draw the table if we have no results
        if ($count === 0)
        {
            $this->report_structure[$section]['content'] .= $query_result;

            return;
        }

        $query_result .= "\t<table cellspacing=\"0\" class=\"sortable\"><thead>\n\t\t<tr>\n";

        // List column names, $result[0] is a assoc array
        $column_names = array_keys($result[0]);
        foreach ($column_names as $col)
        {
            $query_result .= "\t\t\t<th>$col</th>\n";
        }
        $query_result .= "\t\t</tr></thead><tbody>\n";

        // List results
        foreach ($result AS $row)
        {
            $query_result .= "\t\t<tr>\n";
            foreach ($column_names as $col)
            {
                $current_result = h($row[$col]);
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
     *
     * @param string $section
     * @param string $query
     * @param string $chartTitle
     * @param string $graphId
     */
    public function addPieChart($section, $query, $chartTitle, $graphId = null)
    {
        $db = DBConnection::get();

        $query_result = "\t<h3>Pie Chart</h3>\n";
        $query_result .= "\t<code>" . h($query) . "</code>\n";
        try
        {
            $result = $db->query($query, DBConnection::FETCH_ALL);
            $count = count($result);
        }
        catch(DBException $e)
        {
            $this->report_structure[$section]['content'] .= $query_result . '<p>ERROR.</p>';

            return;
        }
        $query_result .= "\t<h3>Result</h3>\n";
        $query_result .= "\t<p>($count rows returned)</p>\n";

        if ($count === 0)
        {
            $this->report_structure[$section]['content'] .= $query_result;

            return;
        }

        $col_names = array_keys($result[0]);
        if (count($col_names) !== 2)
        {
            $this->report_structure[$section]['content'] .= "<p>Error: This query did not return a result with two columns.</p>";
        }

        $values = array();
        $labels = array();
        foreach ($result AS $row)
        {
            $labels[] = $row[$col_names[0]];
            $values[] = $row[$col_names[1]];
        }

        $data_file = graph_data_to_json($values, $labels, 'pie', $graphId);
        $query_result .= '<div class="pie_chart" id="' . $graphId . '">' . $chartTitle . "\n" . $data_file . '</div>';

        $this->report_structure[$section]['content'] .= $query_result;
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
        $query_result .= "\t<h3>Result</h3>\n";
        $query_result .= "\t<p>($count rows returned)</p>\n";
        if ($count === 0)
        {
            $this->report_structure[$section]['content'] .= $query_result;

            return;
        }

        $col_names = array_keys($points[0]);

        // Group points by label
        $lines = array();
        foreach ($points AS $point)
        {
            // Hash the line label in the key to remove bad characters
            if (!array_key_exists(md5($point[$col_names[0]]), $lines))
            {
                $lines[md5($point[$col_names[0]])] =
                    array('label' => $point[$col_names[0]], 'x' => array(), 'y' => array());
            }

            $lines[md5($point[$col_names[0]])]['x'][] = $point[$col_names[1]];
            $lines[md5($point[$col_names[0]])]['y'][] = $point[$col_names[2]];
        }
        $lines = array_values($lines);

        // Split into arrays of x-values, y-values and labels
        $xvalues = array();
        $yvalues = array();
        $labels = array();
        foreach ($lines AS $line)
        {
            $xvalues[] = $line['x'];
            $yvalues[] = $line['y'];
            $labels[] = $line['label'];
        }
        // Make x-values numeric
        for ($i = 0; $i < count($xvalues); $i++)
        {
            for ($j = 0; $j < count($xvalues[$i]); $j++)
            {
                $xvalues[$i][$j] = strtotime($xvalues[$i][$j]);
            }
        }

        $data_file = graph_data_to_json(array($xvalues, $yvalues), $labels, 'time', $graphId);
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
        $return .= "\t<script src=\"" . JS_LOCATION . "sorttable.js\" type=\"text/javascript\"></script>\n";
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
