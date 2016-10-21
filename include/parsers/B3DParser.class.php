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
 * Helper class to parse B3D model files
 * @link http://www.blitzbasic.com/sdkspecs/sdkspecs/b3dfile_specs.txt
 */
class B3DParser extends Parser
{
    /**
     * @var bool
     */
    protected $binary_file = true;

    /**
     * @var int
     */
    private $loc_texs;

    /**
     * @var int
     */
    private $loc_brus;

    /**
     * @var int
     */
    private $loc_node;

    /**
     * @var int
     */
    private $loc_mesh;

    /**
     * @throws B3DException
     */
    protected function _loadFile()
    {
        // Read the file header
        fseek($this->file, 0);
        $read = fread($this->file, 4);
        if ($read !== 'BB3D')
        {
            throw new B3DException('Invalid header on B3D file');
        }

        // Read file size byte
        $read = fread($this->file, 4);
        $byte = unpack('V', $read);

        // The internal counter does not include the first 8 bytes
        if ($byte[1] != ($this->file_size - 8))
        {
            throw new B3DException('File size declaration is incorrect');
        }

        // Find section markers
        for ($i = 0; $i <= $this->file_size; $i++)
        {
            // go to the i position
            $chunk_start = false;
            fseek($this->file, $i);

            // read 4 bytes at a time
            $chunk = fread($this->file, 4);

            switch ($chunk)
            {
                case 'TEXS':
                    $this->loc_texs = $i;
                    $chunk_start = true;
                    break;

                case 'BRUS':
                    $this->loc_brus = $i;
                    $chunk_start = true;
                    break;

                case 'NODE':
                    $this->loc_node = $i;
                    $chunk_start = true;
                    break;

                case 'MESH':
                    $this->loc_mesh = $i;
                    $chunk_start = true;
                    break;

                default:
                    break;
            }

            // Skip ahead by the size of the chunk
            if ($chunk_start === true)
            {
                $read = fread($this->file, 4);
                $int = unpack('V', $read);
                $i += (8 + $int[1] - 1);
            }
        }
    }

    /**
     * Get the textures referenced by the model
     *
     * @return array
     * @throws B3DException
     */
    public function listTextures()
    {
        if (!$this->file)
        {
            throw new B3DException('No B3D file opened');
        }
        if ($this->loc_texs === null)
        {
            return [];
        }

        $textures = [];
        fseek($this->file, $this->loc_texs);
        $read = fread($this->file, 4);
        if ($read !== 'TEXS')
        {
            throw new B3DException('Texture declaration not found');
        }

        // Read texture chunk size
        $read = fread($this->file, 4);
        $byte = unpack('V', $read);
        $chunk_size = $byte[1];
        $chunk = fread($this->file, $chunk_size);

        // Format is <name><flags><blend><xpos><ypos><xscale><yscale><rot>,
        // <name> is padded with a null byte
        // Everything following is 28 bytes long
        while (mb_strlen($chunk) !== 0)
        {
            $fname_len = mb_strpos($chunk, "\x00");
            $textures[] = mb_substr($chunk, 0, $fname_len);
            $chunk = mb_substr($chunk, $fname_len + 29);
        }

        return $textures;
    }
}
