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
 * Class Parser
 */
abstract class Parser
{
    /**
     * @var bool
     */
    protected $binary_file = false;

    /**
     * The file resource
     * @var resource
     */
    protected $file;

    /**
     * @var string
     */
    protected $file_name;

    /**
     * @var int
     */
    protected $file_size = 0;

    /**
     * Flag that indicates to open the file with write access
     * @var bool
     */
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
        if (!FileSystem::exists($file))
        {
            throw new ParserException('File not found');
        }

        $read_flag = ($write) ? 'r+' : 'r';
        if ($binary)
        {
            $read_flag .= 'b';
        }

        try
        {
            $handle = FileSystem::fileOpen($file, $read_flag);
        }
        catch (FileException $e)
        {
            throw new ParserException('Error opening file');
        }

        $this->file_name = basename($file);
        $this->file = $handle;
        $this->file_size = FileSystem::fileSize($file);

        $this->writeable = $write;
        $this->_loadFile();
    }

    /**
     * Custom load file
     */
    abstract protected function _loadFile();
}
