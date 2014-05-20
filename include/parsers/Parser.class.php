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

abstract class Parser
{
    protected $binary_file = false;

    protected $file = null;

    protected $file_name = null;

    protected $file_size = 0;

    protected $writeable = false;

    /**
     * Load a file into the parser
     *
     * @param string  $file   File, absolute path
     * @param boolean $write  Open with write access
     * @param boolean $binary Open in binary mode
     *
     * @throws ParserException
     */
    public function loadFile($file, $write = false, $binary = null)
    {
        if ($binary === null)
        {
            $binary = $this->binary_file;
        }
        if (!file_exists($file))
        {
            throw new ParserException('File not found');
        }

        $read_flag = ($write) ? 'r+' : 'r';
        if ($binary)
        {
            $read_flag .= 'b';
        }
        $handle = fopen($file, $read_flag);
        $this->file_name = basename($file);
        if (!$handle)
        {
            throw new ParserException('Error opening file');
        }
        $this->file = $handle;

        $this->file_size = filesize($file);

        $this->writeable = $write;
        $this->_loadFile();
    }

    abstract protected function _loadFile();
}
