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
    imagepng($destination);
}

function img_label($text)
{
    $write_dir = UP_LOCATION.'temp/';
    $read_dir = DOWN_LOCATION.'temp/';

    if (!file_exists($write_dir.'im_'.$text.'.png'))
    {
        $length = strlen($text);

        $font = 3;
        $height = imagefontwidth($font) * $length;
        $width = imagefontheight($font);

        $image = imagecreate($width,$height);

        $x = imagesx($image) - $width;
        $y = imagesy($image) - $height;
        $bgcolor = imagecolorallocate($image,200,200,200);
        $textcolor = imagecolorallocate($image,0,0,0);
        imagecolortransparent($image,$bgcolor);
        imagestringup($image,$font,0,$height - 1,$text,$textcolor);
        imagepng($image,$write_dir.'im_'.$text.'.png');
        imagedestroy($image);
    }
    return '<img src="'.$read_dir.'im_'.htmlentities($text).'.png'.'" alt="'.htmlentities($text).'" />';
}
?>
