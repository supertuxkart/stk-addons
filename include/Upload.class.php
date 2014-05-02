<?php

/**
 * copyright 2012 Stephen Just <stephenjust@users.sf.net>
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
require_once(INCLUDE_DIR . 'parsers/b3dParser.class.php');
require_once(INCLUDE_DIR . 'parsers/addonXMLParser.class.php');

class Upload
{
    private $file_name;

    private $file_type;

    private $file_size;

    private $file_tmp;

    private $file_ext;

    private $expected_type;

    private $dest;

    private $temp;

    private $properties = array();

    private $upload_type;

    private $upload_name;

    private $addon_id;


    public function __construct($file_record, $expected_type)
    {
        $this->file_name = $file_record['name'];
        $this->file_type = $file_record['type'];
        $this->file_tmp = $file_record['tmp_name'];
        $this->file_size = $file_record['size'];
        $this->expected_type = $expected_type;

        Upload::readError($file_record['error']);
        $this->file_ext = Upload::checkType();

        $this->temp = TMP . 'uploads/' . time() . '-' . $this->file_name . '/';

        // Clean up old temp files to make room for new upload
        File::deleteOldSubdirectories(TMP . 'uploads', 3600);

        $this->doUpload();
    }

    public function __destruct()
    {
        File::deleteRecursive($this->temp);
    }

    /**
     * Remove all the files from the temporary directory
     */
    public function removeTempFiles()
    {
        File::deleterecursive($this->temp);
    }

    /**
     * Check the filename for an uploaded file to make sure the extension is
     * one that can be handled
     *
     * @throws UploadException if the file extension if not appropriate
     *
     * @return string File extension
     */
    public function checkType()
    {
        // Check file-extension for uploaded file
        if ($this->expected_type === 'image')
        {
            if (!preg_match('/\.(png|jpg|jpeg)$/i', $this->file_name, $ext))
            {
                throw new UploadException(htmlspecialchars(
                        _('Uploaded image files must be either PNG or Jpeg files.')
                ));
            }
        }
        else
        {
            // File extension must be .zip, .tgz, .tar, .tar.gz, tar.bz2, .tbz
            if (!preg_match('/\.(zip|t[bg]z|tar|tar\.gz|tar\.bz2)$/i', $this->file_name, $ext))
            {
                throw new UploadException(htmlspecialchars(_('The file you uploaded was not the correct type.')));
            }
        }

        return $ext[1];
    }

    /**
     * Perform the actual upload to our server
     *
     * @throws UploadException
     */
    private function doUpload()
    {
        if (@!mkdir(
                $this->temp, /* Directory */
                0755, /* Permissions */
                true /* Recursive create */
        )
        )
        {
            throw new UploadException('Failed to create temporary directory for upload: ' .
                    htmlspecialchars($this->temp)
            );
        }
        // Copy file to temp folder
        if ((move_uploaded_file($this->file_tmp, $this->temp . $this->file_name) === false) &&
                !file_exists($this->temp . $this->file_name)
        )
        {
            throw new UploadException(htmlspecialchars(_('Failed to move uploaded file.')));
        }

        if ($this->expected_type === 'image')
        {
            $this->doImageUpload();

            return;
        }

        try
        {
            File::extractArchive($this->temp . $this->file_name, $this->temp, $this->file_ext);
            File::flattenDirectory($this->temp, $this->temp);
            Upload::removeInvalidFiles();
            Upload::parseFiles();
        }
        catch(FileException $e)
        {
            throw new UploadException("File Exception: " . $e);
        }
        catch(ParserException $e)
        {
            throw new UploadException("Parser Exception: " . $e->getMessage());
        }

        // --------------------------------------------------------------------
        // FIXME: This is only a temporary measure!
        // --------------------------------------------------------------------
        if ($this->properties['xml_attributes']['version'] > 5 && $this->upload_type === "tracks")
        {
            throw new UploadException('You uploaded a track with version ' . $this->properties['xml_attributes']['version'] . ' of the track format.<br />'
                    . 'This new format is not yet supported by stkaddons. The stkaddons developer is working on distributing add-ons in a sort of "main package/dependency" manner to save internet bandwidth for users by sharing resources. The developer is using the format change to ensure STK 0.7.x can still access their own addons without disruption.<br />'
                    . 'Thank you for your patience. The developer hopes to have this finished before any "beta" versions of STK 0.8 are released.');
        }

        // Make sure the parser found a license file
        if (!isset($this->properties['license_file']))
        {
            throw new UploadException(htmlspecialchars(
                    _(
                            'A valid License.txt file was not found. Please add a License.txt file to your archive and re-submit it.'
                    )
            ));
        }
        $this->properties['xml_attributes']['license'] =
                htmlentities(file_get_contents($this->properties['license_file'], false));

        // Get addon id from page request if possible
        $addon_id = null;
        if (isset($_GET['name']))
        {
            $addon_id = Addon::cleanId($_GET['name']);
            if (!Addon::exists($addon_id))
            {
                $addon_id = null;
            }
            elseif ($this->expected_type != 'source')
            {
                $addon = new Addon($addon_id);
                $revisions = $addon->getAllRevisions();
                end($revisions);
                $this->properties['addon_revision'] = key($revisions) + 1;
                unset($revisions);
                unset($addon);
            }
        }

        // For source packages
        if ($this->expected_type === 'source')
        {
            if ($addon_id === null)
            {
                throw new UploadException('No add-on id was provided with your source archive.');
            }
            if (!Addon::exists($addon_id))
            {
                throw new UploadException('The add-on you want to add a source file to does not exist');
            }
            if ($this->upload_type === null)
            {
                $this->upload_type = mysql_real_escape_string($_GET['type']);
            }
            $filetype = 'source';
        }
        else
        {
            // For add-on files
            if ($this->upload_type === null)
            {
                throw new UploadException('No add-on information file was found.');
            }

            // Get addon id from XML if we still don't have it
            if (!preg_match('/^[a-z0-9\-]+_?[0-9]*$/i', $addon_id) || $addon_id === null)
            {
                $addon_id = Addon::generateId($this->upload_type, $this->properties['xml_attributes']['name']);
                $this->properties['addon_revision'] = 1;
            }

            Upload::editInfoFile();

            // Get image file
            $image_file = ($this->upload_type == 'karts') ? $this->properties['xml_attributes']['icon-file'] :
                    $this->properties['xml_attributes']['screenshot'];
            $image_file = $this->temp . $image_file;
            if (file_exists($image_file))
            {
                // Get image file extension
                preg_match('/\.([a-z]+)$/i', $image_file, $imageext);

                // Save file
                $fileid = uniqid();
                while (file_exists(UP_LOCATION . 'images/' . $fileid . '.' . $imageext[1]))
                {
                    $fileid = uniqid();
                }
                $this->properties['image_path'] = UP_LOCATION . 'images/' . $fileid . '.' . $imageext[1];
                copy($image_file, $this->properties['image_path']);

                // Record image file in database
                try
                {
                    DBConnection::get()->query(
                            "CALL `" . DB_PREFIX . "create_file_record`
                            (:addon_id, :upload_type, 'image', :file, @result_id)",
                            DBConnection::NOTHING,
                            array(
                                ":addon_id" => $addon_id,
                                ":upload_type"     => $this->upload_type,
                                ":file"     => 'images/' . $fileid . $imageext[1]
                            )
                    );

                    try
                    {
                        $id = DBConnection::get()->query(
                                'SELECT @result_id',
                                DBConnection::FETCH_FIRST
                        );
                        // example taken from
                        // http://stackoverflow.com/questions/118506/stored-procedures-mysql-and-php/4502524#4502524
                        // TODO test it
                        $image_file = $id["@result_id"];
                    }
                    catch(DBException $e)
                    {
                        $image_file = null;
                        if (DEBUG_MODE)
                        {
                            trigger_error("Could not select the return from the procedure", E_ERROR);
                        }
                    }
                }
                catch(DBException $e)
                {
                    echo '<span class="error">' . htmlspecialchars(
                                    _('Failed to associate image file with addon.')
                            ) . mysql_error() . '</span><br />';
                    unlink($this->properties['image_path']);
                    $image_file = null;
                }
            }
            else
            {
                $image_file = null;
            }
            $this->properties['image_file'] = $image_file;

            try
            {
                if (isset($this->properties['quad_file']))
                {
                    File::newImageFromQuads($this->properties['quad_file'], $addon_id, $this->upload_type);
                }
            }
            catch(FileException $e)
            {
                echo '<span class="error">' . $e->getMessage() . '</span><br />';
            }

            $filetype = 'addon';
        }
        $this->addon_id = $addon_id;

        // Validate addon type field
        if (!Addon::isAllowedType($this->upload_type))
        {
            throw new UploadException(htmlspecialchars(_('Invalid add-on type.')));
        }

        // Pack zip file
        $this->dest = UP_LOCATION;
        $this->generateFilename('zip');
        if (!File::compress($this->temp, $this->upload_name))
        {
            throw new UploadException(htmlspecialchars(_('Failed to re-pack archive file.')));
        }

        // Record addon's file in database
        try
        {
            DBConnection::get()->query(
                    'CALL `' . DB_PREFIX . 'create_file_record` ' .
                    "(:addon_it, :upload_type, :file_type, :file, @result_id)",
                    DBConnection::NOTHING,
                    array(
                            ":addon_id"     => $addon_id,
                            ":upload_type"  => $this->upload_type,
                            ":file_type"    => $filetype,
                            ":file"         => basename($this->upload_name)
                    )
            );

            try
            {
                $id = DBConnection::get()->query(
                        'SELECT @result_id',
                        DBConnection::FETCH_FIRST
                );
                $this->properties['xml_attributes']['fileid'] = $id["@result_id"];
            }
            catch(DBException $e)
            {
                $this->properties['xml_attributes']['fileid'] = 0;
                if (DEBUG_MODE)
                {
                    trigger_error("Could not select the return from the procedure", E_ERROR);
                }
            }
        }
        catch(DBException $e)
        {
            unlink($this->upload_name);

            if ($_POST['upload-type'] !== 'source')
            {
                $this->properties['xml_attributes']['fileid'] = 0;
            }

            throw new UploadException(htmlspecialchars(_('Failed to associate archive file with addon.')));
        }

        if ($_POST['upload-type'] === 'source')
        {
            echo htmlspecialchars(_('Successfully uploaded source archive.')) . '<br />';
            echo '<span style="font-size: large"><a href="' . File::rewrite(
                            'addons.php?type=' . $this->upload_type . '&amp;name=' . $this->addon_id
                    ) . '">' . htmlspecialchars(_('Continue.')) . '</a></span><br />';

            return true;
        }

        // Set first revision to be "latest"
        if ($this->properties['addon_revision'] == 1)
        {
            $this->properties['status'] += F_LATEST;
        }

        $this->properties['xml_attributes']['status'] = $this->properties['status'];
        $this->properties['xml_attributes']['image'] = $this->properties['image_file'];
        $this->properties['xml_attributes']['missing_textures'] = $this->properties['missing_textures'];

        try
        {
            if (!Addon::exists($this->addon_id))
            {
                // Check if we were trying to add a new revision
                if ($this->properties['addon_revision'] != 1)
                {
                    throw new UploadException(htmlspecialchars(
                            _('You are trying to add a new revision of an add-on that does not exist.')
                    ));
                }

                $addon = Addon::create($this->upload_type, $this->properties['xml_attributes'], $fileid);
            }
            else
            {
                $addon = new Addon($this->addon_id);
                // Check if we are the original uploader, or a moderator
                if (User::$user_id != $addon->getUploader() && !$_SESSION['role']['manageaddons'])
                {
                    throw new UploadException(htmlspecialchars(
                            _('You do not have the necessary permissions to perform this action.')
                    ));
                }
                $addon->createRevision($this->properties['xml_attributes'], $fileid);
            }
        }
        catch(AddonException $e)
        {
            echo '<span class="error">' . $e->getMessage() . '</span><br />';
        }

        echo htmlspecialchars(
                        _(
                                'Your add-on was uploaded successfully. It will be reviewed by our moderators before becoming publicly available.'
                        )
                ) . '<br /><br />';
        echo '<a href="upload.php?type=' . $this->upload_type . '&amp;name=' . $this->addon_id . '&amp;action=file">' . htmlspecialchars(
                        _('Click here to upload the sources to your add-on now.')
                ) . '</a><br />';
        echo htmlspecialchars(
                        _(
                                '(Uploading the sources to your add-on enables others to improve your work and also ensure your add-on will not be lost in the future if new SuperTuxKart versions are not compatible with the current format.)'
                        )
                ) . '<br /><br />';
        echo '<a href="' . File::rewrite(
                        'addons.php?type=' . $this->upload_type . '&amp;name=' . $this->addon_id
                ) . '">' . htmlspecialchars(_('Click here to view your add-on.')) . '</a><br />';
    }

    /**
     * Perform the actual upload to our server
     *
     * @throws UploadException
     */
    private function doImageUpload()
    {
        try
        {
            $this->dest = UP_LOCATION . 'images/';
            $this->generateFilename();
            $addon_id = Addon::cleanId($_GET['name']);
            $addon_type = $_GET['type'];
            rename($this->temp . $this->file_name, $this->upload_name);
            File::newImage(null, $this->upload_name, $addon_id, $addon_type);
            echo htmlspecialchars(_('Successfully uploaded image.')) . '<br />';
            echo '<span style="font-size: large"><a href="addons.php?type=' . $_GET['type'] . '&amp;name=' . $_GET['name'] . '">' . htmlspecialchars(
                            _('Continue.')
                    ) . '</a></span><br />';

            return true;
        }
        catch(FileException $e)
        {
            throw new UploadException($e->getMessage());
        }
    }

    /**
     * Remove all the files that are considered invalid
     */
    private function removeInvalidFiles()
    {
        // Check for invalid files
        if ($this->expected_type !== 'source')
        {
            $invalid_files = File::typeCheck($this->temp);
        }
        else
        {
            $invalid_files = File::typeCheck($this->temp, true);
        }

        if (is_array($invalid_files) && count($invalid_files) !== 0)
        {
            echo '<span class="warning">' . htmlspecialchars(
                            _(
                                    'Some invalid files were found in the uploaded add-on. These files have been removed from the archive:'
                            )
                    ) . ' ' . htmlspecialchars(implode(', ', $invalid_files)) . '</span><br />';
        }
    }

    /**
     * Generate a random file name for our upload_name attribute
     *
     * @$file_ext string $file_ext
     *
     * @throws UploadException if the destination is not set
     */
    private function generateFilename($file_ext = null)
    {
        if ($file_ext === null)
        {
            $file_ext = $this->file_ext;
        }
        if ($this->dest === null)
        {
            throw new UploadException('A destination has not been set yet');
        }

        $fileid = uniqid();
        while (file_exists($this->dest . $fileid . '.' . $file_ext))
        {
            $fileid = uniqid();
        }

        $this->upload_name = $this->dest . $fileid . '.' . $file_ext;
    }

    /**
     * Parse the b3d files
     */
    private function parseFiles()
    {
        $files = scandir($this->temp);

        // Initialize counters
        $b3d_textures = array();

        // Loop through all files
        foreach ($files as $file)
        {
            if ($file === '.' || $file === '..')
            {
                continue;
            }

            // Parse any B3D models
            if (preg_match('/\.b3d$/i', $file))
            {
                $b3d_parse = new b3dParser();
                $b3d_parse->loadFile($this->temp . $file);
                $b3d_textures = array_merge($b3d_parse->listTextures(), $b3d_textures);
            }

            // Parse any XML files
            if (preg_match('/\.xml/i', $file))
            {
                $xml_parse = new addonXMLParser();
                $xml_parse->loadFile($this->temp . $file);
                $xml_type = $xml_parse->getType();

                if ($xml_type === 'TRACK' || $xml_type === 'KART')
                {
                    $this->properties['xml_attributes'] = $xml_parse->addonFileAttributes();
                    if ($xml_type == 'TRACK')
                    {
                        if ($file !== 'track.xml')
                        {
                            continue;
                        }
                        if ($this->properties['xml_attributes']['arena'] != 'Y')
                        {
                            $this->upload_type = 'tracks';
                        }
                        else
                        {
                            $this->upload_type = 'arenas';
                        }
                    }
                    else
                    {
                        if ($file != 'kart.xml')
                        {
                            continue;
                        }
                        $this->upload_type = 'karts';
                    }
                    $this->properties['addon_file'] = $this->temp . $file;
                    $this->addon_name = $this->properties['xml_attributes']['name'];
                }
                if ($xml_type == 'QUADS')
                {
                    $this->properties['quad_file'] = $this->temp . $file;
                }
            }

            // Mark an existing license file
            if (preg_match('/^license\.txt$/i', $file))
            {
                $this->properties['license_file'] = $this->temp . $file;
            }
        }

        // Initialize the status flag
        $this->properties['status'] = 0;

        // Check to make sure all image dimensions are powers of 2
        if (!File::imageCheck($this->temp))
        {
            echo '<span class="warning">' . htmlspecialchars(
                            _('Some images in this add-on do not have dimensions that are a power of two.')
                    )
                    . ' ' . htmlspecialchars(_('This may cause display errors on some video cards.')) . '</span><br />';
            $this->properties['status'] += F_TEX_NOT_POWER_OF_2;
        }

        // List missing textures
        $this->properties['b3d_textures'] = $b3d_textures;
        $missing_textures = array();
        foreach ($this->properties['b3d_textures'] as $tex)
        {
            if (!file_exists($this->temp . $tex))
            {
                $missing_textures[] = $tex;
            }
        }
        // Remove duplicate values
        $this->properties['missing_textures'] = array_unique($missing_textures, SORT_STRING);
    }

    private function editInfoFile()
    {
        $xml_parse = new addonXMLParser();
        $xml_parse->loadFile($this->properties['addon_file'], true);
        $xml_parse->setAttribute('groups', 'Add-Ons');
        $xml_parse->setAttribute('revision', $this->properties['addon_revision']);
        $xml_parse->writeAttributes();
    }

    /**
     * Read the error code passed and throw an appropriate exception
     *
     * @param int $error_code
     *
     * @throws UploadException
     */
    private static function readError($error_code)
    {
        switch ($error_code)
        {
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
                throw new UploadException(htmlspecialchars(
                        _('There is no TEMP directory to store the uploaded file in.')
                ));
            case UPLOAD_ERR_CANT_WRITE:
                throw new UploadException(htmlspecialchars(_('Unable to write uploaded file to disk.')));
            default:
                throw new UploadException(htmlspecialchars(_('Unknown file upload error.')));
        }
    }
}
