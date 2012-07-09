<?php
/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
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

require_once(ROOT.'include/parsers/Parser.class.php');

/**
 * Helper class to parse XML files included with addons 
 */
class addonXMLParser extends Parser {
    private $file_type;
    private $file_contents;
    private $values;
    private $index;
    
    private $attributes = array();
    
    protected function _loadFile() {
	$this->file_contents = fread($this->file,$this->file_size);
	// Fix common XML errors
	$this->file_contents = trim($this->file_contents);
	$this->file_contents = str_replace('& ','&amp;',$this->file_contents);

	// Get type of xml file
	$reader = xml_parser_create();
	if (!xml_parse_into_struct($reader,
		$this->file_contents,$values,$index))
	    throw new XMLParserException('XML Error: '.xml_error_string(xml_get_error_code($reader)));
	
	$this->file_type = $values[0]['tag'];
	$this->values = $values;
	$this->index = $index;
    }
    
    public function addonFileAttributes() {
	// Loop through xml tags
	$attributes = array();
	foreach ($this->values AS $val)
	{
	    if ($val['tag'] != $this->file_type)
		continue;
	    
	    if (!isset($val['attributes']))
		continue;

	    foreach ($val['attributes'] AS $attribute => $value) {
		$attributes[strtolower($attribute)] = $value;
	    }
	}

	// Make sure certain attributes exist
	if (!array_key_exists('arena',$attributes))
	    $attributes['arena'] = '0';
	if (!array_key_exists('designer',$attributes))
	    $attributes['designer'] = '';

	return $attributes;
    }
    
    public function setAttribute($attb, $value) {
	$this->attributes[$attb] = $value;
    }
    
    public function writeAttributes() {
	if (!$this->writeable)
	    throw new XMLParserException('You did not open the file for writing, so you cannot write to it.');
	
	// Set up the XMLWriter to modify the XML file
	$writer = new XMLWriter();
	$writer->openMemory();
	$writer->startDocument('1.0');
	$writer->setIndent(true);
	$writer->setIndentString('    ');

	// Cycle through all of the xml file's elements
	foreach ($this->values AS $val)
	{
	    if ($val['type'] == 'close')
	    {
		$writer->endElement();
		continue;
	    }
	    if ($val['type'] == 'open' || $val['type'] == 'complete')
		$writer->startElement(strtolower($val['tag']));
	    if (isset($val['attributes']))
	    {
		foreach ($val['attributes'] AS $attribute => $value)
		{
		    // Add attributes to the root element
		    if ($val['tag'] == $this->file_type)
		    {
			$attribute = strtolower($attribute);
			if (isset($this->attributes[$attribute])) {
			    $writer->writeAttribute($attribute,$this->attributes[$attribute]);
			    unset($this->attributes[$attribute]);
			} else {
			    $writer->writeAttribute($attribute, $value);
			}
		    } else {
			// For all other elements, just write all attributes
			$writer->writeAttribute(strtolower($attribute),$value);
		    }
		}
		// Write any remaining attributes that we wanted to set
		if ($val['tag'] == $this->file_type) {
		    foreach ($this->attributes AS $attb => $attb_val) {
			$writer->writeAttribute($attb,$attb_val);
		    }
		}
	    }
	    if ($val['type'] == 'complete')
		$writer->endElement();
	}
	$writer->endDocument();
	$new_xml = $writer->flush();

	rewind($this->file);
	fwrite($this->file, $new_xml, strlen($new_xml));
	ftruncate($this->file, strlen($new_xml));

	// Clear attributes
	$this->attributes = array();
    }
    
    public function getType() {
	return $this->file_type;
    }
}

class XMLParserException extends ParserException {}
?>
