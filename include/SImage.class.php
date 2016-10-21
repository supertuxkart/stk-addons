<?php
/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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
 * Class to hold all image-manipulation operations
 * @author Stephen
 */
class SImage
{
    /**
     * @const int
     */
    const SIZE_SMALL = 1;

    /**
     * @const int
     */
    const SIZE_MEDIUM = 2;

    /**
     * @const int
     */
    const SIZE_BIG = 3;

    /**
     * The full path to the image
     * @var string
     */
    private $path;

    /**
     * The image formatL jpeg, png
     * @var int
     */
    private $format;

    /**
     * @var array
     */
    private $info;

    /**
     * @var resource
     */
    private $imageData;

    /**
     * @param string $image_path
     *
     * @throws SImageException
     */
    public function __construct($image_path)
    {
        $this->setPath($image_path);

        // TODO make this code into function
        $image_info = getimagesize($this->path);
        switch ($image_info[2])
        {
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($this->path);
                $this->format = IMAGETYPE_PNG;
                break;

            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($this->path);
                $this->format = IMAGETYPE_JPEG;
                break;

            default:
                throw new SImageException('Unsupported image format.');
        }

        $this->info = $image_info;
        $this->imageData = $source;
    }

    /**
     * @param string $path to the image file
     * @throws SImageException if file does not exist or is not a file
     */
    public function setPath($path)
    {
        if (!file_exists($path))
        {
            throw new SImageException(_h('Path does not exist on the filesystem'));
        }
        if (!is_file($path))
        {
            throw new SImageException(_h('Path does not point to a file.'));
        }

        $this->path = $path;
    }

    /**
     * Write the current image to a file
     *
     * @param string $file_path Absolute file path
     */
    public function save($file_path)
    {
        if ($this->format === IMAGETYPE_PNG)
        {
            imagepng($this->imageData, $file_path);
        }
        elseif ($this->format === IMAGETYPE_JPEG)
        {
            imagejpeg($this->imageData, $file_path);
        }
    }

    /**
     * Scale image based on given dimensions.
     *
     * @param int $max_x Max width, or 0 to ignore
     * @param int $max_y Max height, or 0 to ignore
     * @param int $min_x > 1
     * @param int $min_y > 1
     *
     * @throws SImageException
     */
    public function scale($max_x, $max_y, $min_x = 1, $min_y = 1)
    {
        if (($max_x < $min_x && $max_x !== 0) || $max_y < $min_y && $max_y !== 0)
        {
            throw new SImageException('Maximum dimension is less than minimum dimension. Cannot scale image.');
        }

        $old_x = $this->info[0];
        $old_y = $this->info[1];

        if ($old_y == 0 || $old_x == 0)
        {
            throw new SImageException('Image dimensions cannot be 0. Failed to scale image.');
        }

        if ($max_x === 0)
        {
            // Scale based on image height
            $new_y = 0;
            if ($old_y > $max_y)
            {
                $new_y = $max_y;
            }
            if ($old_y < $min_y)
            {
                $new_y = $min_y;
            }

            $new_x = (int)(($new_y / $old_y) * $old_x);
            if ($new_x < $min_x)
            {
                $new_x = $min_x;
            }
        }
        elseif ($max_y === 0)
        {
            // Scale based on image width
            $new_x = 0;
            if ($old_x > $max_x)
            {
                $new_x = $max_x;
            }
            if ($old_x < $min_x)
            {
                $new_x = $min_x;
            }

            $new_y = (int)(($new_x / $old_x) * $old_y);
            if ($new_y < $min_y)
            {
                $new_y = $min_y;
            }
        }
        else
        {
            // Scale image based on both max dimensions
            if ($old_x > $old_y)
            {
                $new_x = 0;
                if ($old_x > $max_x)
                {
                    $new_x = $max_x;
                }
                if ($old_x < $min_x)
                {
                    $new_x = $min_x;
                }

                $new_y = (int)(($new_x / $old_x) * $old_y);
                if ($new_y < $min_y)
                {
                    $new_y = $min_y;
                }
                if ($new_y > $max_y)
                {
                    $new_y = $max_y;
                }
            }
            else
            {
                $new_y = 0;
                if ($old_y > $max_y)
                {
                    $new_y = $max_y;
                }
                if ($old_y < $min_y)
                {
                    $new_y = $min_y;
                }

                $new_x = (int)(($new_y / $old_y) * $old_x);
                if ($new_x < $min_x)
                {
                    $new_x = $min_x;
                }
                if ($new_x > $max_x)
                {
                    $new_x = $max_x;
                }
            }
        }

        // Create new canvas
        $destination = imagecreatetruecolor($new_x, $new_y);

        // Preserve transparency
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent_bg = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_x, $new_y, $transparent_bg);

        // Resize image
        imagecopyresampled($destination, $this->imageData, 0, 0, 0, 0, $new_x, $new_y, $old_x, $old_y);

        $this->imageData = $destination;
    }
}
