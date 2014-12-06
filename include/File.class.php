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
    public static function existsDB($path)
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
     * @param string $file_ext    the archive extension
     *
     * @throws FileException
     */
    public static function extractArchive($file, $destination, $file_ext)
    {
        if (!file_exists($file))
        {
            throw new FileException(_h('The file to extract does not exist.'));
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
                static::deleteFileFS($file); // delete file archive from inside folder
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
                static::deleteFileFS($file); // delete file archive from inside folder
                break;

            default:
                throw new FileException(_h('Unknown archive type.'));
        }
    }

    /**
     * Add a directory to a zip archive
     *
     * @param string $directory
     * @param string $filename
     *
     * @throws FileException
     */
    public static function compress($directory, $filename)
    {
        $zip = new ZipArchive();


        if (file_exists($filename))
        {
            static::deleteFileFS($filename, false);
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
                static::flattenDirectory($current_dir . $file . DS, $destination_dir);
                static::deleteDir($current_dir . $file);
                continue;
            }

            if ($current_dir !== $destination_dir)
            {
                static::move($current_dir . $file, $destination_dir . $file);
            }
        }
    }

    /**
     * Check that all image sizes are power of 2 and that they have the correct MIME type
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
                static::deleteFileFS($file);
            }
        }

        return $removed_files;
    }

    /**
     * Move a file or directory
     *
     * @param string $old_name
     * @param string $new_name
     * @param bool   $overwrite flat that indicates to overwrite $new_name if it exists
     *
     * @throws FileException
     */
    public static function move($old_name, $new_name, $overwrite = true)
    {
        // validate
        if ($old_name === $new_name)
        {
            throw new FileException("Old name is the same as new name");
        }
        if (!$overwrite && file_exists($new_name))
        {
            throw new FileException(sprintf(
                "Failed to move file '%s 'to new location('%s') because it already exists",
                $old_name,
                $new_name
            ));
        }

        if (rename($old_name, $new_name) === false)
        {
            throw new FileException(sprintf("Failed to move file '%s' to '%s'.", $old_name, $new_name));
        }
    }


    /**
     * Write to a file in the filesystem, safely. Create the file if it does not exist
     *
     * @param string $file    the filename in the system
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
     * Delete a file from the filesystem and the database
     *
     * @param int $file_id
     *
     * @return boolean true on success, false otherwise
     */
    public static function deleteFile($file_id)
    {
        // delete file from database
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

        // delete from filesystem
        if ($file)
        {
            if (file_exists(UP_PATH . $file['file_path']))
            {
                static::deleteFileFS(UP_PATH . $file['file_path'], false);
            }
        }

        writeAssetXML();
        writeNewsXML();

        return true;
    }

    /**
     * Delete a file from the filesystem
     *
     * @param string $file_name  the file to delete
     * @param bool   $check_file flag that indicates to check the file before trying to remove it
     *
     * @throws FileException
     */
    public static function deleteFileFS($file_name, $check_file = true)
    {
        if ($check_file && !is_file($file_name) && !is_link($file_name))
        {
            throw new FileException(sprintf("'%s' is not a file/link", $file_name));
        }

        if (unlink($file_name) === false)
        {
            throw new FileException(sprintf("Failed to delete file '%s'", $file_name));
        }
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
            if (static::deleteFile($file["id"]))
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
            if ($file === "." || $file === "..")
            {
                continue;
            }

            // Check if our item is a subfolder
            if (is_dir($dir . DS . $file))
            {
                $last_mod = filemtime($dir . DS . $file . DS . '.');

                // Check if our folder is old enough to delete
                if (Util::isOldEnough($last_mod, $max_age))
                {
                    static::deleteDir($dir . DS . $file);
                }
            }
        }
    }

    /**
     * Recursively delete a directory. This does not touch the database.
     *
     * @param string      $dir           the directory to delete
     * @param string|null $exclude_regex files that match this regex are excluded and if this is not
     *                                   null
     *
     * @throws FileException
     */
    public static function deleteDir($dir, $exclude_regex = null)
    {
        // Make sure we are looking at a directory
        $dir = rtrim($dir, DS);
        if (!is_dir($dir))
        {
            throw new FileException(sprintf("'%s' is not a directory", $dir));
        }

        $directory = dir($dir);
        while (($file = $directory->read()) !== false)
        {
            if ($file === '.' || $file === '..')
            {
                continue;
            }

            if ($exclude_regex && preg_match($exclude_regex, $file))
            {
                continue;
            }

            // delete file or directory
            $file_path = $dir . DS . $file;
            if (is_dir($file_path))
            {
                static::deleteDir($file_path);
            }
            else
            {
                static::deleteFileFS($file_path);
            }
        }
        $directory->close();

        if (!$exclude_regex) // remove root directory only if no exclude regex was given
        {
            if (rmdir($dir) === false)
            {
                throw new FileException(sprintf("Failed to remove '%s' directory", $dir));
            }
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
     * @param string $file_name  the name of the image
     * @param int    $addon_id   the addon_id that this image belongs tp
     * @param string $addon_type the addon type
     *
     * @throws FileException
     */
    public static function createNewImage($file_name, $addon_id, $addon_type)
    {
        // Delete the existing image by this name
        $file_name = basename($file_name);
        if (file_exists(UP_PATH . 'images' . DS . $file_name))
        {
            try
            {
                DBConnection::get()->query(
                    'DELETE FROM `' . DB_PREFIX . 'files`
                    WHERE `file_path` = :file_name',
                    DBConnection::NOTHING,
                    [":file_name" => 'images/' . $file_name]
                );
            }
            catch(DBException $e)
            {
                throw new FileException("Failed to delete an existing image");
            }

            // Clean image cache
            Cache::clearAddon($addon_id);
        }

        // Scan image validity with GD
        $image_path = UP_PATH . 'images' . DS . $file_name;
        $image_info = getimagesize($image_path);
        if (!$image_info)
        {
            // Image is not read-able - must be corrupt or otherwise invalid
            static::deleteFileFS($image_path);
            throw new FileException(_h('The uploaded image file is invalid.'));
        }

        $image_max_dimension = (int)Config::get(Config::IMAGE_MAX_DIMENSION);
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
                    ":file"        => 'images/' . $file_name
                ]
            );
        }
        catch(DBException $e)
        {
            static::deleteFileFS($image_path);
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
                $image,             // image
                [                   // points
                  $quads[$i][0][0],
                  $quads[$i][0][2],
                  $quads[$i][1][0],
                  $quads[$i][1][2],
                  $quads[$i][2][0],
                  $quads[$i][2][2],
                  $quads[$i][3][0],
                  $quads[$i][3][2]
                ],
                4,                   // num_points
                $color[$color_index] // color
            );
        }

        // Save output file
        $out_file = UP_PATH . 'images' . DS . $addon_id . '_map.png';
        imagepng($image, $out_file);

        // Add image record to add-on
        static::createNewImage($out_file, $addon_id, $addon_type);
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
        $del_date = date('Y-m-d', time() + (int)Config::get(Config::XML_UPDATE_TIME) + Util::SECONDS_IN_A_DAY);
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
     * @param bool   $tab_index flag to add it to tab index
     *
     * @return string
     */
    public static function link($href, $label, $rewrite = true, $tab_index = true)
    {
        $href = $rewrite ? static::rewrite($href) : $href;
        $tab_string = $tab_index ? "" : " tabindex=\"-1\"";

        return sprintf('<a href="%s"%s>%s</a>', $href, $tab_string, $label);
    }

    /**
     * Generate a unique file name for a file in directory
     *
     * @param string $directory the directory where the file resides
     * @param string $extension the extension of the file
     *
     * @return string the unique file name in directory
     */
    public static function generateUniqueFileName($directory, $extension)
    {
        do
        {
            $filename = uniqid(mt_rand());
        } while (file_exists($directory . $filename . '.' . $extension));

        return $filename;
    }
}
