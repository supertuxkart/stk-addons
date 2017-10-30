<?php
/**
 * copyright 2012      Stephen Just <stephenjust@users.sf.net>
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
 * Class Upload
 */
class Upload
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
     * @const int
     */
    const REVISION = 4;

    /**
     * The uploaded file name
     * @var string
     */
    private $file_name;

    /**
     * The uploaded file type. eg: application/zip
     * @var string
     */
    private $file_type;

    /**
     * The uploaded file size
     * @var int
     */
    private $file_size;

    /**
     * The uploaded file temporary name path
     * @var string
     */
    private $file_tmp;

    /**
     * The upload file extension
     * @var string
     */
    private $file_ext;

    /**
     * The expected file type
     * @var int
     */
    private $expected_file_type;

    /**
     * The directory we create to extract the archive
     * @var string
     */
    private $temp_file_dir;

    /**
     * The temporary full path to our file
     * @var string
     */
    private $temp_file_fullpath;

    /**
     * Hold the data from the zip archive. b3d files, license, texture etc
     * @var array
     */
    private $properties = [];

    /**
     * The final directory where the file will be stored
     * @var string
     */
    private $upload_file_dir;

    /**
     * The final uploaded name of the file
     * @var string
     */
    private $upload_file_name;

    /**
     * @var int
     */
    private $addon_id;

    /**
     * @var int
     */
    private $addon_type;

    /**
     * Hold the addon moderator message
     * @var string
     */
    private $moderator_message;

    /**
     * Hold the warning messages
     * @var array
     */
    private $warnings = [];

    /**
     * Hold the success messages
     * @var array
     */
    private $success = [];

    /**
     * Constructor
     *
     * @param array  $file_record
     * @param string $addon_name
     * @param int    $addon_type
     * @param int    $expected_type see File::SOURCE, FILE::ADDON
     * @param string $moderator_message
     *
     * @throws UploadException
     */
    public function __construct($file_record, $addon_name, $addon_type, $expected_type, $moderator_message)
    {
        $this->file_name = $file_record['name'];
        $this->file_type = $file_record['type'];
        $this->file_tmp = $file_record['tmp_name'];
        $this->file_size = (int)$file_record['size'];
        $this->expected_file_type = $expected_type;
        $this->moderator_message = $moderator_message;

        // both should be null for when upload a new addon
        $this->addon_id = $addon_name;
        $this->addon_type = $addon_type;

        // validate
        static::checkUploadError($file_record['error']);
        $this->file_ext = static::checkUploadExtension($this->file_name, $this->expected_file_type);

        $STK_UPLOADS_PATH = TMP_PATH . 'stk-uploads';
        $this->temp_file_dir = $STK_UPLOADS_PATH . DS . time() . '-' . $this->file_name . DS;
        $this->temp_file_fullpath = $this->temp_file_dir . $this->file_name;

        try
        {
            // Clean up old temp files to make room for new upload
            if (FileSystem::isDirectory($STK_UPLOADS_PATH))
            {
                FileSystem::deleteOldSubdirectories($STK_UPLOADS_PATH, Util::SECONDS_IN_A_HOUR);
            }

            $this->doUpload();
        }
        catch (ParserException $e)
        {
            throw new UploadException("Parser Exception: " . $e->getMessage());
        }
        catch (FileSystemException $e)
        {
            throw new UploadException("FileSystem Exception: " . $e->getMessage());
        }
        catch (FileException $e)
        {
            throw new UploadException("File Exception: " . $e->getMessage());
        }
        catch (AddonException $e)
        {
            throw new UploadException("Addon Exception: " . $e->getMessage());
        }
    }

    /**
     * Deconstructor, delete the temp file
     */
    public function __destruct()
    {
        try
        {
            $this->removeTempFiles();
        }
        catch (FileSystemException $e)
        {
            throw new UploadException("FileSystem Exception: " . $e->getMessage());
        }
    }

    /**
     * Remove all the files from the temporary directory
     * @throws FileSystemException
     */
    public function removeTempFiles()
    {
        FileSystem::removeDirectory($this->temp_file_dir);
    }

    /**
     * Get the success message
     * @return string
     */
    public function getSuccessMessage()
    {
        return implode("<br>", $this->success);
    }

    /**
     * Get the warning message
     * @return string
     */
    public function getWarningMessage()
    {
        return implode("<br>", $this->warnings);
    }

    /**
     * Perform the actual upload to our server
     *
     * @throws UploadException
     */
    private function doUpload()
    {
        try
        {
            $this->prepareUploadedFiles();
        }
        catch (FileSystemException $e)
        {
            // try to clean up
            if (FileSystem::exists($this->temp_file_fullpath))
            {
                FileSystem::removeFile($this->temp_file_fullpath);
            }
            throw $e;
        }

        // treat images separately
        if ($this->expected_file_type === static::IMAGE)
        {
            $this->upload_file_dir = UP_PATH . 'images' . DS;
            $this->generateUploadFilename($this->file_ext);
            $this->doImageUpload();
        }
        else
        {
            $this->upload_file_dir = UP_PATH;
            $this->generateUploadFilename('zip');
            $this->doArchiveUpload();
        }
    }

    /**
     * Relocate uploaded files to a 'scratch' directory.
     * If the uploaded file is compressed, decompress it.
     * @throws UploadException|FileSystemException
     */
    private function prepareUploadedFiles()
    {
        if (!mkdir($this->temp_file_dir, 0755, true))
        {
            throw new UploadException('Failed to create temporary directory for upload: ' . h($this->temp_file_dir));
        }

        // move file from the php tmp location, to our temp location, where we can perform filesystem operations on it
        static::moveUploadFile($this->file_tmp, $this->temp_file_fullpath); // also does is_uploaded_file

        if ($this->expected_file_type !== static::IMAGE) // archive
        {
            // load the data from the archive, and validate it

            // unarchive it first
            FileSystem::extractFromArchive($this->temp_file_fullpath, $this->temp_file_dir, $this->file_ext);

            // Flatten directories
            // TODO check if we want this :/
            try
            {
                FileSystem::flattenDirectory($this->temp_file_dir);
            }
            catch (FileSystemException $e)
            {
                throw new FileSystemException(
                    sprintf(
                        "Error while trying to flatten the archive. 
                This might happen because files from subdirectories have the same name as files from the root directory. Error = `%s`",
                        $e->getMessage()
                    )
                );
            }

            static::removeInvalidFiles();
            static::parseFiles();
        }
    }

    /**
     * Perform the upload of an image
     * @throws UploadException|FileSystemException|FileException
     */
    private function doImageUpload()
    {
        FileSystem::move($this->temp_file_fullpath, $this->upload_file_dir . $this->upload_file_name);
        File::createImage($this->upload_file_dir . $this->upload_file_name, $this->addon_id);

        $this->success[] = _h('Successfully uploaded image.');
        $this->success[] = StkTemplate::makeHTMLHyperLink(
            Addon::buildPermalink($this->addon_type, $this->addon_id),
            _h('Continue to addon.')
        );
    }

    /**
     * Perform the upload of an archive
     * @throws UploadException
     */
    private function doArchiveUpload()
    {
        // Make sure the parser found a license file, and load it into the xml attributes
        if (empty($this->properties['license_file']))
        {
            throw new UploadException(
                _h('A valid License.txt file was not found. Please add it to your archive and re-submit.')
            );
        }

        if (!$this->addon_type || !Addon::isAllowedType($this->addon_type))
        {
            throw new UploadException(_h('Invalid add-on type.'));
        }

        // For source packages
        if ($this->expected_file_type === static::SOURCE)
        {
            if (!$this->addon_id)
            {
                throw new UploadException(_h('No add-on id was provided with your source archive.'));
            }
            if (!Addon::exists($this->addon_id))
            {
                throw new UploadException(_h('The add-on you want to add a source file to, does not exist'));
            }

            $this->storeUploadArchive(File::SOURCE);

            $this->success[] = _h('Successfully uploaded source archive.');
            $this->success[] = StkTemplate::makeHTMLHyperLink(
                Addon::buildPermalink($this->addon_type, $this->addon_id),
                _h('Continue.')
            );

            return;
        }

        // empty xml files
        if (empty($this->properties['xml_attributes']))
        {
            throw new UploadException(_h("The archive does not contain any addon information"));
        }
        $this->properties['xml_attributes']['license'] =
            h(FileSystem::fileGetContents($this->properties['license_file'], false));

        // new revision
        $addon = null;
        if ($this->expected_file_type === static::REVISION)
        {
            try
            {
                $addon = Addon::get($this->addon_id);
                $this->properties['addon_revision'] = $addon->getMaxRevisionID() + 1;
            }
            catch (AddonException $e)
            {
                throw new UploadException(
                    sprintf(
                        _h("Can not upload revision because the addon with name = '%s' does not exist"),
                        h($this->addon_id)
                    )
                );
            }

            try
            {
                $addon->checkUserEditPermissions();
            }
            catch (AddonException $e)
            {
                throw new UploadException(
                    _h('You do not have the necessary permissions to upload a revision for this addon')
                );
            }
        }
        else // new addon
        {
            if (!User::hasPermission(AccessControl::PERM_ADD_ADDON))
            {
                throw new UploadException(_h('You do not have the necessary permissions to upload a addon'));
            }

            // Get addon id from XML if we still don't have it
            $this->addon_id = Addon::generateId($this->properties['xml_attributes']['name']);
            $this->properties['addon_revision'] = 1;
            $this->properties['status'] += F_LATEST;
        }
        $is_revision = ($this->expected_file_type === static::REVISION && $addon);

        if (!$is_revision) // add new addon
        {
            $addon = Addon::create($this->addon_id, $this->addon_type, $this->properties['xml_attributes']);
        }

        // add additional addon data to database
        $this->editInfoFile();
        $this->storeUploadImage();
        $this->storeUploadQuadFile();
        $this->storeUploadArchive(File::ADDON);

        $this->properties['xml_attributes']['status'] = $this->properties['status'];
        $this->properties['xml_attributes']['image'] = $this->properties['image_file'];
        $this->properties['xml_attributes']['missing_textures'] = $this->properties['missing_textures'];

        // add addon revision to database
        try
        {
            // new revision
            if ($is_revision)
            {
                $addon->createRevision($this->properties['xml_attributes'], $this->moderator_message);
                $addon->sendMailModeratorNewRevision();
            }
            else // new addon
            {
                $addon->createRevisionFirst($this->properties['xml_attributes'], $this->moderator_message);
                $addon->sendMailModeratorNewAddon();
            }
            writeXML();
        }
        catch (AddonException $e)
        {
            // TODO add undo methods for steps above
            throw $e;
        }

        $this->success[] =
            _h(
                'Your add-on was uploaded successfully. It will be reviewed by our moderators before becoming publicly available.'
            );
        $this->success[] =
            '<a href="?type=' . $this->addon_type . '&amp;name=' . $this->addon_id . '&amp;upload-type=source">' .
            _h('Click here to upload the sources to your add-on now.')
            . '</a>';
        $this->success[] = _h(
            '(Uploading the sources to your add-on enables others to improve your work and also ensure your add-on will not be lost in the future if new SuperTuxKart versions are not compatible with the current format.)'
        );
        $this->success[] = StkTemplate::makeHTMLHyperLink(
            Addon::buildPermalink($this->addon_type, $this->addon_id),
            _h('Click here to view your add-on.')
        );
    }

    /**
     * Upload the image file
     * @throws UploadException|FileException|FileSystemException
     */
    private function storeUploadImage()
    {
        // Get image file
        if ($this->addon_type === Addon::KART)
        {
            $image_file = $this->properties['xml_attributes']['icon-file'];
        }
        else
        {
            $image_file = $this->properties['xml_attributes']['screenshot'];
        }
        $image_file = $this->temp_file_dir . $image_file;

        if (!FileSystem::exists($image_file))
        {
            throw new UploadException(
                _h("A screenshot/icon file does not exist in the archive(file name is case sensitive)")
            );
        }

        // Get image file extension
        preg_match('/\.([a-z]+)$/i', $image_file, $image_ext);
        if (count($image_ext) !== 2)
        {
            throw new UploadException(sprintf("The image = '%s', does not have a file extension", h($image_file)));
        }
        $image_ext = $image_ext[1];

        // Save file to local filesystem, TODO maybe find a way to use generateUploadFilename
        $file_id = FileSystem::generateUniqueFileName(UP_PATH . 'images' . DS, $image_ext);
        $image_path = 'images' . DS . $file_id . '.' . $image_ext;
        $this->properties['image_path'] = UP_PATH . $image_path;
        copy($image_file, $this->properties['image_path']);

        // Record image file in database
        try
        {
            $this->properties['image_file'] = File::createFileInDatabase($this->addon_id, File::IMAGE, $image_path);
            if (DEBUG_MODE)
            {
                Assert::true(count(File::getAllAddon($this->addon_id, File::IMAGE)) > 0);
            }
        }
        catch (FileException $e)
        {
            FileSystem::removeFile($this->properties['image_path']);
            throw $e;
        }
    }

    /**
     * Create an image file from the quad file, if it exists
     * @throws FileException|FileSystemException
     */
    private function storeUploadQuadFile()
    {
        if (isset($this->properties['quad_file']))
        {
            File::createImageFromQuadsXML($this->properties['quad_file'], $this->addon_id);
        }
    }

    /**
     * Upload the archive
     *
     * @param int $filetype
     *
     * @throws FileException|FileSystemException|UploadException
     */
    private function storeUploadArchive($filetype)
    {
        // Pack zip file
        try
        {
            FileSystem::compressToArchive($this->temp_file_dir, $this->upload_file_dir . $this->upload_file_name);
        }
        catch (FileSystemException $e)
        {
            throw new UploadException(_h('Failed to re-pack archive file. Reason: ' . $e->getMessage()));
        }

        // Record addon's file in database
        try
        {
            $this->properties['xml_attributes']['file_id'] =
                File::createFileInDatabase($this->addon_id, $filetype, $this->upload_file_name);
        }
        catch (FileException $e)
        {
            FileSystem::removeFile($this->upload_file_dir . $this->upload_file_name);
            throw $e;
        }

        if (DEBUG_MODE)
        {
            Assert::true(count(File::getAllAddon($this->addon_id, $filetype)) > 0);
        }
    }

    /**
     * Remove all the files that are considered invalid
     * @throws FileSystemException
     */
    private function removeInvalidFiles()
    {
        // Check for invalid files
        $path = $this->temp_file_dir;

        $invalid_files = [];
        if (!FileSystem::exists($path) || !FileSystem::isDirectory($path))
        {
            Debug::addMessage(sprintf("%s does not exist or is not a directory", $path));
        }
        else
        {
            $is_source = $this->expected_file_type === static::SOURCE;
            // Make a list of approved file types
            $approved_types = $is_source ? Config::get(Config::ALLOWED_SOURCE_EXTENSIONS) :
                Config::get(Config::ALLOWED_ADDON_EXTENSIONS);
            if (!$approved_types)
            {
                throw new FileSystemException("Can't get config values from database. Should never happen.");
            }

            $approved_types = Util::commaStringToArray($approved_types);
            foreach (FileSystem::ls($path) as $file)
            {
                // Don't check current and parent directory
                if (FileSystem::isDirectory($path . $file))
                {
                    continue;
                }

                // Make sure the whole path is there
                $file = $path . $file;

                // Remove files with unapproved extensions
                if (!preg_match('/\.(' . implode('|', $approved_types) . ')$/i', $file))
                {
                    $invalid_files[] = basename($file);
                    FileSystem::removeFile($file);
                }
            }
        }

        // Remove invalid files from the path that are not allowed extensions
        if ($invalid_files)
        {
            $this->warnings[] = _h(
                                    'Some invalid files were found in the uploaded add-on. These files have been removed from the archive:'
                                )
                                . ' ' . h(implode(', ', $invalid_files));
            Debug::addMessage(Util::array_last($this->warnings));
        }
    }

    /**
     * Generate a random file name for our upload_file_name attribute
     *
     * @param string $file_ext optional param
     *
     * @throws UploadException if the upload directory is not set
     */
    private function generateUploadFilename($file_ext)
    {
        $file_id = FileSystem::generateUniqueFileName($this->upload_file_dir, $file_ext);
        $this->upload_file_name = $file_id . "." . $file_ext;
    }

    /**
     * Parse the b3d, xml, license.txt file
     * Will modify the following keys from the proprieties:
     *  - xml_attributes, addon_file, license_file, quad_file, status, b3d_textures, missing_textures
     *
     * @throws FileSystemException
     */
    private function parseFiles()
    {
        // Initialize counters
        $b3d_textures = [];

        // Loop through all files
        foreach (FileSystem::ls($this->temp_file_dir) as $file)
        {
            // Parse any B3D models
            if (preg_match('/\.b3d$/i', $file))
            {
                $b3d_parse = new B3DParser();
                $b3d_parse->loadFile($this->temp_file_dir . $file);
                $b3d_textures = array_merge($b3d_parse->listTextures(), $b3d_textures);
            }

            // Parse any XML files
            if (preg_match('/\.xml/i', $file))
            {
                $xml_parse = new AddonXMLParser();
                $xml_parse->loadFile($this->temp_file_dir . $file);
                $xml_type = $xml_parse->getType();

                if ($xml_type === 'TRACK' || $xml_type === 'KART')
                {
                    // TODO better validate attributes
                    $this->properties['xml_attributes'] = $xml_parse->addonFileAttributes();

                    if ($xml_type === 'TRACK')
                    {
                        if ($file !== 'track.xml')
                        {
                            continue;
                        }

                        if ($this->properties['xml_attributes']['arena'] === 'Y')
                        {
                            $this->addon_type = Addon::ARENA;
                        }
                        else
                        {
                            $this->addon_type = Addon::TRACK;
                        }
                    }
                    else // kart
                    {
                        if ($file !== 'kart.xml')
                        {
                            continue;
                        }

                        $this->addon_type = Addon::KART;
                    }

                    $this->properties['addon_file'] = $this->temp_file_dir . $file;
                }

                if ($xml_type === 'QUADS')
                {
                    $this->properties['quad_file'] = $this->temp_file_dir . $file;
                }
            }

            // Mark an existing license file
            if (preg_match('/^license\.txt$/i', $file))
            {
                $this->properties['license_file'] = $this->temp_file_dir . $file;
            }
        }

        // Initialize the status flag
        $this->properties['status'] = 0;

        // Check to make sure all image dimensions are powers of 2
        if (!FileSystem::checkImagesAreValid($this->temp_file_dir))
        {
            $this->warnings[] = _h('Some images in this add-on do not have dimensions that are a power of two.') . ' ' .
                                _h('This may cause display errors on some video cards.');
            $this->properties['status'] += F_TEX_NOT_POWER_OF_2;
        }

        // List missing textures
        $this->properties['b3d_textures'] = $b3d_textures;
        $missing_textures = [];
        foreach ($this->properties['b3d_textures'] as $tex)
        {
            if (!FileSystem::exists($this->temp_file_dir . $tex))
            {
                $missing_textures[] = $tex;
            }
        }

        // Remove duplicate values
        $this->properties['missing_textures'] = array_unique($missing_textures, SORT_STRING);
    }

    /**
     * Rewrite the addon file with the revision attribute
     */
    private function editInfoFile()
    {
        $xml_parse = new AddonXMLParser();
        $xml_parse->loadFile($this->properties['addon_file'], true);
        $xml_parse->setAttribute('groups', 'Add-Ons');
        $xml_parse->setAttribute('revision', $this->properties['addon_revision']);
        $xml_parse->writeAttributes();
    }

    /**
     * Move an uploaded file to a new destination
     *
     * @param string $from
     * @param string $to
     *
     * @throws UploadException
     */
    public static function moveUploadFile($from, $to)
    {
        if (move_uploaded_file($from, $to) === false)
        {
            throw new UploadException(_h("Failed to move uploaded file '%s' "), $from);
        }
        if (!FileSystem::exists($to))
        {
            throw new UploadException('The file was not moved. This should never happen!');
        }
    }

    /**
     * Read the error code passed and throw an appropriate exception
     *
     * @param int $error_code
     *
     * @throws UploadException
     */
    public static function checkUploadError($error_code)
    {
        switch ($error_code)
        {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                throw new UploadException(_h('Uploaded file is too large.'));
            case UPLOAD_ERR_FORM_SIZE:
                throw new UploadException(_h('Uploaded file is too large.'));
            case UPLOAD_ERR_PARTIAL:
                throw new UploadException(_h('Uploaded file is incomplete.'));
            case UPLOAD_ERR_NO_FILE:
                throw new UploadException(_h('No file was uploaded.'));
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new UploadException(_h('There is no TEMP directory to store the uploaded file in.'));
            case UPLOAD_ERR_CANT_WRITE:
                throw new UploadException(_h('Unable to write uploaded file to disk.'));
            case UPLOAD_ERR_EXTENSION:
                throw new UploadException(_h('Upload file stopped by extension'));
            default:
                throw new UploadException(_h('Unknown file upload error.') . $error_code);
        }
    }

    /**
     * Check the filename for an uploaded file to make sure the extension is one that can be handled
     *
     * @param string $file_name
     * @param int    $file_type
     *
     * @throws UploadException
     * @return string
     */
    public static function checkUploadExtension($file_name, $file_type)
    {
        // Check file-extension for uploaded file
        if ($file_type === static::IMAGE)
        {
            if (!preg_match('/\.(png|jpg|jpeg)$/i', $file_name, $file_ext))
            {
                throw new UploadException(_h('Uploaded image files must be either PNG or JPEG files.'));
            }
        }
        else // source, addon
        {
            // File extension must be .zip, .tgz, .tar, .tar.gz, tar.bz2, .tbz
            if (!preg_match('/\.(zip|t[bg]z|tar|tar\.gz|tar\.bz2)$/i', $file_name, $file_ext))
            {
                throw new UploadException(
                    _h('The file you uploaded was not the correct type. File extension must one of these types:')
                    . ' .zip, .tgz, .tar, .tar.gz, tar.bz2, .tbz'
                );
            }
        }

        return $file_ext[1];
    }

    /**
     * Get an array of allowed types
     * @return array
     */
    public static function getAllowedTypes()
    {
        return [static::IMAGE, static::SOURCE, static::ADDON, static::REVISION];
    }

    /**
     * Check if the type is allowed
     *
     * @param int $type
     *
     * @return bool
     */
    public static function isAllowedType($type)
    {
        return in_array($type, static::getAllowedTypes(), true);
    }

    /**
     * Return the appropriate upload type for the string provided
     *
     * @param string $string
     *
     * @return int
     */
    public static function stringToType($string)
    {
        switch ($string)
        {
            case 'img':
            case 'image':
                return static::IMAGE;

            case 'src':
            case 'source':
                return static::SOURCE;

            case 'rev':
            case 'revision':
                return static::REVISION;

            default:
                return static::ADDON;
        }
    }
}
