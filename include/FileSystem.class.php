<?php
/**
 * copyright 2016 Daniel Butum <danibutum at gmail dot com>
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
 * Class to hold all file system related operations
 */
class FileSystem
{
    /**
     * Move a file or directory, the same as rename but checks if we are moving to the same path
     *
     * @param string $old_name  the original filename or directory
     * @param string $new_name  the new filename or directory
     * @param bool   $overwrite flag that indicates to overwrite $new_name if it exists
     *
     * @throws FileSystemException
     */
    public static function move($old_name, $new_name, $overwrite = false)
    {
        // validate
        if ($old_name === $new_name)
        {
            throw new FileSystemException('Old name is the same as new name');
        }

        static::rename($old_name, $new_name, $overwrite);
    }

    /**
     * Rename a file or directory
     *
     * @param string $old_name  the original filename or directory
     * @param string $new_name  the new filename or directory
     * @param bool   $overwrite flag that indicates to overwrite $new_name if it exists
     *
     * @throws FileSystemException
     */
    public static function rename($old_name, $new_name, $overwrite = false)
    {
        if (!$overwrite && static::exists($new_name))
        {
            throw new FileSystemException(
                sprintf(
                    'Failed to move file = "%s" to new location = "%s" because it already exists',
                    $old_name,
                    $new_name
                )
            );
        }

        if (@rename($old_name, $new_name) === false)
        {
            throw new FileSystemException(
                sprintf(
                    'Failed to move/rename "%s" to "%s". error = "%s"',
                    $old_name,
                    $new_name,
                    static::getLastErrorString()
                )
            );
        }
    }

    /**
     * Delete a file from the filesystem
     *
     * @param string $file_name  the file to delete
     * @param bool   $check_file flag that indicates to check the file before trying to remove it
     *
     * @throws FileSystemException
     */
    public static function removeFile($file_name, $check_file = true)
    {
        if ($check_file && !static::isFile($file_name) && !static::isSymbolicLink($file_name))
        {
            throw new FileSystemException(sprintf('"%s" is not a file/link or it does not exist', $file_name));
        }

        if (@unlink($file_name) === false)
        {
            throw new FileSystemException(
                sprintf('Failed to delete file = "%s", error = "%s"', $file_name, static::getLastErrorString())
            );
        }

        static::clearStatCache($file_name);
    }

    /**
     * Recursively delete a directory.
     *
     * @param string      $directory       the directory to delete
     * @param bool        $remove_root_dir flag that indicates if it also remove the $directory root level
     * @param string|null $exclude_regex   files that match this regex are excluded from deleting, ONLY matches the
     *                                     root level ones. If $remove_root_dir is true and the regex matches some
     *                                     files, then the root dir won't be removed as there are still some files
     *                                     inside the directory.
     *
     * @throws FileSystemException
     */
    public static function removeDirectory($directory, $remove_root_dir = true, $exclude_regex = null)
    {
        // Make sure we are looking at a directory
        $directory = rtrim($directory, DS);
        if (!static::isDirectory($directory))
        {
            throw new FileSystemException(sprintf('"%s" is not a directory', $directory));
        }

        $found_regex = false;
        $dir_handle = dir($directory);
        while (($file = $dir_handle->read()) !== false)
        {
            if ($file === '.' || $file === '..')
            {
                continue;
            }

            if ($exclude_regex && preg_match($exclude_regex, $file))
            {
                $found_regex = true;
                continue;
            }

            // delete file or directory
            $file_path = $directory . DS . $file;
            if (static::isDirectory($file_path))
            {
                static::removeDirectory($file_path, true, null);
            }
            else
            {
                static::removeFile($file_path);
            }
        }
        $dir_handle->close();

        // only remove if the top directory is empty and told explicitly to delete it
        if ($remove_root_dir && !$found_regex)
        {
            if (@rmdir($directory) === false)
            {
                throw new FileSystemException(
                    sprintf('Failed to remove directory = "%s", error = "%s"', $directory, static::getLastErrorString())
                );
            }
        }
    }

    /**
     * Reduce/flatten the directory tree to a single level
     *
     * @param string $current_dir     the directory to flatten
     * @param string $destination_dir the final destination of the flattened files, it can be another directory, or if
     *                                null it is the $current_dir, so the files are flattened in place
     *
     * @throws FileSystemException
     */
    public static function flattenDirectory($current_dir, $destination_dir = null)
    {
        // use current directory as the destination
        if (!$destination_dir)
        {
            $destination_dir = $current_dir;
        }

        if (!static::isDirectory($current_dir) || !static::isDirectory($destination_dir))
        {
            throw new FileSystemException(_h('Invalid source or destination directory.'));
        }

        foreach (static::ls($current_dir) as $file)
        {
            if (static::isDirectory($current_dir . $file))
            {
                static::flattenDirectory($current_dir . $file . DS, $destination_dir);
                static::removeDirectory($current_dir . $file);
                continue;
            }

            // move to top level
            if ($current_dir !== $destination_dir)
            {
                static::rename($current_dir . $file, $destination_dir . $file);
            }
        }
    }

    /**
     * Opens a file or URL
     *
     * @param string $filename         the filename to open
     * @param string $mode             specifies the type of access you require to the stream
     * @param bool   $use_include_path if true it searches in the include path for the file
     *
     * @return resource the file handle
     * @throws FileSystemException
     */
    public static function fileOpen($filename, $mode, $use_include_path = false)
    {
        $handle = @fopen($filename, $mode, $use_include_path);

        if ($handle === false)
        {
            throw new FileSystemException(
                "Failed to open file = '$filename' with mode = '$mode', error = " . static::getLastErrorString()
            );
        }

        return $handle;
    }

    /**
     * Closes an open file pointer
     *
     * @param resource $handle a file system pointer resource
     *
     * @throws FileSystemException
     */
    public static function fileClose($handle)
    {
        if (fclose($handle) === false)
        {
            throw new FileSystemException("Failed to close file");
        }
    }

    /**
     * Binary-safe file write
     *
     * @param resource $handle a file system pointer resource
     * @param string   $string the string that is to be written.
     * @param null|int $length if the length argument is given, writing will stop after length bytes have been written
     *                         or the end of string is reached, whichever comes first
     *
     * @return int the number of bytes written on success
     * @throws FileSystemException
     */
    public static function fileWrite($handle, $string, $length = null)
    {
        $bytes_written = $length ? fwrite($handle, $string, $length) : fwrite($handle, $string);

        if ($bytes_written === false)
        {
            throw new FileSystemException("Failed to write to file");
        }

        // TODO probably should check if bytes_written < min(len(string), length)
        if ($string && $bytes_written === 0)
        {
            throw new FileSystemException("Failed to write any bytes to the file");
        }

        return $bytes_written;
    }

    /**
     * Gets the size for the given file
     *
     * @param string $filename path to the file
     * @param bool   $check    performs checks on the file
     *
     * @return int returns the size of the file in bytes
     * @throws FileSystemException
     */
    public static function fileSize($filename, $check = true)
    {
        if ($check && !static::exists($filename))
        {
            throw new FileSystemException("Trying to get file size of a file that does not exist, file = '$filename'");
        }
        // TODO maybe check if it is a directory?

        $size = @filesize($filename);
        if ($size === false)
        {
            throw new FileSystemException(
                "Failed to get the file size of file = '$filename', error = " . static::getLastErrorString()
            );
        }

        return $size;
    }

    /**
     * Gets file/directory modification time
     *
     * @param string $filename path to the file
     * @param bool   $check    performs checks on the file
     *
     * @return int returns the time the file was last modified, the time is returned as a Unix timestamp
     * @throws FileSystemException
     */
    public static function fileModificationTime($filename, $check = true)
    {
        if ($check && !static::exists($filename))
        {
            throw new FileSystemException("Trying to get file size of a file that does not exist, file = '$filename'");
        }

        $modification_time = @filemtime($filename);
        if ($modification_time === false)
        {
            throw new FileSystemException(
                "Failed to get the file modification time of file = '$filename', error = " .
                static::getLastErrorString()
            );
        }

        return $modification_time;
    }

    /**
     * Write a string to a file.
     * This function is identical to calling fileOpen(), fileWrite() and fileClose() successively to write data to a
     * file.
     *
     * @param string $filename path to the file where to write the data.
     * @param string $content  the data to write
     * @param int    $flags    the value of flags can be any combination of the following flags at
     *                         https://secure.php.net/manual/en/function.file-put-contents.php
     *
     * @return int the number of bytes written on success
     * @throws FileSystemException
     */
    public static function filePutContents($filename, $content, $flags = 0)
    {
        $bytes_written = file_put_contents($filename, $content, $flags);

        if ($bytes_written === false)
        {
            throw new FileSystemException("Failed to write to file = '$filename'");
        }

        return $bytes_written;
    }

    /**
     * Reads entire file into a string
     *
     * @param string $filename         name of the file to read
     * @param bool   $use_include_path search the file in the include path
     *
     * @return string the read data
     * @throws FileSystemException
     */
    public static function fileGetContents($filename, $use_include_path = false)
    {
        if (!static::exists($filename))
        {
            throw new FileSystemException("Trying to get file contents of a file that does not exist, file = '$filename'");
        }

        $read_data = @file_get_contents($filename, $use_include_path);
        if ($read_data === false)
        {
            throw new FileSystemException(
                "Failed to get contents of file = '$filename', error = " . static::getLastErrorString()
            );
        }

        return $read_data;
    }

    /**
     * Sets access and modification time of file
     *
     * @param string   $filename    the name of the file being touched.
     * @param null|int $time        the touch time. If time is not supplied, the current system time is used.
     * @param null|int $access_time if present, the access time of the given filename is set to the value of atime.
     *                              Otherwise, it is set to the value passed to the time parameter. If neither are
     *                              present, the current system time is used.
     *
     * @throws FileSystemException
     */
    public static function touch($filename, $time = null, $access_time = null)
    {
        if ($time)
        {
            $touch = $access_time ? touch($filename, $time, $access_time) : touch($filename, $time);
        }
        else
        {
            $touch = touch($filename);
        }

        if ($touch === false)
        {
            throw new FileSystemException("Failed to touch file = '$filename'");
        }
    }

    /**
     * Get the directory contents of specified path
     *
     * @param string $path
     * @param bool   $has_dots flag that indicates the output has also the .. and . directory listings
     *
     * @return array of all files and directories
     */
    public static function ls($path, $has_dots = false)
    {
        $files = scandir($path);
        if ($files === false)
        {
            Debug::addException(new FileSystemException(_h('Path is not a directory')));
        }

        return $has_dots ? $files : array_diff($files, ['..', '.']);
    }

    /**
     * Checks whether the path points to a directory
     *
     * @param string $path
     *
     * @return bool returns true if the filename exists and is a directory, false otherwise
     */
    public static function isDirectory($path)
    {
        return @is_dir($path);
    }

    /**
     * Checks whether the filename is a regular file
     *
     * @param string $path
     *
     * @return bool returns true if the filename exists and is a regular file, false otherwise
     */
    public static function isFile($path)
    {
        return @is_file($path);
    }

    /**
     * Checks whether the filename is a symbolic link
     *
     * @param string $path
     *
     * @return bool returns true if the filename exists and is a symbolic link, false otherwise
     */
    public static function isSymbolicLink($path)
    {
        return @is_link($path);
    }

    /**
     * Checks whether a file or directory for that path exists
     *
     * @param string $path             the path/filename/directory to check
     * @param bool   $clear_stat_cache indicates if the stat cache for that file should be cleared before looking
     *
     * @return bool returns true if the file or directory specified by filename exists; false otherwise
     */
    public static function exists($path, $clear_stat_cache = false)
    {
        if ($clear_stat_cache) static::clearStatCache($path);

        return @file_exists($path);
    }

    /**
     * Clears file status cache. This is used if a file that is accessed multiple times in the runtime of a script is
     * in danger of being removed during that scripts operation.
     *
     * @param null|string $filename clear the real path and the stat cache for a specific filename only or null to
     *                              clear all files
     */
    public static function clearStatCache($filename = null)
    {
        if ($filename)
        {
            clearstatcache(true, $filename);
        }
        else
        {
            clearstatcache(false);
        }
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
        // TODO use the same length?
        do
        {
            $filename = uniqid((string)mt_rand());
        } while (static::exists($directory . $filename . '.' . $extension));

        return $filename;
    }

    /**
     * Delete subdirectories of a folder which have not been modified recently
     *
     * @param string $dir
     * @param int    $max_age the max age since modification in milliseconds
     *
     * @throws FileSystemException
     */
    public static function deleteOldSubdirectories($dir, $max_age)
    {
        // Make sure we are looking at a directory
        $dir = rtrim($dir, DS);
        if (!static::isDirectory($dir))
        {
            throw new FileSystemException(sprintf('The path specified is not a directory = "%s"', $dir));
        }

        foreach (static::ls($dir) as $file)
        {
            // Check if our item is a subfolder
            $path = $dir . DS . $file;
            if (static::isDirectory($path))
            {
                $last_mod = static::fileModificationTime($path, false);

                // Check if our folder is old enough to delete
                if (Util::isOldEnough($last_mod, $max_age))
                {
                    static::removeDirectory($path);
                }
            }
        }
    }

    /**
     * Add a directory to a zip archive
     *
     * @param string $directory
     * @param string $filename
     *
     * @throws FileSystemException
     */
    public static function compressToArchive($directory, $filename)
    {
        $zip = new ZipArchive();

        if (static::exists($filename))
        {
            static::removeFile($filename, false);
        }

        if ($zip->open($filename, ZipArchive::CREATE) !== true)
        {
            throw new FileSystemException("Cannot open filename = '$filename'");
        }

        // Find files to add to archive
        foreach (static::ls($directory) as $file)
        {
            if (static::isDirectory($directory . $file))
            {
                continue;
            }

            if (!$zip->addFile($directory . $file, $file))
            {
                throw new FileSystemException("Can't add this file = '$file' to the archive");
            }
            if (!static::exists($directory . $file))
            {
                throw new FileSystemException("Can't add this file = '$file' as it doesn't exist");
            }
        }

        if (!$zip->close())
        {
            throw new FileSystemException("Can't close the zip");
        }
    }

    /**
     * Extract an archive file.
     *
     * @param string $file        the archive file to extract
     * @param string $destination the directory where to extract to
     * @param string $file_ext    the archive extension
     *
     * @throws FileSystemException
     */
    public static function extractFromArchive($file, $destination, $file_ext)
    {
        if (!static::exists($file))
        {
            throw new FileSystemException(sprintf(_h('The file = `%s` to extract does not exist.'), $file));
        }

        // Extract archive
        switch ($file_ext)
        {
            // Handle archives using ZipArchive class
            case 'zip':
                $archive = new ZipArchive;

                if (!$archive->open($file))
                {
                    throw new FileSystemException(_h('Could not open archive file. It may be corrupted.'));
                }
                if (!$archive->extractTo($destination))
                {
                    throw new FileSystemException(_h('Failed to extract archive file.') . ' (zip)');
                }

                $archive->close();
                static::removeFile($file); // delete the file archive from inside folder
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
                else if ($file_ext === 'tbz' || $file_ext === 'tar.bz2' || $file_ext === 'bz2')
                {
                    $compression = 'bz2';
                }

                $archive = new Archive_Tar($file, $compression);

                if (!$archive->extract($destination))
                {
                    throw new FileSystemException(_h('Failed to extract archive file.') . ' (' . $compression . ')');
                }
                static::removeFile($file); // delete file archive from inside folder
                break;

            default:
                throw new FileSystemException(_h('Unknown archive type.'));
        }
    }

    /**
     * Check that all image sizes are power of 2 and that they have the correct MIME type
     *
     * @param string $path the path to an image
     *
     * @return bool     true if all images are valid, false otherwise
     */
    public static function checkImagesAreValid($path)
    {
        if (!static::exists($path))
        {
            return false;
        }
        if (!static::isDirectory($path))
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
        // Can't identify image
        if (empty($image_file_ext))
        {
            return false;
        }

        foreach (static::ls($path) as $file)
        {
            if (static::isDirectory($path . $file))
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
     * Helper function to return the last error string
     * @return mixed
     */
    private static function getLastErrorString()
    {
        $error = error_get_last();

        return DEBUG_MODE ? var_export($error, true) : $error['message'];
    }
}
