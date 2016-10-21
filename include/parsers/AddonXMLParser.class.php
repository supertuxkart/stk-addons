<?php
/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
 *
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Helper class to parse XML files included with addons
 */
class AddonXMLParser extends Parser
{
    /**
     * @var string
     */
    private $file_type;

    /**
     * The content of the file
     * @var string
     */
    private $file_contents;

    /**
     * The values of the xml data
     * @var array
     */
    private $values;

    /**
     * The pointers to the $values
     * @var array
     */
    private $index;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @throws XMLParserException
     */
    protected function _loadFile()
    {
        $this->file_contents = fread($this->file, $this->file_size);

        // Fix common XML errors
        $this->file_contents = trim($this->file_contents);
        $this->file_contents = str_replace('& ', '&amp; ', $this->file_contents);

        // Get type of xml file
        $reader = xml_parser_create();
        if (!xml_parse_into_struct($reader, $this->file_contents, $values, $index))
        {
            throw new XMLParserException('XML Error: ' . xml_error_string(xml_get_error_code($reader)) . ' - file: ' . $this->file_name);
        }

        $this->file_type = $values[0]['tag'];
        $this->values = $values;
        $this->index = $index;
    }

    /**
     * Get the attributes from the addon file.
     * Attributes include: screenshot, version, name, etc
     *
     * @return array
     */
    public function addonFileAttributes()
    {
        // Loop through xml tags
        $attributes = [];
        foreach ($this->values as $val)
        {
            if ($val['tag'] !== $this->file_type) // tag is not a file type aka is not a track or kart
            {
                continue;
            }
            if (!isset($val['attributes'])) // tag does not have attributes
            {
                continue;
            }

            foreach ($val['attributes'] as $attribute => $value)
            {
                $attributes[mb_strtolower($attribute)] = $value;
            }
        }

        // Make sure certain attributes exist
        if (!array_key_exists('arena', $attributes))
        {
            $attributes['arena'] = '0';
        }
        if (!array_key_exists('designer', $attributes))
        {
            $attributes['designer'] = '';
        }

        return $attributes;
    }

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Write new attributes back to the file
     *
     * @throws XMLParserException
     */
    public function writeAttributes()
    {
        if (!$this->writeable)
        {
            throw new XMLParserException('You did not open the file for writing, so you cannot write to it.');
        }

        // Set up the XMLWriter to modify the XML file
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0');
        $writer->setIndent(true);
        $writer->setIndentString('    ');

        // Cycle through all of the xml file's elements
        foreach ($this->values as $val)
        {
            if ($val['type'] === 'close')
            {
                $writer->endElement();
                continue;
            }

            if ($val['type'] === 'open' || $val['type'] === 'complete')
            {
                $writer->startElement(mb_strtolower($val['tag']));
            }

            if (isset($val['attributes']))
            {
                foreach ($val['attributes'] as $attribute => $value)
                {
                    // Add attributes to the root element
                    if ($val['tag'] === $this->file_type)
                    {
                        $attribute = mb_strtolower($attribute);
                        if (isset($this->attributes[$attribute]))
                        {
                            $writer->writeAttribute($attribute, $this->attributes[$attribute]);
                            unset($this->attributes[$attribute]);
                        }
                        else
                        {
                            $writer->writeAttribute($attribute, $value);
                        }
                    }
                    else
                    {
                        // For all other elements, just write all attributes
                        $writer->writeAttribute(mb_strtolower($attribute), $value);
                    }
                }

                // Write any remaining attributes that we wanted to set
                if ($val['tag'] === $this->file_type)
                {
                    foreach ($this->attributes as $attb => $attb_val)
                    {
                        $writer->writeAttribute($attb, $attb_val);
                    }
                }
            }

            if ($val['type'] === 'complete')
            {
                $writer->endElement();
            }
        }
        $writer->endDocument();
        $new_xml = $writer->flush();

        rewind($this->file);
        fwrite($this->file, $new_xml, mb_strlen($new_xml));
        ftruncate($this->file, mb_strlen($new_xml));

        // Clear attributes
        $this->attributes = [];
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->file_type;
    }
}
