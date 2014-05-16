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
 * Resize an image
 *
 * @param string $file
 *
 * @return null
 */
function resizeImage($file)
{
    // Determine image size
    $type = (isset($_GET['type'])) ? $_GET['type'] : null;
    switch ($type)
    {
        default:
            $size = 100;
            break;
        case 'small':
            $size = 25;
            break;
        case 'medium':
            $size = 75;
            break;
        case 'big':
            $size = 300;
            break;
    }
    $cache_name = $size . '--' . basename($file);
    $local_path = UP_LOCATION . $file;

    // Check if image exists, and if it does, check its format
    $orig_file = File::exists($file);
    if ($orig_file === -1)
    {
        if (!file_exists(ROOT . $file))
        {
            header('HTTP/1.1 404 Not Found');

            return;
        }
        else
        {
            $local_path = ROOT . $file;
        }
    }

    // Check if a cached version is available
    if (Cache::fileExists($cache_name) !== array())
    {
        header('Cached-Image: true');
        header('Location: ' . CACHE_DL . '/' . $cache_name);

        return;
    }

    // Start processing the original file
    $image_info = @getimagesize($local_path);
    switch ($image_info[2])
    {
        default:
            $source = imagecreatefrompng(ROOT . 'image/notfound.png');
            $format = 'png';
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($local_path);
            $format = 'png';
            break;
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($local_path);
            $format = 'jpg';
            break;
    }

    // Get length and width of original image
    $width_source = imagesx($source);
    $height_source = imagesy($source);
    if ($width_source > $height_source)
    {
        $width_destination = $size;
        $height_destination = $size * $height_source / $width_source;
    }
    if ($width_source <= $height_source)
    {
        $height_destination = $size;
        $width_destination = $size * $width_source / $height_source;
    }

    // Create new canvas
    $destination = imagecreatetruecolor($width_destination, $height_destination);

    // Preserve transparency
    imagealphablending($destination, false);
    imagesavealpha($destination, true);
    $transparent_bg = imagecolorallocatealpha($destination, 255, 255, 255, 127);
    imagefilledrectangle($destination, 0, 0, $width_destination, $height_destination, $transparent_bg);

    // Resize image
    imagecopyresampled(
        $destination,
        $source,
        0,
        0,
        0,
        0,
        $width_destination,
        $height_destination,
        $width_source,
        $height_source
    );

    // Display image and cache the result
    header('Cached-Image: false');
    if ($format === 'png')
    {
        header('Content-Type: image/png');
        imagepng($destination, CACHE_DIR . '/' . $cache_name, 9);
        imagepng($destination, null, 9);
    }
    else
    {
        header("Content-Type: image/jpeg");
        imagejpeg($destination, CACHE_DIR . '/' . $cache_name, 90);
        imagejpeg($destination, null, 90);
    }

    // Create a record of the cached file
    $orig_file_addon = File::getAddon($orig_file);
    Cache::createFile($cache_name, $orig_file_addon, sprintf('w=%d,h=%d', $width_destination, $height_destination));
}

/**
 * @param string $text
 *
 * @return string
 */
function img_label($text)
{
    $write_dir = UP_LOCATION . 'temp/';
    $read_dir = DOWN_LOCATION . 'temp/';
    $text_noaccent = preg_replace('/\W/s', '_', $text);

    if (!file_exists($write_dir . 'im_' . $text_noaccent . '.png'))
    {
        $text_size = 11;
        $text_angle = 90;
        $font = ROOT . 'include/DejaVuSans.ttf';
        $bbox = imagettfbbox($text_size, $text_angle, $font, $text);

        $min_x = min(array($bbox[0], $bbox[2], $bbox[4], $bbox[6]));
        $max_x = max(array($bbox[0], $bbox[2], $bbox[4], $bbox[6]));
        $min_y = min(array($bbox[1], $bbox[3], $bbox[5], $bbox[7]));
        $max_y = max(array($bbox[1], $bbox[3], $bbox[5], $bbox[7]));

        $width = $max_x - $min_x + 2;
        $height = $max_y - $min_y + 2;

        $image = imagecreatetruecolor($width, $height);

        $bgcolor = imagecolorallocate($image, 0, 0, 0);
        imagecolortransparent($image, $bgcolor);
        $textcolor = imagecolorallocate($image, 2, 2, 2);

        imagettftext($image, $text_size, $text_angle, $width, $height, $textcolor, $font, $text);

        imagepng($image, $write_dir . 'im_' . $text_noaccent . '.png');
        imagedestroy($image);
    }

    return '<img src="' . $read_dir . 'im_' . $text_noaccent . '.png' . '" alt="' . htmlentities($text) . '" />';
}


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
