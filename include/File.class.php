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
     * @return boolean
     */
    public static function deleteRecursive($dir)
    {
        if (is_dir($dir))
        {
            $dir = rtrim($dir, '/');
            $oDir = dir($dir);
            while (($sFile = $oDir->read()) !== false)
            {
                if ($sFile != '.' && $sFile != '..')
                {
                    (!is_link("$dir/$sFile") && is_dir("$dir/$sFile")) ? File::deleteRecursive("$dir/$sFile") : unlink("$dir/$sFile");
                }
            }
            $oDir->close();
            rmdir($dir);
            return true;
        }
        return false;
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
}

?>
