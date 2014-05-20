<?php
/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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

/**
 * Class to hold all image-manipulation operations
 *
 * @author Stephen
 */
class SImage
{
    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $format;

    /**
     * @var array
     */
    public $info;

    /**
     * @var resource
     */
    private $imageData;

    /**
     * @param string $file
     *
     * @throws ImageException
     */
    public function __construct($file)
    {
        if (!file_exists($file))
        {
            throw new ImageException('Image file not found.');
        }

        $this->path = $file;

        $image_info = getimagesize($this->path);
        switch ($image_info[2])
        {
            default:
                throw new ImageException('Unsupported image format.');
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($this->path);
                $format = 'png';
                break;
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($this->path);
                $format = 'jpg';
                break;
        }

        $this->info = $image_info;
        $this->imagedata = $source;
        $this->format = $format;
    }

    /**
     * Write the current image to a file
     *
     * @param string $file_path Absolute file path
     */
    public function save($file_path)
    {
        if ($this->format === 'png')
        {
            imagepng($this->imageData, $file_path);
        }
        elseif ($this->format === 'jpg')
        {
            imagejpeg($this->imageData, $file_path);
        }
    }

    /**
     * Scale image based on given dimensions.
     *
     * @param int $max_x Max width, or 0 to ignore
     * @param int $max_y Max height, or 0 to ignore
     * @param int $min_x &gt; 1
     * @param int $min_y &gt; 1
     *
     * @throws ImageException
     */
    public function scale($max_x, $max_y, $min_x = 1, $min_y = 1)
    {
        if (($max_x < $min_x && $max_x !== 0) || $max_y < $min_y && $max_y !== 0)
        {
            throw new ImageException('Maximum dimension is less than minimum dimension. Cannot scale image.');
        }

        $old_x = $this->info[0];
        $old_y = $this->info[1];

        if ($old_y == 0 || $old_x == 0)
        {
            throw new ImageException('Image dimensions cannot be 0. Failed to scale image.');
        }

        if ($max_x === 0)
        {
            // Scale based on image height
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
