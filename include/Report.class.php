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
        
        $query_result .= "\t<table>\n\t\t<thead>\n\t\t\t<tr>\n";
        // List column names
        for ($i = 0; $i < mysql_num_fields($handle); $i++) {
            $field_info = mysql_fetch_field($handle, $i);
            $query_result .= "\t\t\t\t<th>{$field_info->name}</th>\n";
        }
        $query_result .= "\t\t\t</tr>\n\t\t</thead>\n\t\t<tbody>\n";
        
        // List results
        for ($i = 1; $i <= $count; $i++)
        {
            $result = mysql_fetch_assoc($handle);
            $query_result .= "\t\t\t<tr>\n";
            foreach ($result AS $current_result)
            {
                $query_result .= "\t\t\t\t<td>{$current_result}</td>\n";
            }
            $query_result .= "\t\t\t<tr>\n";
        }
        
        $query_result .= "\t\t</tbody>\n\t</table>";
        
        $this->report_structure[$section]['content'] .= $query_result;
    }
    
    public function __toString()
    {
        $return = "<html>\n<head>\n\t<title>{$this->report_title}</title>\n</head>\n";
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
