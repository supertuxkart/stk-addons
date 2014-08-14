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
 * Class to hold all file-related operations
 *
 * @author Stephen
 */
class File
{
    /**
     * @const int
     */
    const IMAGE = 1;

    /**
     * @const int
     */
    const SOURCE = 2;

    /**
     * @const int
     */
    const ADDON = 3;

    /**
     * Approve a file
     *
     * @param int  $file_id
     * @param bool $approve
     *
     * @throws FileException
     *
     * @return int File id, or -1 if file record does not exist
     */
    public static function approve($file_id, $approve = true)
    {
        if (!$approve)
        {
            $approve = false;
        }

        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            throw new FileException(_h('Insufficient permissions to approve a file'));
        }

        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'files`
                SET `approved` = :approve
                WHERE `id` = :file_id',
                DBConnection::NOTHING,
                [
                    ':approve' => (int)$approve,
                    ':file_id' => $file_id
                ],
                [
                    ":approve" => DBConnection::PARAM_INT,
                    ":file_id" => DBConnection::PARAM_INT
                ]
            );
        }
        catch(DBException $e)
        {
            throw new FileException(_h('Failed to change file approval status.'));
        }

        writeAssetXML();
        writeNewsXML();
    }

    /**
     * Check if a file exists (based on record in database file table)
     *
     * @param string $path Relative to upload directory
     *
     * @return int File id, or 0 if file record does not exist
     */
    public static function exists($path)
    {
        try
        {
            $file = DBConnection::get()->query(
                'SELECT `id`
                FROM `' . DB_PREFIX . 'files`
                WHERE `file_path` = :path',
                DBConnection::FETCH_FIRST,
                [':path' => $path]
            );

        }
        catch(DBException $e)
        {
            return 0;
        }

        if (empty($file))
        {
            return 0;
        }

        return $file;
    }

    /**
     * Extract an archive file.
     *
     * @param string $file        the file to extract
     * @param string $destination the directory where to extract
     * @param string $file_ext
     *
     * @throws FileException
     */
    public static function extractArchive($file, $destination, $file_ext = null)
    {
        if (!file_exists($file))
        {
            throw new FileException(_h('The file to extract does not exist.'));
        }

        if (!$file_ext)
        {
            $file_ext = pathinfo($file, PATHINFO_EXTENSION);
        }

        // Extract archive
        switch ($file_ext)
        {
            // Handle archives using ZipArchive class
            case 'zip':
                $archive = new ZipArchive;

                if (!$archive->open($file))
                {
                    throw new FileException(_h('Could not open archive file. It may be corrupted.'));
                }
                if (!$archive->extractTo($destination))
                {
                    throw new FileException(_h('Failed to extract archive file.') . ' (zip)');
                }

                $archive->close();
                unlink($file); // delete file archive from inside folder
                break;

            // Handle archives using Archive_Tar class
            case 'tar':
            case 'tar.gz':
            case 'tgz':
            case 'gz':
            case 'tbz':
            case 'tar.bz2':
            case 'bz2':
                $compression = null;
                if ($file_ext === 'tar.gz' || $file_ext === 'tgz' || $file_ext === 'gz')
                {
                    $compression = 'gz';
                }
                elseif ($file_ext === 'tbz' || $file_ext === 'tar.bz2' || $file_ext === 'bz2')
                {
                    $compression = 'bz2';
                }

                $archive = new Archive_Tar($file, $compression);
                if (!$archive)
                {
                    throw new FileException(_h('Could not open archive file. It may be corrupted.'));
                }
                if (!$archive->extract($destination))
                {
                    throw new FileException(_h('Failed to extract archive file.') . ' (' . $compression . ')');
                }
                unlink($file); // delete file archive from inside folder
                break;

            default:
                throw new FileException(_h('Unknown archive type.'));
        }
    }

    /**
     * Add a directory to a zip archive
     *
     * @param string $directory
     * @param string $out_file
     *
     * @throws FileException
     */
    public static function compress($directory, $out_file)
    {
        $zip = new ZipArchive();
        $filename = $out_file;

        if (file_exists($filename))
        {
            unlink($filename);
        }

        if ($zip->open($filename, ZIPARCHIVE::CREATE) !== true)
        {
            throw new FileException("Cannot open <$filename>");
        }

        // Find files to add to archive
        foreach (scandir($directory) as $file)
        {
            if ($file === ".." || $file === "." || is_dir($directory . $file))
            {
                continue;
            }
            if (!$zip->addFile($directory . $file, $file))
            {
                throw new FileException("Can't add this file: " . $file);
            }
            if (!file_exists($directory . $file))
            {
                throw new FileException("Can't add this file (it doesn't exist): " . $file);
            }
        }

        if (!$zip->close())
        {
            throw new FileException("Can't close the zip");
        }
    }

    /**
     * Reduce the directory tree to a single level
     *
     * @param string $current_dir
     * @param string $destination_dir
     *
     * @throws FileException
     */
    public static function flattenDirectory($current_dir, $destination_dir)
    {
        if (!is_dir($current_dir) || !is_dir($destination_dir))
        {
            throw new FileException(_h('Invalid source or destination directory.'));
        }

        $dir_contents = scandir($current_dir);
        foreach ($dir_contents as $file)
        {
            if ($file === '.' || $file === '..')
            {
                continue;
            }

            if (is_dir($current_dir . $file))
            {
                File::flattenDirectory($current_dir . $file . DS, $destination_dir);
                File::deleteDir($current_dir . $file);
                continue;
            }

            if ($current_dir !== $destination_dir)
            {
                rename($current_dir . $file, $destination_dir . $file);
            }
        }
    }

    /**
     * Check that all image sizes are power of 2 and
     *
     * @param string $path the path to an image
     *
     * @return bool
     */
    public static function imageCheck($path)
    {
        if (!file_exists($path))
        {
            return false;
        }
        if (!is_dir($path))
        {
            return false;
        }

        // Check supported image types
        $image_types = imagetypes();
        $image_file_ext = [];
        if ($image_types & IMG_GIF)
        {
            $image_file_ext[] = 'gif';
        }
        if ($image_types & IMG_PNG)
        {
            $image_file_ext[] = 'png';
        }
        if ($image_types & IMG_JPG)
        {
            $image_file_ext[] = 'jpg';
            $image_file_ext[] = 'jpeg';
        }
        if ($image_types & IMG_WBMP)
        {
            $image_file_ext[] = 'wbmp';
        }
        if ($image_types & IMG_XPM)
        {
            $image_file_ext[] = 'xpm';
        }

        $files = scandir($path);
        foreach ($files as $file)
        {
            // Don't check current and parent directory
            if ($file === '.' || $file === '..' || is_dir($path . $file))
            {
                continue;
            }

            // Make sure the whole path is there
            $file = $path . $file;

            // Don't check files that aren't images
            if (!preg_match('/\.(' . implode('|', $image_file_ext) . ')$/i', $file))
            {
                continue;
            }

            // If we're still in the loop, there is an image to check
            $image_size = getimagesize($file);
            $image_width = $image_size[0];
            $image_height = $image_size[1];

            // Make sure dimensions are powers of 2. By using: num & (num - 1)
            if (($image_width & ($image_width - 1)) || ($image_width <= 0))
            {
                return false;
            }
            if (($image_height & ($image_height - 1)) || ($image_height <= 0))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove invalid files from the path that are not allowed extensions
     *
     * @param string $path
     * @param bool   $source flag that indicates the removal of invalid source extensions
     *
     * @return array of removed file names
     */
    public static function removeInvalidFiles($path, $source = false)
    {
        if (!file_exists($path) || !is_dir($path))
        {
            if (DEBUG_MODE)
            {
                trigger_error(sprintf("%s does not exist or is not a directory"), $path);
            }

            return [];
        }

        // Make a list of approved file types
        if ($source === false)
        {
            $approved_types = Config::get(Config::ALLOWED_ADDON_EXTENSIONS);
        }
        else
        {
            $approved_types = Config::get(Config::ALLOWED_SOURCE_EXTENSIONS);
        }
        $approved_types = Util::commaStringToArray($approved_types);

        $removed_files = [];
        foreach (scandir($path) as $file)
        {
            // Don't check current and parent directory
            if ($file === '.' || $file === '..' || is_dir($path . $file))
            {
                continue;
            }

            // Make sure the whole path is there
            $file = $path . $file;

            // Remove files with unapproved extensions
            if (!preg_match('/\.(' . implode('|', $approved_types) . ')$/i', $file))
            {
                $removed_files[] = basename($file);
                unlink($file);
            }
        }

        if (empty($removed_files))
        {
            return [];
        }

        return $removed_files;
    }

    /**
     * Move an uploaded file to a new destination
     *
     * @param string $from
     * @param string $to
     *
     * @throws FileException
     */
    public static function moveUploadFile($from, $to)
    {
        if (move_uploaded_file($from, $to) === false)
        {
            throw new FileException(_h('Failed to move uploaded file.'));
        }
        if (!file_exists($to))
        {
            throw new FileException('The file was not moved. This should never happen');
        }
    }

    /**
     * Write to a file in the filesystem, safely. Create the file if it does not exist
     *
     * @param string $file the filename in the system
     * @param string $content the content to write
     *
     * @return bool return true on success, false otherwise
     */
    public static function write($file, $content)
    {
        // If file doesn't exist, create it
        if (!file_exists($file))
        {
            if (!touch($file))
            {
                return false;
            }
        }

        $fhandle = fopen($file, 'w');
        if (!$fhandle)
        {
            return false;
        }
        if (!fwrite($fhandle, $content))
        {
            return false;
        }
        fclose($fhandle);

        return true;
    }

    /**
     * Delete a file and its corresponding database record
     *
     * @param int $file_id
     *
     * @return boolean true on success, false otherwise
     */
    public static function delete($file_id)
    {
        // delete from filesystem
        try
        {
            $file = DBConnection::get()->query(
                'SELECT `file_path`
                FROM `' . DB_PREFIX . 'files`
                WHERE `id` = :file_id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                [':file_id' => $file_id],
                [':file_id' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            return false;
        }
        if ($file)
        {
            if (file_exists(UP_PATH . $file['file_path']))
            {
                unlink(UP_PATH . $file['file_path']);
            }
        }

        // delete file from databse
        try
        {
            DBConnection::get()->query(
                'DELETE FROM `' . DB_PREFIX . 'files`
                WHERE `id` = :file_id',
                DBConnection::NOTHING,
                [':file_id' => $file_id],
                [':file_id' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            return false;
        }

        writeAssetXML();
        writeNewsXML();

        return true;
    }

    /**
     * Delete the queued files form the database and from the filesystem
     *
     * @throws FileException
     *
     * @return boolean
     */
    public static function deleteQueuedFiles()
    {
        try
        {
            $queued_files = DBConnection::get()->query(
                'SELECT `id`
                FROM `' . DB_PREFIX . 'files`
                WHERE `delete_date` <= :date
                AND `delete_date` <> \'0000-00-00\'',
                DBConnection::FETCH_ALL,
                [':date' => date('Y-m-d')]
            );
        }
        catch(DBException $e)
        {
            throw new FileException('Failed to read deletion queue.');
        }

        // delete from the filesystem
        foreach ($queued_files as $file)
        {
            if (File::delete($file["id"]))
            {
                print 'Deleted file ' . $file["id"] . "<br />\n";
                Log::newEvent('Processed queued deletion of file ' . $file["id"]);
            }
        }

        return true;
    }

    /**
     * Delete subdirectories of a folder which have not been modified recently
     *
     * @param string $dir
     * @param int    $max_age in milliseconds
     *
     * @return null
     * @throws FileException
     */
    public static function deleteOldSubdirectories($dir, $max_age)
    {
        // Make sure we are looking at a directory
        $dir = rtrim($dir, DS);
        if (!is_dir($dir))
        {
            return null;
        }

        $files = scandir($dir);
        foreach ($files as $file)
        {
            // Check if our item is a subfolder
            if ($file !== '.' && $file !== '..' && is_dir($dir . DS . $file))
            {
                $last_mod = filemtime($dir . DS . $file . DS . '.');

                // Check if our folder is old enough to delete
                if (Util::isOldEnough($last_mod, $max_age))
                {
                    File::deleteDir($dir . DS . $file);
                }
            }
        }
    }

    /**
     * Recursively delete a directory. This does not touch the database.
     *
     * @param string      $dir the directory to delete
     * @param string|null $exclude_regex
     *
     * @throws FileException only in debug mode
     */
    public static function deleteDir($dir, $exclude_regex = null)
    {
        // Make sure we are looking at a directory
        $dir = rtrim($dir, DS);
        if (!is_dir($dir))
        {
            trigger_error(sprintf("%s is not a directory", $dir));

            return;
        }

        $oDir = dir($dir);
        while (($sFile = $oDir->read()) !== false)
        {
            if ($sFile === '.' || $sFile === '..')
            {
                continue;
            }

            if ($exclude_regex && preg_match($exclude_regex, $sFile))
            {
                continue;
            }

            // delete file or directory
            $file_path = $dir . DS . $sFile;
            if (!is_link($file_path) && is_dir($file_path))
            {
                static::deleteDir($file_path);
            }
            else
            {
                unlink($file_path);
            }
        }
        $oDir->close();

        if (!$exclude_regex) // remove root directory only if no exclude regex was given
        {
            rmdir($dir);
        }
    }

    /**
     * Get the addon id based on the file id
     *
     * @param int $file_id
     *
     * @throws FileException
     *
     * @return int|bool the addon id or false otherwise
     */
    public static function getAddon($file_id)
    {
        // Validate input
        if (!is_numeric($file_id))
        {
            return false;
        }
        if ($file_id === 0)
        {
            return false;
        }

        // Look up file path from database
        try
        {
            $addon = DBConnection::get()->query(
                'SELECT `addon_id`
                FROM `' . DB_PREFIX . 'files`
                WHERE `id` = :file_id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                [':file_id' => $file_id],
                [':file_id' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new FileException("Can not retrieve addon a DB error occurred");
        }

        if (empty($addon))
        {
            return false;
        }

        // get the first record
        return $addon['addon_id'];
    }

    /**
     * Get the file path
     *
     * @param int $file_id the id of the file
     *
     * @throws FileException
     *
     * @return string|bool the file path or false otherwise
     */
    public static function getPath($file_id)
    {
        // Validate input
        if (!is_numeric($file_id))
        {
            return false;
        }
        if ($file_id === 0)
        {
            return false;
        }

        // Look up file path from database
        try
        {
            $file = DBConnection::get()->query(
                'SELECT `file_path`
                FROM `' . DB_PREFIX . 'files`
                WHERE `id` = :file_id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                [':file_id' => $file_id],
                [':file_id' => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new FileException("Can not retrieve path a DB error occurred");
        }

        if (empty($file))
        {
            return false;
        }

        // get the first record
        return $file['file_path'];
    }

    /**
     * Get all files from the database and filesystem
     *
     * @return array of all file
     */
    public static function getAllFiles()
    {
        // Look-up all file records in the database
        try
        {
            $db_files = DBConnection::get()->query(
                'SELECT *
                FROM `' . DB_PREFIX . 'files`
                ORDER BY `addon_id` ASC',
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            return false;
        }

        // Look-up all existing files on the disk
        $fs_files = [];
        $folder = UP_PATH;
        $dir_handle = opendir($folder);
        while (false !== ($entry = readdir($dir_handle)))
        {
            if (is_dir($folder . $entry)) // ignore folders
            {
                continue;
            }
            $fs_files[] = $entry;
        }

        $folder = UP_PATH . 'images' . DS;
        $dir_handle = opendir($folder);
        while (false !== ($entry = readdir($dir_handle)))
        {
            if (is_dir($folder . $entry))
            {
                continue;
            }
            $fs_files[] = 'images' . DS . $entry;
        }

        // Loop through database records and remove those entries from the list of files existing on the disk
        $return_files = [];
        foreach ($db_files as $db_file)
        {
            $key = array_search($db_file['file_path'], $fs_files, true);
            if ($key === false) // files does not exist in the database
            {
                $db_file['exists'] = false;
            }
            else // file does exist in the database
            {
                unset($fs_files[$key]); // remove it from fs_files
                $db_file['exists'] = true;
            }
            $return_files[] = $db_file;
        }
        // fs_files now contains only files that do not exist in the database and exist only on disk
        $fs_files = array_values($fs_files); // reset indices

        // add files that exist on the disk but not in the database
        foreach ($fs_files as $file_path)
        {
            $return_files[] = [
                'id'         => false,
                'addon_id'   => false,
                'addon_type' => false,
                'file_type'  => false,
                'file_path'  => $file_path,
                'exists'     => true
            ];
        }

        return $return_files;
    }

    /**
     * Create a new image
     *
     * @param resource $upload_handle
     * @param string   $file_name
     * @param int      $addon_id
     * @param string   $addon_type
     *
     * @throws FileException
     */
    public static function newImage($upload_handle, $file_name, $addon_id, $addon_type)
    {
        if ($upload_handle !== null)
        {
            if (!move_uploaded_file($upload_handle['tmp_name'], UP_PATH . 'images' . DS . $file_name))
            {
                throw new FileException(_h('Failed to move uploaded file.'));
            }
        }
        else
        {
            // Delete the existing image by this name
            if (file_exists(UP_PATH . 'images' . DS . basename($file_name)))
            {
                try
                {
                    DBConnection::get()->query(
                        'DELETE FROM `' . DB_PREFIX . 'files`
                        WHERE `file_path` = :file_name',
                        DBConnection::NOTHING,
                        [":file_name" => 'images/' . basename($file_name)]
                    );
                }
                catch(DBException $e)
                {
                    throw new FileException("Failed to delete an existing image");
                }

                // Clean image cache
                Cache::clearAddon($addon_id);
            }
        }

        // Scan image validity with GD
        $image_path = UP_PATH . 'images' . DS . basename($file_name);
        $image_info = getimagesize($image_path);
        if (!$image_info)
        {
            // Image is not read-able - must be corrupt or otherwise invalid
            unlink($image_path);
            throw new FileException(_h('The uploaded image file is invalid.'));
        }

        $image_max_dimension = Config::get(Config::IMAGE_MAX_DIMENSION);
        $image_width = $image_info[0];
        $image_height = $image_info[1];

        // Validate image size
        if ($image_width > $image_max_dimension || $image_height > $image_max_dimension)
        {
            // Image is too large. Scale it.
            try
            {
                $image = new SImage($image_path);
                $image->scale($image_max_dimension, $image_max_dimension);
                $image->save($image_path);
            }
            catch(SImageException $e)
            {
                throw new FileException($e->getMessage());
            }
        }

        // Add database record for image
        try
        {
            DBConnection::get()->query(
                "CALL `" . DB_PREFIX . "create_file_record`
                (:addon_id, :upload_type, 'image', :file, @result_id)",
                DBConnection::NOTHING,
                [
                    ":addon_id"    => $addon_id,
                    ":upload_type" => $addon_type,
                    ":file"        => 'images/' . basename($file_name)
                ]
            );
        }
        catch(DBException $e)
        {
            unlink($image_path);
            throw new FileException(_h('Failed to associate image file with addon.'));
        }
    }

    /**
     * Create a new image from a quad file
     *
     * @param string $quad_file
     * @param int    $addon_id
     * @param string $addon_type
     *
     * @throws FileException
     */
    public static function newImageFromQuads($quad_file, $addon_id, $addon_type)
    {
        $reader = xml_parser_create();

        // Remove whitespace at beginning and end of file
        $xml_content = trim(file_get_contents($quad_file));

        if (!xml_parse_into_struct($reader, $xml_content, $vals, $index))
        {
            throw new FileException('XML Error: ' . xml_error_string(xml_get_error_code($reader)));
        }

        // Cycle through all of the xml file's elements
        $quads = [];
        foreach ($vals as $val)
        {
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
                    throw new FileException('Unexpected number of points for quad ' . $i . '.');
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

        // Add image record to add-on
        File::newImage(null, basename($out_file), $addon_id, $addon_type);
    }

    /**
     * Mark a file to be deleted by a cron script a day after all clients should
     * have updated their XML files
     *
     * @param int $file_id
     *
     * @return boolean
     */
    public static function queueDelete($file_id)
    {
        $del_date = date('Y-m-d', time() + Config::get(Config::XML_UPDATE_TIME) + Util::SECONDS_IN_A_DAY);
        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'files`
                SET  `delete_date` = :date
                WHERE  `id` = :file_id',
                DBConnection::NOTHING,
                [
                    ":file_id" => $file_id,
                    ":date"    => $del_date
                ],
                [":file_id" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            return false;
        }

        return true;
    }

    /**
     * Modify an the internal link using the apache_rewrites config from the database
     *
     * @param string $link
     *
     * @return string
     */
    public static function rewrite($link)
    {
        // Don't rewrite external links
        if (mb_substr($link, 0, 7) === 'http://' && mb_substr($link, 0, mb_strlen(SITE_ROOT)) !== SITE_ROOT)
        {
            return $link;
        }

        $link = str_replace(SITE_ROOT, null, $link);
        $rules = Config::get(Config::APACHE_REWRITES);
        $rules = preg_split('/(\\r)?\\n/', $rules);

        foreach ($rules as $rule)
        {
            // Check for invalid lines
            if (!preg_match('/^([^\ ]+) ([^\ ]+)( L)?$/i', $rule, $parts))
            {
                continue;
            }

            // Check rewrite regular expression
            $search = '@' . $parts[1] . '@i';
            $new_link = $parts[2];
            if (!preg_match($search, $link, $matches))
            {
                continue;
            }

            $matches_count = count($matches);
            for ($i = 1; $i < $matches_count; $i++)
            {
                $new_link = str_replace('$' . $i, $matches[$i], $new_link);
            }
            $link = $new_link;

            if (isset($parts[3]) && ($parts[3] === ' L'))
            {
                break;
            }
        }

        return SITE_ROOT . $link;
    }

    /**
     * Return an link html element
     *
     * @param string $href
     * @param string $label
     * @param bool   $rewrite
     *
     * @return string
     */
    public static function link($href, $label, $rewrite = true)
    {
        $href = $rewrite ? File::rewrite($href) : $href;

        return '<a href="' . $href . '">' . $label . '</a>';
    }
}
