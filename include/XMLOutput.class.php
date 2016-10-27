<?php

/**
 * copyright 2013      Glenn De Jonghe
 *           2014-2016 Daniel Butum <danibutum at gmail dot com>
 * This file is part of SuperTuxKart
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
interface IAsXML
{
    /**
     * Get the object as a XML string
     * @return mixed
     */
    function asXML();
}

/**
 * XMLOutput class, handles all the XML writing behaviour for the API
 */
class XMLOutput extends XMLWriter
{
    /**
     * The constructor
     */
    public function __construct()
    {
        $this->openMemory();
        $this->setIndent(false);
    }

    /**
     * Insert XML as a string.
     *
     * @param string $xml_string the xml string to write
     *
     * @return bool
     */
    public function insert($xml_string)
    {
        return $this->writeRaw($xml_string);
    }

    /**
     * Will flush all output and output as XML.
     */
    public function printToScreen()
    {
        ob_start();
        header('Content-type: text/xml');
        echo $this->outputMemory();
        ob_end_flush();
    }

    /**
     * Can be used for debugging purposes. Flushed the memory.
     */
    public function printAsString()
    {
        echo $this->outputMemory();
    }

    /**
     * Helper method. Add an error element that is sent to the server, with attribute success no
     *
     * @param string $element_name
     * @param string $info
     */
    public function addErrorElement($element_name, $info)
    {
        // handle MAINTENANCE_MODE
        $attr_info = function_exists("h") ? h($info) : $info;

        $this->startElement($element_name);
            $this->writeAttribute('success', 'no');
            $this->writeAttribute('info', $attr_info);
        $this->endElement();
    }

    /**
     * Can be used for debugging purposes or to pass between methods. Flushes the memory.
     */
    public function asString()
    {
        return $this->outputMemory();
    }

    /**
     * Helper function that exits with an xml error.
     *
     * @param string $message
     */
    public static function exitXML($message)
    {
        $output = new XMLOutput();
        $output->startDocument('1.0', 'UTF-8');

        $output->addErrorElement("request", $message);
        $output->endDocument();

        $output->printToScreen();
        exit();
    }
}
