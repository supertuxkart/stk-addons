<?php
/**
 * copyright 2013 Glenn De Jonghe
 *
 * This file is part of SuperTuxKart
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
class XMLOutput extends XMLWriter
{
    /**
     * The constructor
     */
    public function __construct()
    {
        $this->openMemory();
        $this->setIndent(true);
        $this->setIndentString('    ');
    }

    /**
     * Insert XML as a string. (
     */
    public function insert($xml)
    {
        return $this->writeRaw($xml);
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
        echo htmlentities($this->outputMemory());
    }

    /**
     * Can be used for debugging purposes or to pass between methods. Flushes the memory.
     */
    public function asString()
    {
        return $this->outputMemory();
    }
}
