<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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
 * Get the current running script path
 *
 * @return string the full path
 */
function get_self()
{
    return $_SERVER["SCRIPT_FILENAME"];
}

/**
 * Output buffer a file and return it's content
 *
 * @param $path
 *
 * @return string
 */
function ob_get_require_once($path)
{
    ob_start();
    require_once($path);
    return  ob_get_clean();
}

/**
 * Get the html purifier config with all settings set
 *
 * @return HTMLPurifier_Config
 */
function getHTMLPurifierConfig()
{
    $config = HTMLPurifier_Config::createDefault();
    $config->set("Core.Encoding", "UTF-8");
    $config->set("Cache.SerializerPath", CACHE_PATH);
    $config->set(
        "HTML.AllowedElements",
        array("h2", "h3", "h4", "h5", "h6", "p", "img", "a", "ol", "li", "ul", "b", "i", "u", "small", "blockquote")
    );
    //$config->set("HTML.AllowedAttributes", array("a.href"));
    $config->set("Attr.AllowedFrameTargets", array("_blank", "_self", "_top", "_parent"));

    return $config;
}

/**
 * @param $nbr
 *
 * @return string
 */
function cryptUrl($nbr)
{
    $str = "";
    $chaine = "abcdefghijklmnpqrstuvwxy";
    srand((double)microtime() * 1000000);
    for ($i = 0; $i < $nbr; $i++)
    {
        $str .= $chaine[rand() % strlen($chaine)];
    }

    return $str;
}

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
    $local_path = UP_PATH . $file;

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
        header('Location: ' . CACHE_LOCATION . '/' . $cache_name);

        return;
    }

    // Start processing the original file
    $image_info = @getimagesize($local_path);
    switch ($image_info[2])
    {
        default:
            $source = imagecreatefrompng(IMG_LOCATION . 'notfound.png');
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
        imagepng($destination, CACHE_PATH . '/' . $cache_name, 9);
        imagepng($destination, null, 9);
    }
    else
    {
        header("Content-Type: image/jpeg");
        imagejpeg($destination, CACHE_PATH . '/' . $cache_name, 90);
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
    $write_dir = UP_PATH . 'temp/';
    $read_dir = DOWNLOAD_LOCATION . 'temp/';
    $text_noaccent = preg_replace('/\W/s', '_', $text);

    if (!file_exists($write_dir . 'im_' . $text_noaccent . '.png'))
    {
        $text_size = 11;
        $text_angle = 90;
        $font = INCLUDE_PATH . 'DejaVuSans.ttf';
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
 * Macro function for htmlspecialchars(_($message))
 *
 * @param string $message
 *
 * @return string
 */
function _h($message)
{
    return htmlspecialchars(_($message));
}


/**
 * Macro function for htmlspecialchar()
 *
 * @param $message
 *
 * @return string
 */
function h($message)
{
    return htmlspecialchars($message);
}

/**
 * @param string $subject
 * @param string $message_html
 *
 * @return null
 */
function moderator_email($subject, $message_html)
{
    $mail_address = ConfigManager::getConfig('list_email');
    if (empty($mail_address))
    {
        echo '<span class="warning">' .
            _h('No moderator mailing-list email is set.')
            . '</span><br />';

        return null;
    }

    $boundary = "-----=" . md5(rand());
    $header = "From: \"STK-Addons Administrator\" <" . ConfigManager::getConfig('admin_email') . ">\n"
        . "Reply-to: \"STK-Addons Administrator\" <" . ConfigManager::getConfig('admin_email') . ">\n"
        . "MIME-Version: 1.0\n"
        . "Content-Type: multipart/alternative;\n boundary=\"$boundary\"\n";
    $message = "\n--" . $boundary . "\n"
        . "Content-Type: text/html; charset=\"ISO-8859-1\"\n"
        . "Content-Transfer-Encoding: 8bit\n"
        . "\n" . $message_html . "\n"
        . "\n--" . $boundary . "--\n"
        . "\n--" . $boundary . "--\n";
    mail($mail_address, $subject, $message, $header);
}
