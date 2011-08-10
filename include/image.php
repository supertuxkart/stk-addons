<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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

function resizeImage($file)
{
    if (!file_exists(UP_LOCATION.$file))
    {
        $source = imagecreatefrompng(ROOT.'image/notfound.png');
    }
    else
    {
        $imageinfo = getimagesize(UP_LOCATION.$file);
        switch ($imageinfo[2])
        {
            default:
                $source = imagecreatefrompng(ROOT.'image/notfound.png');
                break;
            case IMAGETYPE_PNG:
                $source = imagecreatefrompng(UP_LOCATION.$file);
                break;
            case IMAGETYPE_JPEG:
                $source = imagecreatefromjpeg(UP_LOCATION.$file);
                break;
        }
    }
    // Get length and width of original image
    $width_source = imagesx($source);
    $height_source = imagesy($source);
    if($_GET['type'] == "big")
    {
        $size = 300;
    }
    elseif($_GET['type'] == "medium")
    {
        $size = 75;
    }
    elseif($_GET['type'] == "small")
    {
        $size = 25;
    }
    else
    {
        $size = 100;
    }
    if($width_source > $height_source)
    {
            $width_destination = $size;
            $height_destination = $size*$height_source/$width_source;
    }
    if($width_source <= $height_source)
    {
            $height_destination = $size;
            $width_destination = $size*$width_source/$height_source;
    }
    // Create new canvas
    $destination = imagecreatetruecolor($width_destination, $height_destination);
    
    // Preserve transparency
    imagealphablending($destination, false);
    imagesavealpha($destination, true);
    $transparent_bg = imagecolorallocatealpha($destination, 255, 255, 255, 127);
    imagefilledrectangle($destination, 0, 0, $width_destination, $height_destination, $transparent_bg);
    
    // Resize image
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $width_destination, $height_destination, $width_source, $height_source);
    // Display image
    header("Content-Type: image/png");
    imagepng($destination,NULL,9);
}

function img_label($text)
{
    $write_dir = UP_LOCATION.'temp/';
    $read_dir = DOWN_LOCATION.'temp/';
    $text_noaccent = preg_replace('/\W/s','_',$text);

    if (!file_exists($write_dir.'im_'.$text_noaccent.'.png'))
    {
        $text_size = 11;
        $text_angle = 90;
        $font = ROOT.'include/DejaVuSans.ttf';
        $bbox = imagettfbbox($text_size, $text_angle, $font, $text);

        $min_x = min(array($bbox[0],$bbox[2],$bbox[4],$bbox[6]));
        $max_x = max(array($bbox[0],$bbox[2],$bbox[4],$bbox[6]));
        $min_y = min(array($bbox[1],$bbox[3],$bbox[5],$bbox[7]));
        $max_y = max(array($bbox[1],$bbox[3],$bbox[5],$bbox[7]));

        $width = $max_x - $min_x + 2;
        $height = $max_y - $min_y + 2;

        $image = imagecreatetruecolor($width, $height);

        $bgcolor = imagecolorallocate($image,0,0,0);
        imagecolortransparent($image, $bgcolor);
        $textcolor = imagecolorallocate($image,2,2,2);

        imagettftext($image, $text_size, $text_angle, $width, $height, $textcolor, $font, $text);

        imagepng($image,$write_dir.'im_'.$text_noaccent.'.png');
        imagedestroy($image);
    }
    return '<img src="'.$read_dir.'im_'.$text_noaccent.'.png'.'" alt="'.htmlentities($text).'" />';
}
?>
