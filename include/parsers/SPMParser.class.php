<?php
/**
 * copyright 2017 SuperTuxKart-Team
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
 * Helper class to parse SPM model files
 */
class SPMParser extends Parser
{
    /**
     * @var bool
     */
    protected $binary_file = true;
    /**
     * @var unsigned short
     */
    private $material_count = 0;

    /**
     * @throws SPMException
     */
    protected function _loadFile()
    {
        // Read the file header
        fseek($this->file, 0);
        $read = fread($this->file, 2);
        if ($read !== 'SP')
        {
            throw new SPMException('Invalid header on SPM file');
        }

        // Skip misc header (like bounding box)
        fseek($this->file, 28);
        $read = fread($this->file, 2);
        $byte = unpack('v', $read);
        $this->material_count = $byte[1];
    }

    /**
     * Get the textures referenced by the model
     *
     * @return array
     * @throws SPMException
     */
    public function listTextures()
    {
        if (!$this->file)
        {
            throw new SPMException('No SPM file opened');
        }
        if ($this->material_count === 0)
        {
            return [];
        }
        $textures = [];
        fseek($this->file, 30);

        // For each material there are 2 layers of textures reserved, if first
        // byte > 0 then it's the string length of the texture file if there is
        // a texture for that layer
        for ($i = 0; $i < $this->material_count; $i++)
        {
            $read_one = fread($this->file, 1);
            $byte_one = unpack('C', $read_one);
            $tex_one_size = $byte_one[1];
            if ($tex_one_size > 0)
            {
                $textures[] = fread($this->file, $tex_one_size);
            }
            $read_two = fread($this->file, 1);
            $byte_two = unpack('C', $read_two);
            $tex_two_size = $byte_two[1];
            if ($tex_two_size > 0)
            {
                $textures[] = fread($this->file, $tex_two_size);
            }
        }
        return $textures;
    }
}
