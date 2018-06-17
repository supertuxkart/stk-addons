<?php
/**
 * copyright 2012        Stephen Just <stephenjust@users.sf.net>
 *           2014 - 2016 Daniel Butum <danibutum at gmail dot com>
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
 */
class StkImage
{
    /**
     * Small image type
     */
    const SIZE_SMALL = 1;

    /**
     * Medium image type
     */
    const SIZE_MEDIUM = 2;

    /**
     * Large image type
     */
    const SIZE_LARGE = 3;

    /**
     * Default size of the image type
     */
    const SIZE_DEFAULT = 4;

    /**
     * PNG image type
     */
    const TYPE_PNG = IMAGETYPE_PNG;

    /**
     * JPEG image type
     */
    const TYPE_JPEG = IMAGETYPE_JPEG;

    /**
     * The full path to the image
     * @var string
     */
    private $path;

    /**
     * The image format: jpeg, png
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
     * @throws StkImageException
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
                $this->format = static::TYPE_PNG;
                break;

            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($this->path);
                $this->format = static::TYPE_JPEG;
                break;

            default:
                throw new StkImageException('Unsupported image format.');
        }

        $this->info = $image_info;
        $this->imageData = $source;
    }

    /**
     * @param string $path to the image file
     *
     * @throws StkImageException if file does not exist or is not a file
     */
    public function setPath($path)
    {
        if (!FileSystem::exists($path))
        {
            throw new StkImageException(_h('Path does not exist on the filesystem'));
        }
        if (!FileSystem::isFile($path))
        {
            throw new StkImageException(_h('Path does not point to a file.'));
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
        if ($this->format === static::TYPE_PNG)
        {
            imagepng($this->imageData, $file_path);
        }
        elseif ($this->format === static::TYPE_JPEG)
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
     * @throws StkImageException
     */
    public function scale($max_x, $max_y, $min_x = 1, $min_y = 1)
    {
        if (($max_x < $min_x && $max_x !== 0) || $max_y < $min_y && $max_y !== 0)
        {
            throw new StkImageException('Maximum dimension is less than minimum dimension. Cannot scale image.');
        }

        $old_x = $this->info[0];
        $old_y = $this->info[1];

        if ($old_y == 0 || $old_x == 0)
        {
            throw new StkImageException('Image dimensions cannot be 0. Failed to scale image.');
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

    /**
     * Resize an image, and send the new resized image to the user with http headers
     *
     * @param string $file_path
     * @param int    $size_type the image size type, see StkImage::SIZE_
     *
     * @return void
     */
    public static function resizeImage($file_path, $size_type = null)
    {
        // file is invalid
        if (!$file_path)
        {
            header('HTTP/1.1 404 Not Found');
            Debug::addMessage('Called Util::resizeImage with an empty file_path');

            return;
        }

        $size = StkImage::sizeToInt($size_type);
        $cache_name = Cache::getCachePrefix($size_type) . basename($file_path);
        $local_path = UP_PATH . $file_path; // all images should be in our upload directory

        // Check if image exists in the database
        try
        {
            $file = File::getFromPath($file_path);
        }
        catch (FileException $e)
        {
            Debug::addMessage(sprintf("%s does not exist in the database", $file_path));
            header('HTTP/1.1 404 Not Found');

            return;
        }

        // file does not exist on disk
        if (!FileSystem::exists($local_path))
        {
            Debug::addMessage(sprintf("%s does not exist on the disk", $file_path));
            header('HTTP/1.1 404 Not Found');

            return;
        }

        // Check if a cached version is available
        if (Cache::fileExists($cache_name))
        {
            header('Cached-Image: true');
            header('Location: ' . CACHE_LOCATION . $cache_name);

            return;
        }

        // Start processing the original file
        $image_info = getimagesize($local_path);
        switch ($image_info[2])
        {
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng($local_path);
                $format = 'png';
                break;

            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg($local_path);
                $format = 'jpg';
                break;

            default:
                $source = imagecreatefrompng(IMG_LOCATION . 'notfound.png');
                $format = 'png';
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
        else // $width_source <= $height_source
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
            imagepng($destination, CACHE_PATH . $cache_name, 9);
            imagepng($destination, null, 9);
        }
        else
        {
            header("Content-Type: image/jpeg");
            imagejpeg($destination, CACHE_PATH . $cache_name, 90);
            imagejpeg($destination, null, 90);
        }

        // Create a record of the cached file
        Cache::createFile(
            $cache_name,
            $file->getAddonId(),
            sprintf('w=%d,h=%d', $width_destination, $height_destination)
        );
    }

    /**
     * Crates an image with the text provided.
     *
     * @param string $text
     *
     * @return array
     */
    public static function createImageLabel($text)
    {
        $write_dir = CACHE_PATH;

        $text_no_accent = preg_replace('/\W/s', '_', $text); // remove some accents
        $image_name = 'im_' . $text_no_accent . '.png';

        if (!FileSystem::exists($write_dir . $image_name))
        {
            $text_size = 11;
            $text_angle = 90;
            $font = FONTS_PATH . 'DejaVuSans.ttf';
            $bbox = imagettfbbox($text_size, $text_angle, $font, $text);

            $min_x = min([$bbox[0], $bbox[2], $bbox[4], $bbox[6]]);
            $max_x = max([$bbox[0], $bbox[2], $bbox[4], $bbox[6]]);
            $min_y = min([$bbox[1], $bbox[3], $bbox[5], $bbox[7]]);
            $max_y = max([$bbox[1], $bbox[3], $bbox[5], $bbox[7]]);

            $width = $max_x - $min_x + 2;
            $height = $max_y - $min_y + 2;

            $image = imagecreatetruecolor($width, $height);

            // set color and transparency
            $bg_color = imagecolorallocate($image, 0, 0, 0);
            imagecolortransparent($image, $bg_color);
            $text_color = imagecolorallocate($image, 2, 2, 2);

            // set text
            imagettftext($image, $text_size, $text_angle, $width, $height, $text_color, $font, $text);

            // create the image in the write dir
            imagepng($image, $write_dir . $image_name);
            imagedestroy($image);
        }

        return [
            'dir' => CACHE_LOCATION,
            'file' => $image_name,
        ];
    }

    /**
     * Create a new image from a quad file
     *
     * @param string $quad_file the path to the XML quad file
     * @param int    $addon_id  the addon id this quad file belongs to.
     *
     * @throws FileSystemException
     * @return string   the path to the new created image
     */
    public static function createImageFromQuadsXML($quad_file, $addon_id)
    {
        $reader = xml_parser_create();

        // Remove whitespace at beginning and end of file
        $xml_content = trim(FileSystem::fileGetContents($quad_file));
        if (!xml_parse_into_struct($reader, $xml_content, $values, $index))
        {
            throw new FileSystemException('XML Error: ' . xml_error_string(xml_get_error_code($reader)));
        }

        // Cycle through all of the xml file's elements
        $quads = [];
        foreach ($values as $val)
        {
            if ($val['tag'] != 'QUAD')
            {
                continue;
            }

            if ($val['type'] === 'close' || $val['type'] === 'comment')
            {
                continue;
            }

            if (isset($val['attributes']))
            {
                if (isset($val['attributes']['INVISIBLE']) && $val['attributes']['INVISIBLE'] === 'yes')
                {
                    continue;
                }
                if (isset($val['attributes']['DIRECTION']))
                {
                    unset($val['attributes']['DIRECTION']);
                }
                $quads[] = array_values($val['attributes']);
            }
        }
        $quads_count = count($quads);

        // Replace references to other quads with proper coordinates
        for ($i = 0; $i < $quads_count; $i++)
        {
            for ($j = 0; $j <= 3; $j++)
            {
                if (preg_match('/^([0-9]+)\:([0-9])$/', $quads[$i][$j], $matches))
                {
                    $quads[$i][$j] = $quads[$matches[1]][$matches[2]];
                }
            }
        }

        // Split coordinates into arrays
        $y_min = null;
        $y_max = null;
        $x_min = null;
        $x_max = null;
        $z_min = null;
        $z_max = null;
        for ($i = 0; $i < $quads_count; $i++)
        {
            for ($j = 0; $j <= 3; $j++)
            {
                $quads[$i][$j] = explode(' ', $quads[$i][$j]);
                if (count($quads[$i][$j]) !== 3)
                {
                    throw new FileSystemException('Unexpected number of points for quad ' . $i . '.');
                }

                // Check max/min y-value
                if ($quads[$i][$j][1] > $y_max || $y_max === null)
                {
                    $y_max = $quads[$i][$j][1];
                }
                if ($quads[$i][$j][1] < $y_min || $y_min === null)
                {
                    $y_min = $quads[$i][$j][1];
                }

                // Check max/min x-value
                if ($quads[$i][$j][0] > $x_max || $x_max === null)
                {
                    $x_max = $quads[$i][$j][0];
                }
                if ($quads[$i][$j][0] < $x_min || $x_min === null)
                {
                    $x_min = $quads[$i][$j][0];
                }

                // Check max/min x-value
                if ($quads[$i][$j][2] > $z_max || $z_max === null)
                {
                    $z_max = $quads[$i][$j][2];
                }
                if ($quads[$i][$j][2] < $z_min || $z_min === null)
                {
                    $z_min = $quads[$i][$j][2];
                }
            }
        }

        // Convert y-values to a number from 0-255, and x and z-values to 0-1023
        $y_range = $y_max - $y_min + 1;
        $x_range = $x_max - $x_min + 1;
        $z_range = $z_max - $z_min + 1;
        for ($i = 0; $i < $quads_count; $i++)
        {
            for ($j = 0; $j <= 3; $j++)
            {
                $y = $quads[$i][$j][1] - $y_min;
                $y = $y / $y_range * 255;
                $quads[$i][$j][1] = (int)$y;

                $quads[$i][$j][0] = (int)(($quads[$i][$j][0] - $x_min) / $x_range * 1023);
                $quads[$i][$j][2] = (int)(1024 - (($quads[$i][$j][2] - $z_min) / $z_range * 1023));
            }
        }

        // Prepare the image
        $image = imagecreatetruecolor(1024, 1024);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        // Set up colors
        $color = [];
        for ($i = 0; $i <= 255; $i++)
        {
            $color[$i] = imagecolorallocate($image, (int)($i / 1.5), (int)($i / 1.5), $i);
        }
        $bg = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, 1023, 1023, $bg);

        // Paint quads
        for ($i = 0; $i < $quads_count; $i++)
        {
            $color_index = (int)(($quads[$i][0][1] + $quads[$i][1][1] + $quads[$i][2][1] + $quads[$i][3][1]) / 4);
            imagefilledpolygon(
                $image, // image
                [ // points
                  $quads[$i][0][0],
                  $quads[$i][0][2],
                  $quads[$i][1][0],
                  $quads[$i][1][2],
                  $quads[$i][2][0],
                  $quads[$i][2][2],
                  $quads[$i][3][0],
                  $quads[$i][3][2]
                ],
                4, // num_points
                $color[$color_index] // color
            );
        }

        // Save output file
        $out_file = UP_PATH . 'images' . DS . $addon_id . '_map.png';
        imagepng($image, $out_file);

        return $out_file;
    }

    /**
     * Get the image label of some text, if the image label for that text does not exist, then create it
     *
     * @param string $text the label text
     *
     * @return string the img tag that points to our image text
     */
    public static function getImageLabel($text)
    {
        $path = static::createImageLabel($text);
        return sprintf('<img src="%s" alt="%s" />', $path['dir'] . $path['file'], $text);
    }

    /**
     * @param int|string $size see SIZE_*
     *
     * @return int
     */
    public static function sizeToInt($size)
    {
        $size = (int)$size;
        switch ($size)
        {
            case static::SIZE_SMALL:
                return 25;

            case static::SIZE_MEDIUM:
                return 75;

            case static::SIZE_LARGE:
                return 300;

            default:
                return 100;
        }
    }

    /**
     * @param int|string $number
     *
     * @return int
     */
    public static function intToSize($number)
    {
        $number = (int)$number;
        switch ($number)
        {
            case 25:
                return static::SIZE_SMALL;

            case 75:
                return static::SIZE_MEDIUM;

            case 300:
                return static::SIZE_LARGE;

            default:
                return static::SIZE_DEFAULT;
        }
    }
}
