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

function get_self()
{
    $list = get_included_files();
    return $list[0];
}

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
    
    public static function compress($directory, $out_file) {
	$zip = new ZipArchive();
	$filename = $out_file;

	if(file_exists($filename))
	    unlink($filename);

	if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE)
	{
	    echo("Cannot open <$filename>\n");
	    return false;
	}

	// Find files to add to archive
	foreach(scandir($directory) as $file)
	{
	    if($file == ".." || $file == "." || is_dir($directory.$file))
		continue;
	    if(!$zip->addFile($directory.$file, $file)) {
		echo "Can't add this file: ".$file."\n";
		return false;
	    }
	    if(!file_exists($directory.$file))
	    {
		echo "Can't add this file (it doesn't exist): ".$file."\n";
		return false;
	    }
	}

	$succes = $zip->close();
	if(!$succes)
	{
	    echo "Can't close the zip\n";
	    return false;
	}
	return true;
    }
    
    public static function flattenDirectory($current_dir, $destination_dir) {
	if (!is_dir($current_dir) || !is_dir($destination_dir))
	    throw new FileException('Invalid source or destination directory.');
	
	$dir_contents = scandir($current_dir);
	foreach ($dir_contents AS $file) {
	    if (($file == '.') || ($file == '..'))
		continue;
	    if (is_dir($current_dir.$file)) {
		File::flattenDirectory($current_dir.$file.'/', $destination_dir);
		File::deleteRecursive($current_dir.$file);
		continue;
	    }
	    
	    if ($current_dir != $destination_dir)
		rename($current_dir.$file, $destination_dir.$file);
	}
    }

    public static function imageCheck($path)
    {
	if (!file_exists($path))
	    return false;
	if (!is_dir($path))
	    return false;
	// Check supported image types
	$imagetypes = imagetypes();
	$imageFileExts = array();
	if ($imagetypes & IMG_GIF)
	    $imageFileExts[] = 'gif';
	if ($imagetypes & IMG_PNG)
	    $imageFileExts[] = 'png';
	if ($imagetypes & IMG_JPG)
	{
	    $imageFileExts[] = 'jpg';
	    $imageFileExts[] = 'jpeg';
	}
	if ($imagetypes & IMG_WBMP)
	    $imageFileExts[] = 'wbmp';
	if ($imagetypes & IMG_XPM)
	    $imageFileExts[] = 'xpm';


	foreach (scandir($path) AS $file)
	{
	    // Don't check current and parent directory
	    if ($file == '.' || $file == '..' || is_dir($path.$file))
		continue;
	    // Make sure the whole path is there
	    $file = $path.$file;
		    
	    // Don't check files that aren't images
	    if (!preg_match('/\.('.implode('|',$imageFileExts).')$/i',$file))
		continue;

	    // If we're still in the loop, there is an image to check
	    $image_size = getimagesize($file);
	    // Make sure dimensions are powers of 2
	    if (($image_size[0] & ($image_size[0]-1)) || ($image_size[0] <= 0))
		return false;
	    if (($image_size[1] & ($image_size[1]-1)) || ($image_size[1] <= 0))
		return false;
	}
	return true;
    }

    public static function typeCheck($path, $source = false)
    {
	if (!file_exists($path))
	    return false;
	if (!is_dir($path))
	    return false;
	// Make a list of approved file types
	if ($source === false)
	    $approved_types = ConfigManager::get_config('allowed_addon_exts');
	else
	    $approved_types = ConfigManager::get_config('allowed_source_exts');
	$approved_types = explode(',',$approved_types);
	$removed_files = array();

	foreach (scandir($path) AS $file)
	{
	    // Don't check current and parent directory
	    if ($file == '.' || $file == '..' || is_dir($path.$file))
		continue;
	    // Make sure the whole path is there
	    $file = $path.$file;
	    
	    // Remove files with unapproved extensions
	    if (!preg_match('/\.('.implode('|',$approved_types).')$/i',$file))
	    {
		$removed_files[] = basename($file);
		unlink($file);
	    }
	}
	if (count($removed_files) == 0)
	    return true;
	return $removed_files;
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

    public static function deleteQueuedFiles() {
	$date = date('Y-m-d');
	$q = 'SELECT `id`
	    FROM `'.DB_PREFIX.'files`
	    WHERE `delete_date` <= \''.$date.'\'
	    AND `delete_date` <> \'0000-00-00\'';
	$h = sql_query($q);
	if (!$h) throw new Exception('Failed to read deletion queue.');
	
	$num_files = mysql_num_rows($h);
	for ($i = 0; $i < $num_files; $i++) {
	    $r = mysql_fetch_array($h);
	    if (File::delete($r[0])) {
		print 'Deleted file '.$r[0]."<br />\n";
		Log::newEvent('Processed queued deletion of file '.$r[0]);
	    }
	}
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
            if (file_exists(UP_LOCATION.'images/'.basename($file_name))) {
                $query = 'DELETE FROM `'.DB_PREFIX.'files`
                    WHERE `file_path` = \'images/'.basename($file_name).'\'';
                $handle = sql_query($query);
                // Clean image cache
                Cache::clearAddon($addon_id);
            }
        }

        // Scan image validity with GD
        $image_path = UP_LOCATION.'images/'.basename($file_name);
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
            "('$addon_id','$addon_type','image','images/".basename($file_name)."',@a)";
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
		if (isset($val['attributes']['DIRECTION']))
		    unset($val['attributes']['DIRECTION']);
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
    
    /**
     * Mark a file to be deleted by a cron script a day after all clients should
     * have updated their XML files
     * @param integer $file_id
     * @return boolean 
     */
    public static function queueDelete($file_id) {
	$del_date = date('Y-m-d',time() + ConfigManager::get_config('xml_frequency') + (60*60*24));
	$query = 'UPDATE `'.DB_PREFIX.'files`
	    SET  `delete_date` = \''.$del_date.'\'
	    WHERE  `id` = '.(int)$file_id;
	$handle = sql_query($query);
	if (!$handle)
	    return false;
	return true;
    }
    
    public static function rewrite($link) {
	// Don't rewrite external links
	if (substr($link,0,7) == 'http://' && substr($link,0,strlen(SITE_ROOT)) != SITE_ROOT)
	    return $link;

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
    
    public static function link($href, $label) {
	return '<a href="'.File::rewrite($href).'">'.$label.'</a>';
    }
}

?>
