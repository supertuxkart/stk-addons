<?php
/**
 * copyright 2011      Stephen Just <stephenjust@users.sf.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
 * Class to hold all file-related operations
 */
class File extends Base
{
    const IMAGE = 1;

    const SOURCE = 2;

    const ADDON = 3;

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $addon_id;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $date_added;

    /**
     * @var bool
     */
    private $is_approved;

    /**
     * @var int
     */
    private $downloads;

    /**
     * @param array $data the data retrieved from the database
     */
    private function __construct(array $data)
    {
        $this->id = (int)$data["id"];
        $this->addon_id = $data["addon_id"];
        $this->type = (int)$data["type"];
        $this->path = $data["path"];
        $this->date_added = $data["date_added"];
        $this->is_approved = (bool)$data["is_approved"];
        $this->downloads = (int)$data["downloads"];
    }

    /**
     * @return string
     */
    public function getAddonId()
    {
        return $this->addon_id;
    }

    /**
     * @return string
     */
    public function getDateAdded()
    {
        return $this->date_added;
    }

    /**
     * @return int
     */
    public function getDownloads()
    {
        return $this->downloads;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return boolean
     */
    public function isApproved()
    {
        return $this->is_approved;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the full path of the file, can also contain directory names
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get the filename of the file from the path
     * @return string
     */
    public function getFileName()
    {
        return basename($this->path);
    }

    /**
     * Delete the current file from the database and filesystem
     *
     * @param string $parent the parent directory of the file in filesystem
     *
     * @return boolean true on success, false otherwise
     * @throws FileException|FileSystemException
     */
    public function delete($parent = UP_PATH)
    {
        try
        {
            DBConnection::get()->delete(
                "files",
                "`id` = :file_id",
                [':file_id' => $this->id],
                [':file_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new FileException(exception_message_db(_h("delete a file")));
        }

        // delete from filesystem
        FileSystem::removeFile($parent . $this->path);
    }

    /**
     * @return FileException
     */
    public static function getException()
    {
        return new FileException();
    }

    /**
     * Factory method from a id
     *
     * @param int $file_id
     *
     * @return File
     */
    public static function getFromID($file_id)
    {
        if (DEBUG_MODE)
            Assert::integerish($file_id);

        $data = static::getFromField(
            "SELECT * FROM " . DB_PREFIX . "files",
            "id",
            $file_id,
            DBConnection::PARAM_INT,
            _h("The requested file id does not exist.")
        );

        return new File($data);
    }

    /**
     * Factory method from a path
     *
     * @param string $file_path
     *
     * @return File
     */
    public static function getFromPath($file_path)
    {
        $data = static::getFromField(
            "SELECT * FROM " . DB_PREFIX . "files",
            "path",
            $file_path,
            DBConnection::PARAM_STR,
            _h("The requested file path does not exist.")
        );

        return new File($data);
    }

    /**
     * Get all the files of an addon
     *
     * @param string $addon_id
     * @param int    $file_type the type of file or null if we want all files
     *
     * @return File[]
     * @throws FileException
     */
    public static function getAllAddon($addon_id, $file_type = null)
    {
        try
        {
            if ($file_type)
            {
                $files = DBConnection::get()->query(
                    'SELECT * FROM `' . DB_PREFIX . "files` WHERE `addon_id` = :id AND `type` = :type",
                    DBConnection::FETCH_ALL,
                    [
                        ":id"   => $addon_id,
                        ":type" => $file_type
                    ],
                    [":type" => DBConnection::PARAM_INT]
                );
            }
            else
            {
                $files = DBConnection::get()->query(
                    'SELECT * FROM `' . DB_PREFIX . "files` WHERE `addon_id` = :id",
                    DBConnection::FETCH_ALL,
                    [":id" => $addon_id]
                );
            }
        }
        catch (DBException $e)
        {
            throw new FileException(exception_message_db(_h("get all the files of addon")));
        }

        $return = [];
        foreach ($files as $file)
        {
            $return[] = new static($file);
        }

        return $return;
    }

    /**
     * Get all files from the database and filesystem
     * This method will silently fail
     *
     * @throws FileException|FileSystemException
     * @return array of all file
     */
    public static function getAllFiles()
    {
        // Look-up all file records in the database
        try
        {
            $db_files = DBConnection::get()->query(
                'SELECT F.*, FT.`name` as `type_string`, A.`type` as addon_type
                FROM `' . DB_PREFIX . 'files` F
                INNER JOIN ' . DB_PREFIX . 'addons A
                    ON F.`addon_id` =  A.`id`
                INNER JOIN ' . DB_PREFIX . 'file_types FT
                    ON FT.`type` = F.`type`
                ORDER BY `addon_id` ASC',
                DBConnection::FETCH_ALL
            );
        }
        catch (DBException $e)
        {
            // TODO error here
            return [];
        }

        // Look-up all existing files on the disk
        $fs_files = [];
        $folder = UP_PATH;
        foreach (FileSystem::ls($folder) as $entry)
        {
            if (FileSystem::isDirectory($folder . $entry)) // ignore folders
            {
                continue;
            }
            $fs_files[] = $entry;
        }

        $folder = UP_PATH . 'images' . DS;
        foreach (FileSystem::ls($folder) as $entry)
        {
            if (FileSystem::isDirectory($folder . $entry))
            {
                continue;
            }
            $fs_files[] = 'images' . DS . $entry;
        }

        // Loop through database records and remove those entries from the list of files existing on the disk
        $return_files = [];
        foreach ($db_files as $db_file)
        {
            $key = array_search($db_file['path'], $fs_files, true);
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
                'id'          => null,
                'addon_id'    => null,
                'addon_type'  => null,
                'type'        => null,
                'type_string' => null,
                'path'        => $file_path,
                'exists'      => true
            ];
        }

        return $return_files;
    }

    /**
     * Approve a file
     *
     * @param int  $file_id
     * @param bool $approve
     *
     * @throws FileException
     */
    public static function approve($file_id, $approve = true)
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'files`
                SET `is_approved` = :is_approved
                WHERE `id` = :file_id',
                DBConnection::NOTHING,
                [
                    ':is_approved' => $approve,
                    ':file_id'     => $file_id
                ],
                [
                    ":is_approved" => DBConnection::PARAM_BOOL,
                    ":file_id"     => DBConnection::PARAM_INT
                ]
            );
        }
        catch (DBException $e)
        {
            throw new FileException(exception_message_db(_('change file approval status')));
        }
    }

    /**
     * Check if a file exists (based on record in database file table)
     * This method will silently fail
     *
     * @param string $path Relative to upload directory
     *
     * @return bool File id, or false if file record does not exist
     */
    public static function existsDB($path)
    {
        return static::existsField("files", "path", $path, DBConnection::PARAM_STR);
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
        // TODO refactor
        try
        {
            $queued_files = DBConnection::get()->query(
                "SELECT F.`id`
                FROM `" . DB_PREFIX . "files_delete` D
                INNER JOIN " . DB_PREFIX . "files F
                    ON F.id = D.file_id
                WHERE D.`date_delete` <= :date AND D.`date_delete` <> '0000-00-00'",
                DBConnection::FETCH_ALL,
                [':date' => date('Y-m-d')]
            );
        }
        catch (DBException $e)
        {
            throw new FileException(exception_message_db(_('read the deletion queue')));
        }

        // delete from the filesystem
        $message = "";
        foreach ($queued_files as $file)
        {
            try
            {
                static::getFromID($file["id"])->delete();
                $message .= 'Deleted file ' . $file["id"] . "<br />\n";
                StkLog::newEvent('Processed queued deletion of file ' . $file["id"]);
            }
            catch (FileException $e)
            {
                StkLog::newEvent("Failed to delete queued file: " . $file["id"], LogLevel::ERROR);
                continue;
            }
        }

        return $message;
    }

    /**
     * Create a new file in the database table
     *
     * @param int    $addon_id
     * @param int    $file_type
     * @param string $file_path
     *
     * @return int the id of the inserted file
     * @throws FileException
     */
    public static function createFileDB($addon_id, $file_type, $file_path)
    {
        try
        {
            DBConnection::get()->query(
                "CALL `" . DB_PREFIX . "create_file_record`
                (:addon_id, :file_type, :file_path, @result_id)",
                DBConnection::NOTHING,
                [
                    ":addon_id"  => $addon_id,
                    ":file_type" => $file_type,
                    ":file_path" => $file_path
                ]
            );
        }
        catch (DBException $e)
        {
            throw new FileException(exception_message_db(_('create file')));
        }

        try
        {
            $id = DBConnection::get()->query(
                'SELECT @result_id',
                DBConnection::FETCH_FIRST
            );
        }
        catch (DBException $e)
        {
            throw new FileException(exception_message_db(_("select file id from procedure")));
        }

        return (int)$id["@result_id"];
    }

    /**
     * Create a new image in the database and modify it on the filesystem if necessary
     * If an image with a similar name exists it will be deleted
     * If the image is to large it will be scaled down
     *
     * @param string $file_name the name of the image
     * @param int    $addon_id  the addon_id that this image belongs tp
     *
     * @throws FileException|FileSystemException
     */
    public static function createImage($file_name, $addon_id)
    {
        // Delete the existing image by this name
        $file_name = basename($file_name);
        if (FileSystem::exists(UP_PATH . 'images' . DS . $file_name))
        {
            try
            {
                DBConnection::get()->query(
                    'DELETE FROM `' . DB_PREFIX . 'files`
                    WHERE `path` = :file_name',
                    DBConnection::NOTHING,
                    [":file_name" => 'images/' . $file_name]
                );
            }
            catch (DBException $e)
            {
                throw new FileException(exception_message_db(_("delete an existing image")));
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
            FileSystem::removeFile($image_path);
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
            catch (SImageException $e)
            {
                throw new FileException($e->getMessage());
            }
        }

        // Add database record for image
        try
        {
            static::createFileDB($addon_id, static::IMAGE, 'images/' . $file_name);
        }
        catch (FileException $e)
        {
            FileSystem::removeFile($image_path);
            throw new FileException($e->getMessage());
        }
    }

    /**
     * Create a new image from a quad file
     *
     * @param string $quad_file
     * @param int    $addon_id
     *
     * @throws FileException|FileSystemException
     */
    public static function newImageFromQuads($quad_file, $addon_id)
    {
        $reader = xml_parser_create();

        // Remove whitespace at beginning and end of file
        $xml_content = trim(FileSystem::fileGetContents($quad_file));

        if (!xml_parse_into_struct($reader, $xml_content, $vals, $index))
        {
            throw new FileException('XML Error: ' . xml_error_string(xml_get_error_code($reader)));
        }

        // Cycle through all of the xml file's elements
        $quads = [];
        foreach ($vals as $val)
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
        static::createImage($out_file, $addon_id);
    }

    /**
     * Mark a file to be deleted by a cron script a day after all clients should have updated their XML files
     * This method silently fails
     *
     * @param int $file_id
     *
     * @throws FileException
     */
    public static function queueDelete($file_id)
    {
        $del_date = date('Y-m-d', time() + (int)Config::get(Config::XML_UPDATE_TIME) + Util::SECONDS_IN_A_DAY);
        try
        {
            DBConnection::get()->insert(
                "files_delete",
                [
                    ":file_id"     => $file_id,
                    ":date_delete" => $del_date
                ],
                [":file_id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new FileException(exception_message_db("mark file for delete"));
        }
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
        $has_prefix =
            Util::isHTTPS() ? (mb_substr($link, 0, 8) === 'https://') : (mb_substr($link, 0, 7) === 'http://');

        if ($has_prefix && mb_substr($link, 0, mb_strlen(ROOT_LOCATION)) !== ROOT_LOCATION)
        {
            return $link;
        }

        $link = str_replace(ROOT_LOCATION, null, $link);
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

        return ROOT_LOCATION . $link;
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
     * Increment the download number for a file path
     *
     * @param string $file_path
     *
     * @throws FileException
     */
    public static function incrementDownload($file_path)
    {
        try
        {
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . "files`
                SET `downloads` = `downloads` + 1
                WHERE `path` = :path",
                DBConnection::NOTHING,
                [':path' => $file_path]
            );
        }
        catch (DBException $e)
        {
            throw new FileException(exception_message_db("increment the download number"));
        }
    }
}
