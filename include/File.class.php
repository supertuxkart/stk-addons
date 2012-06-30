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

/**
 * Class to hold all file-related operations
 *
 * @author Stephen
 */
class File {
    public static function approve($file_id,$approve = true)
    {
        if ($approve !== true)
            $approve = false;

        if (!$_SESSION['role']['manageaddons'])
            throw new FileException(htmlspecialchars(_('Insufficient permissions.')));

        $approve_query = 'UPDATE `'.DB_PREFIX.'files`
            SET `approved` = '.(int)$approve.'
            WHERE `id` = '.(int)$file_id;
        $approve_handle = sql_query($approve_query);
        if (!$approve_handle)
            throw new FileException('Failed to change file approval status.');
        writeAssetXML();
        writeNewsXML();
    }

    /**
     * Check if a file exists (based on record in database file table)
     * @param string $path Relative to upload directory
     * @return integer File id, or -1 if file record does not exist
     */
    public static function exists($path) {
	$path = mysql_real_escape_string($path);
	$query = 'SELECT `id`
		FROM `'.DB_PREFIX.'files`
		WHERE `file_path` = \''.$path.'\'';
	$handle = sql_query($query);
	if (!$handle)
	    return -1;
	if (mysql_num_rows($handle) == 0)
	    return -1;
	$result = mysql_fetch_array($handle);
	return $result[0];
    }
    
    /**
     * Extract an archive file
     * @param string $file
     * @param string $destination
     * @param string $fileext 
     */
    public static function extractArchive($file,$destination,$fileext = NULL) {
        if (!file_exists($file))
            throw new FileException(htmlspecialchars(_('The file to extract does not exist.')));

        if ($fileext == NULL)
            $fileext = pathinfo($file, PATHINFO_EXTENSION);

        // Extract archive
        switch ($fileext) {
            // Handle archives using ZipArchive class
            case 'zip':
                $archive = new ZipArchive;
                if (!$archive->open($file)) {
                    unlink($file);
                    throw new FileException(htmlspecialchars(_('Could not open archive file. It may be corrupted.')));
                }
                if (!$archive->extractTo($destination)) {
                    unlink($file);
                    throw new FileException(htmlspecialchars(_('Failed to extract archive file.')).' (zip)');
                }
                $archive->close();
                unlink($file);
                break;

            // Handle archives using Archive_Tar class
            case 'tar':
            case 'tar.gz':
            case 'tgz':
            case 'gz':
            case 'tbz':
            case 'tar.bz2':
            case 'bz2':
                require_once('Archive/Tar.php');
                $compression = NULL;
                if ($fileext == 'tar.gz' || $fileext == 'tgz' || $fileext == 'gz') {
                    $compression = 'gz';
                }
                elseif ($fileext == 'tbz' || $fileext == 'tar.bz2' || $fileext == 'bz2') {
                    $compression = 'bz2';
                }
                $archive = new Archive_Tar($file, $compression);
                if (!$archive) {
                    unlink($file);
                    throw new FileException(htmlspecialchars(_('Could not open archive file. It may be corrupted.')));
                }
                if (!$archive->extract($destination)) {
                    unlink($file);
                    throw new FileException(htmlspecialchars(_('Failed to extract archive file.')).' ('.$compression.')');
                }
                unlink($file);
                break;

            default:
                unlink($file);
                throw new FileException(htmlspecialchars(_('Unknown archive type.')));
        }
    }
    
    /**
     * Check a file upload's error code, and provide a useful exception if an
     * error did occur
     * @param integer $error_code 
     */
    public static function checkUploadError($error_code) {
        switch ($error_code)
        {
            default:
                throw new UploadException(htmlspecialchars(_('Unknown file upload error.')));
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                throw new UploadException(htmlspecialchars(_('Uploaded file is too large.')));
            case UPLOAD_ERR_FORM_SIZE:
                throw new UploadException(htmlspecialchars(_('Uploaded file is too large.')));
            case UPLOAD_ERR_PARTIAL:
                throw new UploadException(htmlspecialchars(_('Uploaded file is incomplete.')));
            case UPLOAD_ERR_NO_FILE:
                throw new UploadException(htmlspecialchars(_('No file was uploaded.')));
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new UploadException(htmlspecialchars(_('There is no TEMP directory to store the uploaded file in.')));
            case UPLOAD_ERR_CANT_WRITE:
                throw new UploadException(htmlspecialchars(_('Unable to write uploaded file to disk.')));
        }
    }

    /**
     * Check the filename for an uploaded file to make sure the extension is
     * one that can be handled
     * @param string $filename
     * @param string $type
     * @return string 
     */
    public static function checkUploadExtension($filename,$type = NULL) {
        // Check file-extension for uploaded file
        if ($type == 'image') {
            if (!preg_match('/\.(png|jpg|jpeg)$/i',$filename,$fileext))
                throw new UploadException(htmlspecialchars(_('Uploaded image files must be either PNG or Jpeg files.')));
        } else {
            // File extension must be .zip, .tgz, .tar, .tar.gz, tar.bz2, .tbz
            if (!preg_match('/\.(zip|t[bg]z|tar|tar\.gz|tar\.bz2)$/i',$filename,$fileext))
                throw new UploadException(htmlspecialchars(_('The file you uploaded was not the correct type.')));
        }
        return $fileext[1];
    }

    /**
     * Delete a file and its corresponding database record
     * @param integer $file_id
     * @return boolean
     */
    public static function delete($file_id) {
        // Get file path
        $get_file_query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id.'
            LIMIT 1';
        $get_file_handle = sql_query($get_file_query);
        if (!$get_file_handle)
            return false;
        if (mysql_num_rows($get_file_handle) == 1) {
            $get_file = mysql_fetch_assoc($get_file_handle);
            if (file_exists(UP_LOCATION.$get_file['file_path']))
                unlink(UP_LOCATION.$get_file['file_path']);
        }

        // Delete file record
        $del_file_query = 'DELETE FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id;
        $del_file_handle = sql_query($del_file_query);
        if(!$del_file_handle)
            return false;
        writeAssetXML();
        writeNewsXML();
        return true;
    }

    /**
     * Recursively delete files. This does not touch the database.
     * @param string $dir
     * @param string $exclude_regex
     * @return boolean
     */
    public static function deleteRecursive($dir, $exclude_regex = NULL)
    {
        if (is_dir($dir))
        {
            $dir = rtrim($dir, '/');
            $oDir = dir($dir);
            while (($sFile = $oDir->read()) !== false)
            {
                if ($sFile != '.' && $sFile != '..')
                {
                    if ($exclude_regex !== NULL && preg_match($exclude_regex, $sFile))
                        continue;
                    (!is_link("$dir/$sFile") && is_dir("$dir/$sFile")) ? File::deleteRecursive("$dir/$sFile") : @unlink("$dir/$sFile");
                }
            }
            $oDir->close();
            @rmdir($dir);
            return true;
        }
        return false;
    }
    
    public static function getAddon($file_id) {
        // Validate input
        if (!is_numeric($file_id))
            return false;
        if ($file_id == 0)
            return false;

        // Look up file path from database
        $query = 'SELECT `addon_id` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id.'
            LIMIT 1';
        $handle = sql_query($query);
        if (mysql_num_rows($handle) == 0)
            return false;
        $file = mysql_fetch_assoc($handle);
        return $file['addon_id'];
    }
    
    public static function getPath($file_id) {
        // Validate input
        if (!is_numeric($file_id))
            return false;
        if ($file_id == 0)
            return false;

        // Look up file path from database
        $query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id.'
            LIMIT 1';
        $handle = sql_query($query);
        if (mysql_num_rows($handle) == 0)
            return false;
        $file = mysql_fetch_assoc($handle);
        return $file['file_path'];
    }
    
    public static function getAllFiles() {
        // Look-up all file records in the database
        $files_query = 'SELECT `id`,`addon_id`,`addon_type`,`file_type`,`file_path`
            FROM `'.DB_PREFIX.'files`
            ORDER BY `addon_id` ASC';
        $filesHandle = sql_query($files_query);
        if (!$filesHandle)
            return false;
        
        // Look-up all existing files on the disk
        $files = array();
        $folder = UP_LOCATION;
        $dir_handle = opendir($folder);
        while (false !== ($entry = readdir($dir_handle))) {
            if (is_dir($folder.$entry))
                continue;
            $files[] = $entry;
        }
        $folder = UP_LOCATION.'images/';
        $dir_handle = opendir($folder);
        while (false !== ($entry = readdir($dir_handle))) {
            if (is_dir($folder.$entry))
                continue;
            $files[] = 'images/'.$entry;
        }
        
        // Loop through database records and remove those entries from the list
        // of files existing on the disk
        $return_files = array();
        for ($i = 0; $i < mysql_num_rows($filesHandle); $i++) {
            $files_result = mysql_fetch_assoc($filesHandle);
            $search = array_search($files_result['file_path'],$files);
            if ($search !== false) {
                unset($files[$search]);
                $files_result['exists'] = true;
            } else {
                $files_result['exists'] = false;
            }
            $return_files[] = $files_result;
        }
        // Reset indices
        $files = array_values($files);
        for ($i = 0; $i < count($files); $i++) {
            $return_files[] = array('id' => false,
                'addon_id' => false,
                'addon_type' => false,
                'file_type' => false,
                'file_path' => $files[$i],
                'exists' => true);
        }
        return $return_files;
    }
    
    public static function newImage($upload_handle, $file_name, $addon_id, $addon_type) {
        if ($upload_handle !== NULL) {
            if (!move_uploaded_file($upload_handle['tmp_name'],UP_LOCATION.'images/'.$file_name))
                throw new FileException(htmlspecialchars(_('Failed to move uploaded file.')));
        } else {
            // Delete the existing image by this name
            if (file_exists(UP_LOCATION.'images/'.$file_name)) {
                $query = 'DELETE FROM `'.DB_PREFIX.'files`
                    WHERE `file_path` = \'images/'.$file_name.'\'';
                $handle = sql_query($query);
                // Clean image cache
                Cache::clearAddon($addon_id);
            }
        }

        // Scan image validity with GD
        $image_path = UP_LOCATION.'images/'.$file_name;
        $gdImageInfo = getimagesize($image_path);
        if (!$gdImageInfo) {
            // Image is not read-able - must be corrupt or otherwise invalid
            unlink($image_path);
            throw new FileException(htmlspecialchars(_('The uploaded image file is invalid.')));
        }
        // Validate image size
        if ($gdImageInfo[0] > ConfigManager::get_config('max_image_dimension')
                || $gdImageInfo[1] > ConfigManager::get_config('max_image_dimension')) {
            // Image is too large. Scale it.
            try {
                $image = new SImage($image_path);
                $image->scale(ConfigManager::get_config('max_image_dimension'),
                        ConfigManager::get_config('max_image_dimension'));
                $image->save($image_path);
            }
            catch (ImageException $e) {
                throw new FileException($e->getMessage());
            }
        }
        
        // Add database record for image
        $newImageQuery = 'CALL `'.DB_PREFIX.'create_file_record` '.
            "('$addon_id','$addon_type','image','images/$file_name',@a)";
        $newImageHandle = sql_query($newImageQuery);
        if (!$newImageHandle)
        {
            unlink($image_path);
            throw new FileException(htmlspecialchars(_('Failed to associate image file with addon.')));
        }
    }
    
    public static function newImageFromQuads($quad_file, $addon_id, $addon_type) {
        $reader = xml_parser_create();
        // Remove whitespace at beginning and end of file
        $xmlContents = trim(file_get_contents($quad_file));

        if (!xml_parse_into_struct($reader,$xmlContents,$vals,$index))
            throw new FileException('XML Error: '.xml_error_string(xml_get_error_code($reader)));

        // Cycle through all of the xml file's elements
        $quads = array();
        foreach ($vals AS $val)
        {
            if ($val['type'] == 'close' || $val['type'] == 'comment')
                continue;

            if (isset($val['attributes'])) {
                if (isset($val['attributes']['INVISIBLE']) && $val['attributes']['INVISIBLE'] == 'yes')
                    continue;
                $quads[] = array_values($val['attributes']);
            }
        }

        // Replace references to other quads with proper coordinates
        for($i = 0; $i < count($quads); $i++) {
            for ($j = 0; $j <= 3; $j++) {
                if(preg_match('/^([0-9]+)\:([0-9])$/',$quads[$i][$j],$matches))
                    $quads[$i][$j] = $quads[$matches[1]][$matches[2]];
            }
        }

        // Split coordinates into arrays
        $y_min = NULL;
        $y_max = NULL;
        $x_min = NULL;
        $x_max = NULL;
        $z_min = NULL;
        $z_max = NULL;
        for($i = 0; $i < count($quads); $i++) {
            for ($j = 0; $j <= 3; $j++) {
                $quads[$i][$j] = explode(' ',$quads[$i][$j]);
                if (count($quads[$i][$j]) != 3)
                    throw new FileException('Unexpected number of points for quad '.$i.'.');
                
                // Check max/min y-value
                if ($quads[$i][$j][1] > $y_max || $y_max === NULL)
                    $y_max = $quads[$i][$j][1];
                if ($quads[$i][$j][1] < $y_min || $y_min === NULL)
                    $y_min = $quads[$i][$j][1];
                
                // Check max/min x-value
                if ($quads[$i][$j][0] > $x_max || $x_max === NULL)
                    $x_max = $quads[$i][$j][0];
                if ($quads[$i][$j][0] < $x_min || $x_min === NULL)
                    $x_min = $quads[$i][$j][0];

                // Check max/min x-value
                if ($quads[$i][$j][2] > $z_max || $z_max === NULL)
                    $z_max = $quads[$i][$j][2];
                if ($quads[$i][$j][2] < $z_min || $z_min === NULL)
                    $z_min = $quads[$i][$j][2];
            }
        }

        // Convert y-values to a number from 0-255, and x and z-values to 0-1023
        $y_range = $y_max - $y_min + 1;
        $x_range = $x_max - $x_min + 1;
        $z_range = $z_max - $z_min + 1;
        for($i = 0; $i < count($quads); $i++) {
            for ($j = 0; $j <= 3; $j++) {
                $y = $quads[$i][$j][1] - $y_min;
                $y = $y/$y_range * 255;
                $quads[$i][$j][1] = (int)$y;
                
                $quads[$i][$j][0] = (int)(($quads[$i][$j][0] - $x_min)/$x_range * 1023);
                $quads[$i][$j][2] = (int)(1024 - (($quads[$i][$j][2] - $z_min)/$z_range * 1023));
            }
        }
        
        // Prepare the image
        $image = imagecreatetruecolor(1024,1024);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        // Set up colors
        for ($i = 0; $i <= 255; $i++) {
            $color[$i] = imagecolorallocate($image,(int)($i/1.5),(int)($i/1.5),$i);
        }
        $bg = imagecolorallocatealpha($image,255,255,255,127);
        imagefilledrectangle($image, 0, 0, 1023, 1023, $bg);
        
        // Paint quads
        for ($i = 0; $i < count($quads); $i++) {
            imagefilledpolygon($image,
                    array($quads[$i][0][0],
                        $quads[$i][0][2],
                        $quads[$i][1][0],
                        $quads[$i][1][2],
                        $quads[$i][2][0],
                        $quads[$i][2][2],
                        $quads[$i][3][0],
                        $quads[$i][3][2]),
                    4,
                    $color[(int)(($quads[$i][0][1]
                        + $quads[$i][1][1]
                        + $quads[$i][2][1]
                        + $quads[$i][3][1])
                        /4)]);
        }
        
        // Save output file
        $out_file = UP_LOCATION.'images/'.$addon_id.'_map.png';
        imagepng($image,$out_file);
        
        // Add image record to add-on
        File::newImage(NULL, basename($out_file), $addon_id, $addon_type);
    }
    
    public static function rewrite($link) {
	$link = str_replace(SITE_ROOT, NULL, $link);
	$rules = ConfigManager::get_config('apache_rewrites');
	$rules = preg_split('/(\\r)?\\n/',$rules);
	
	foreach ($rules AS $rule) {
	    // Check for invalid lines
	    if (!preg_match('/^([^\ ]+) ([^\ ]+)( L)?$/i', $rule, $parts)) continue;

	    // Check rewrite regular expression
	    $search = '@'.$parts[1].'@i';
	    $new_link = $parts[2];
	    if (!preg_match($search, $link, $matches)) continue;
	    for ($i = 1; $i < count($matches); $i++) {
		$new_link = str_replace('$'.$i, $matches[$i], $new_link);
	    }
	    $link = $new_link;
	    
	    if (isset($parts[3]) && ($parts[3] == ' L'))
		break;
	}
	
	return SITE_ROOT.$link;
    }
}

?>
