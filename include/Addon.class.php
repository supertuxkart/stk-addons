<?php
/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *                2014 Daniel Butum <danibutum at gmail dot com>
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
 * Class Addon
 */
class Addon
{
    /**
     * @var array
     */
    protected static $allowedTypes = array('karts', 'tracks', 'arenas');

    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $uploaderId;

    /** The addon creation date
     * @var string
     */
    protected $creationDate;

    /**
     * @var string
     */
    protected $designer;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected $license;

    /**
     * @var int
     */
    protected $minInclude;

    /**
     * @var int
     */
    protected $maxInclude;

    /**
     * @var int
     */
    protected $image = 0;

    /**
     * @var int
     */
    protected $icon = 0;

    /**
     * @var string
     */
    protected $permalink;

    /**
     * @var array
     */
    protected $revisions = array();

    /**
     * @var
     */
    protected $latestRevision;


    /**
     * Instance constructor
     *
     * @param string $id
     *
     * @throws AddonException
     */
    public function __construct($id)
    {
        $this->id = static::cleanId($id);

        // get addon data
        try
        {
            $addon = DBConnection::get()->query(
                'SELECT *
                FROM `' . DB_PREFIX . 'addons`
                WHERE `id` = :id',
                DBConnection::FETCH_FIRST,
                array(':id' => (string)$this->id)
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to read the requested add-on\'s information.'));
        }

        if (empty($addon))
        {
            throw new AddonException(_h('The requested add-on does not exist.'));
        }

        $this->type = $addon['type'];
        $this->name = $addon['name'];
        $this->uploaderId = $addon['uploader'];
        $this->creationDate = $addon['creation_date'];
        $this->designer = $addon['designer'];
        $this->description = $addon['description'];
        $this->license = $addon['license'];
        $this->permalink = SITE_ROOT . 'addons.php?type=' . $this->type . '&amp;name=' . $this->id;
        $this->minInclude = $addon['min_include_ver'];
        $this->maxInclude = $addon['max_include_ver'];

        // get revisions
        try
        {
            $revisions = DBConnection::get()->query(
                'SELECT *
                FROM `' . DB_PREFIX . $this->type . '_revs`
                WHERE `addon_id` = :id
                ORDER BY `revision` ASC',
                DBConnection::FETCH_ALL,
                array(':id' => $this->id)
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to read the requested add-on\'s revision information.'));
        }

        if (empty($revisions))
        {
            throw new AddonException(_h('No revisions of this add-on exist. This should never happen.'));
        }

        foreach ($revisions as $rev)
        {
            $currentRev = array(
                'file'           => $rev['fileid'],
                'format'         => $rev['format'],
                'image'          => $rev['image'],
                'icon'           => (isset($rev['icon'])) ? $rev['icon'] : 0,
                'moderator_note' => $rev['moderator_note'],
                'revision'       => $rev['revision'],
                'status'         => $rev['status'],
                'timestamp'      => $rev['creation_date']
            );
            if ($currentRev['status'] & F_LATEST)
            {
                $this->latestRevision = $rev['revision'];
                $this->image = $rev['image'];
                $this->icon = (isset($rev['icon'])) ? $rev['icon'] : 0;
            }
            $this->revisions[$rev['revision']] = $currentRev;
        }
    }

    /**
     * Create an add-on revision
     *
     * @param array  $attributes
     * @param string $file_id
     *
     * @throws AddonException
     */
    public function createRevision($attributes, $file_id)
    {
        global $moderator_message;
        foreach ($attributes['missing_textures'] as $tex)
        {
            $moderator_message .= "Texture not found: $tex\n";
            echo '<span class="warning">' . h(
                    sprintf(_('Texture not found: %s'), $tex)
                ) . '</span><br />';
        }

        // Check if logged in
        if (!User::isLoggedIn())
        {
            throw new AddonException(_h('You must be logged in to create an add-on revision.'));
        }

        // Make sure an add-on file with this id does not exist
        try
        {
            $rows = DBConnection::get()->query(
                'SELECT * FROM ' . DB_PREFIX . $this->type . '_revs WHERE `id` = :id',
                DBConnection::ROW_COUNT,
                array(':id' => (string)$file_id)
            );
            if ($rows)
            {
                throw new AddonException(_h('The file you are trying to create already exists.'));
            }
        }
        catch(DBException $e)
        {
            throw new AddonException(sprintf('Failed to acces the %s_revs table.', $this->type));
        }

        // Make sure user has permission to upload a new revision for this add-on
        if (User::getLoggedId() !== $this->uploaderId && !User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            throw new AddonException(_h('You do not have the necessary permissions to perform this action.'));
        }

        // Update the addon name
        $this->setName($attributes['name']);

        // Update license file record
        $this->setLicense($attributes['license']);

        // Prevent duplicate images from being created.
        $images = $this->getImageHashes();

        // Compare with new image
        $new_image = File::getPath($attributes['image']);
        $new_hash = md5_file(UP_PATH . $new_image);
        $images_count = count($images);
        for ($i = 0; $i < $images_count; $i++)
        {
            // Skip image that was just uploaded
            if ($images[$i]['id'] === $attributes['image'])
            {
                continue;
            }

            if ($new_hash === $images[$i]['hash'])
            {
                File::delete($attributes['image']);
                $attributes['image'] = $images[$i]['id'];
                break;
            }
        }

        // Calculate the next revision number
        $highest_rev = max(array_keys($this->revisions));
        $rev = $highest_rev + 1;

        // Add revision entry
        $fields_data = array(
            ":id" => $file_id,
            ":addon_id" => $this->id,
            ":fileid" => $attributes['fileid'],
            ":revision" => $rev,
            ":format" => $attributes['version'],
            ":image" => $attributes['image'],
            ":status" => $attributes['status']
        );

        if ($this->type === 'karts')
        {
            $fields_data[":icon"] =  $attributes['image'];
        }

        // Add moderator message if available
        if (!empty($moderator_message))
        {
            $fields_data[":moderator_note"] = $moderator_message;
        }

        try
        {
            DBConnection::get()->insert($this->type . '_revs', $fields_data);
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to create add-on revision.'));
        }

        // Send mail to moderators
        moderator_email(
            'New Addon Upload',
            sprintf(
                "%s has uploaded a new revision for %s '%s' %s",
                User::getLoggedUserName(),
                $this->type,
                $attributes['name'],
                (string)$this->id
            )
        );
        writeAssetXML();
        writeNewsXML();
        Log::newEvent("New add-on revision for '{$attributes['name']}'");
    }

    /**
     * Delete an add-on record and all associated files and ratings
     *
     * @throws AddonException
     */
    public function delete()
    {
        if (!User::isLoggedIn())
        {
            throw new AddonException(_h('You must be logged in to perform this action.'));
        }

        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && User::getLoggedId() !== $this->uploaderId)
        {
            throw new AddonException(_h('You do not have the necessary permissions to perform this action.'));
        }

        // Remove cache files for this add-on
        Cache::clearAddon($this->id);

        // Remove files associated with this addon
        try
        {
            $files = DBConnection::get()->query(
                'SELECT *
                FROM `' . DB_PREFIX . "files`
                WHERE `addon_id` = :id",
                DBConnection::FETCH_ALL,
                array(
                    ":id" => $this->id
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to find files associated with this addon.'));
        }

        foreach ($files as $file)
        {
            if (file_exists(UP_PATH . $file['file_path']) && !unlink(UP_PATH . $file['file_path']))
            {
                echo '<span class="error">' . _h('Failed to delete file:') . ' ' . $file['file_path'] . '</span><br>';
            }
        }

        // Remove file records associated with addon
        try
        {
            DBConnection::get()->query(
                'DELETE FROM `' . DB_PREFIX . 'files`
                WHERE `addon_id` = :id',
                DBConnection::NOTHING,
                array(
                    ":id" => $this->id
                )
            );
        }
        catch(DBException $e)
        {
            echo '<span class="error">' . _h('Failed to remove file records for this addon.') . '</span><br>';
        }

        // Remove addon entry
        // FIXME: The two queries above should be included with this one
        // in a transaction, or database constraints added so that the two
        // queries above are no longer needed.
        try
        {
            DBConnection::get()->query(
                'DELETE FROM `' . DB_PREFIX . 'addons`
                WHERE `id` = :id',
                DBConnection::NOTHING,
                array(':id' => $this->id)
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to remove addon.'));
        }

        writeAssetXML();
        writeNewsXML();
        Log::newEvent("Deleted add-on '{$this->name}'");
    }

    /**
     * Delete a file by id
     *
     * @param int $file_id
     *
     * @throws AddonException
     */
    public function deleteFile($file_id)
    {
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && $this->uploaderId !== User::getLoggedId())
        {
            throw new AddonException(_h('You do not have the necessary permissions to perform this action.'));
        }

        if (!File::delete($file_id))
        {
            throw new AddonException(_h('Failed to delete file.'));
        }
    }

    /**
     * Delete a revision by id
     *
     * @param int $rev
     *
     * @throws AddonException
     */
    public function deleteRevision($rev)
    {
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && $this->uploaderId !== User::getLoggedId())
        {
            throw new AddonException(_h('You do not have the necessary permissions to perform this action.'));
        }
        $rev = (int)$rev;
        if ($rev < 1 || !isset($this->revisions[$rev]))
        {
            throw new AddonException(_h('The revision you are trying to delete does not exist.'));
        }
        if (count($this->revisions) === 1)
        {
            throw new AddonException(_h('You cannot delete the last revision of an add-on.'));
        }
        if (($this->revisions[$rev]['status'] & F_LATEST))
        {
            throw new AddonException(
                _h(
                    'You cannot delete the latest revision of an add-on. Please mark a different revision to be the latest revision first.'
                )
            );
        }

        // Queue addon file for deletion
        if (!File::queueDelete($this->revisions[$rev]['file']))
        {
            throw new AddonException(_h('The add-on file could not be queued for deletion.'));
        }

        // Remove the revision record from the database
        try
        {
            DBConnection::get()->query(
                'DELETE FROM `' . DB_PREFIX . $this->type . '_revs`
                WHERE `addon_id` = :id AND `revision` = :revision',
                DBConnection::NOTHING,
                array(
                    ':addon_id' => $this->id,
                    ':revision' => $rev
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('The add-on revision could not be deleted.'));
        }

        Log::newEvent('Deleted revision ' . $rev . ' of \'' . $this->name . '\'');
        writeAssetXML();
        writeNewsXML();
    }

    /**
     * Get the id of the addon
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the id of image if $icon is set or the id of the icon
     *
     * @param bool $icon
     *
     * @return int
     */
    public function getImage($icon = false)
    {
        if ($icon === false)
        {
            return $this->image;
        }

        return $this->icon;
    }

    /**
     * @return string
     */
    public function getCreationDate()
    {
        return $this->creationDate;
    }

    /**
     * @return int
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return int
     */
    public function getMaxInclude()
    {
        return $this->maxInclude;
    }

    /**
     * @return int
     */
    public function getMinInclude()
    {
        return $this->minInclude;
    }

    /**
     * @return string
     */
    public function getPermalink()
    {
        return $this->permalink;
    }

    /**
     * @return array
     */
    public function getRevisions()
    {
        return $this->revisions;
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Get the addon designed if known
     *
     * @return string
     */
    public function getDesigner()
    {
        if ($this->designer === null)
        {
            return _h('Unknown');
        }

        return $this->designer;
    }

    /**
     * Get all the revisions
     *
     * @return array
     */
    public function getAllRevisions()
    {
        return $this->revisions;
    }

    /**
     * Get the last revision
     *
     * @return string
     */
    public function getLatestRevision()
    {
        return $this->revisions[$this->latestRevision];
    }

    /**
     * Get the current status of the latest revision
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->revisions[$this->latestRevision]['status'];
    }

    /**
     * Get the addon type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the addon name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the license text
     *
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * Get the html link
     *
     * @return string
     */
    public function getLink()
    {
        // Don't rewrite here, because we might be editing the URL later
        return $this->permalink;
    }

    /**
     * Get the id of the uploader
     *
     * @return int the id of the uploader
     */
    public function getUploaderId()
    {
        return $this->uploaderId;
    }

    /**
     * Get the minimum stk version that this addon supports
     *
     * @return string
     */
    public function getIncludeMin()
    {
        return $this->minInclude;
    }

    /**
     * Get the maximum stk version that this addon supports
     *
     * @return string
     */
    public function getIncludeMax()
    {
        return $this->maxInclude;
    }

    /**
     * Get the path to the requested add-on file
     *
     * @param integer $revision Revision number
     *
     * @throws AddonException
     *
     * @return string File path relative to the asset directory
     */
    public function getFile($revision)
    {
        if (!is_int($revision))
        {
            throw new AddonException(_h('An invalid revision was provided.'));
        }

        // Look up file ID
        try
        {
            $file_id_lookup = DBConnection::get()->query(
                'SELECT `fileid`
                FROM `' . DB_PREFIX . $this->type . '_revs`
                WHERE `addon_id` = :addon_id
                AND `revision` = :revision',
                DBConnection::FETCH_FIRST,
                array(
                    ':addon_id' => $this->id,
                    ':revision' => $revision
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to look up file ID'));
        }

        if (empty($file_id_lookup))
        {
            throw new AddonException(_h('There is no add-on found with the specified revision number.'));
        }

        $file_id = $file_id_lookup['fileid'];

        // Look up file path from database
        try
        {
            $file = DBConnection::get()->query(
                'SELECT `file_path` FROM `' . DB_PREFIX . 'files`
                WHERE `id` = :id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                array(
                    ':id' => $file_id,
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to search for the file in the database.'));
        }

        if (empty($file))
        {
            throw new AddonException(_h('The requested file does not have an associated file record.'));
        }

        return $file['file_path'];
    }

    /**
     * Get the md5sums of all the image files of this addon
     *
     * @throws AddonException
     *
     * @return array
     */
    public function getImageHashes()
    {
        try
        {
            $paths = DBConnection::get()->query(
                "SELECT `id`, `file_path`
                FROM `" . DB_PREFIX . "files`
                WHERE `addon_id` = :addon_id
                AND `file_type` = 'image'
                LIMIT 50",
                DBConnection::FETCH_ALL,
                array(
                    ':addon_id' => $this->id,
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('DB error when fetching images associated with this add-on.'));
        }

        $return = array();
        foreach ($paths as $path)
        {
            $return[] = array(
                'id'   => $path['id'],
                'path' => $path['file_path'],
                'hash' => md5_file(UP_PATH . $path['file_path'])
            );
        }

        return $return;
    }

    /**
     * Get the image files associated with this addon
     *
     * @return array
     */
    public function getImages()
    {
        try
        {
            $result = DBConnection::get()->query(
                'SELECT * FROM `' . DB_PREFIX . 'files`
                WHERE `addon_id` = :addon_id
                AND `file_type` = :file_type',
                DBConnection::FETCH_ALL,
                array(
                    ':addon_id'  => (string)$this->id,
                    ':file_type' => 'image'
                )
            );
        }
        catch(DBException $e)
        {
            return array();
        }

        return $result;
    }

    /**
     * Get all of the source files associated with an addon
     *
     * @return array
     */
    public function getSourceFiles()
    {
        try
        {
            $result = DBConnection::get()->query(
                'SELECT * FROM `' . DB_PREFIX . 'files`
                WHERE `addon_id` = :addon_id
                AND `file_type` = :file_type',
                DBConnection::FETCH_ALL,
                array(
                    ':addon_id'  => (string)$this->id,
                    ':file_type' => (string)'source'
                )
            );
        }
        catch(DBException $e)
        {
            return array();
        }

        return $result;
    }

    /**
     * Set the add-on's description
     *
     * @param string $description
     *
     * @throws AddonException
     */
    public function setDescription($description)
    {
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && $this->uploaderId !== User::getLoggedId())
        {
            throw new AddonException(_h('You do not have the neccessary permissions to perform this action.'));
        }

        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'addons`
                 SET `description` = :description
                 WHERE `id` = :id',
                DBConnection::NOTHING,
                array(
                    ':description' => h($description),
                    ':id'          => $this->id
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to update the description record for this add-on.'));
        }

        writeAssetXML();
        writeNewsXML();
        $this->description = $description;
    }

    /**
     * Set the add-on's designer
     *
     * @param string $designer
     *
     * @throws AddonException
     */
    public function setDesigner($designer)
    {
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && $this->uploaderId !== User::getLoggedId())
        {
            throw new AddonException(_h('You do not have the neccessary permissions to perform this action.'));
        }

        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'addons`
                SET `description` = :description
                WHERE `id` = :id',
                DBConnection::NOTHING,
                array(
                    ':designer' => h($designer),
                    ':id'       => $this->id
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to update the designer record for this add-on.'));
        }

        writeAssetXML();
        writeNewsXML();
        $this->designer = $designer;
    }

    /**
     * Set the image for the latest revision of this add-on.
     *
     * @param integer $image_id
     * @param string  $field
     *
     * @throws AddonException
     */
    public function setImage($image_id, $field = 'image')
    {
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && $this->uploaderId !== User::getLoggedId())
        {
            throw new AddonException(_h('You do not have the neccessary permissions to perform this action.'));
        }

        try
        {
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . $this->type . "_revs`
                SET `" . $field . "` = :image_id
                WHERE `addon_id` = :addon_id
                AND `status` & " . F_LATEST,
                DBConnection::NOTHING,
                array(
                    ':image_id' => $image_id,
                    ':addon_id' => $this->id
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to update the image record for this add-on.'));
        }
    }

    /**
     * @param $start_ver
     * @param $end_ver
     *
     * @throws AddonException
     */
    public function setIncludeVersions($start_ver, $end_ver)
    {
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            throw new AddonException(_h('You do not have the neccessary permissions to perform this action.'));
        }

        try
        {
            Validate::versionString($start_ver);
            Validate::versionString($end_ver);
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'addons`
                SET `min_include_ver` = :start_ver, `max_include_ver` = :end_ver
                WHERE `id` = :addon_id',
                DBConnection::NOTHING,
                array(
                    ':addon_id'  => (string)$this->id,
                    ':start_ver' => (string)$start_ver,
                    ':end_ver'   => (string)$end_ver
                )
            );
            writeAssetXML();
            writeNewsXML();
            $this->minInclude = $start_ver;
            $this->maxInclude = $end_ver;
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('An error occurred while setting the min/max include versions.'));
        }
    }

    /**
     * Set the license of this addon
     *
     * @param string $license
     *
     * @throws AddonException
     */
    public function setLicense($license)
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'addons`
                SET `license` = :license,
                WHERE `id` = :addon_id',
                DBConnection::NOTHING,
                array(
                    ':license'  => $license,
                    ':addon_id' => (string)$this->id
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to update the license record for this add-on.'));
        }

        $this->license = $license;
    }

    /**
     * Set the name of this addon
     *
     * @param string $name
     *
     * @throws AddonException
     */
    public function setName($name)
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'addons`
                SET `name` = :name,
                WHERE `id` = :addon_id',
                DBConnection::NOTHING,
                array(
                    ':name'     => $name,
                    ':addon_id' => (string)$this->id
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to update the name record for this add-on.'));
        }

        $this->name = $name;
    }

    /**
     * Set notes on this addon
     *
     * @param string $fields
     *
     * @throws AddonException
     */
    public function setNotes($fields)
    {
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            throw new AddonException(_h('You do not have the neccessary permissions to perform this action.'));
        }

        $fields = explode(',', $fields);
        $notes = array();
        foreach ($fields as $field)
        {
            if (!isset($_POST[$field]))
            {
                $_POST[$field] = null;
            }
            $fieldinfo = explode('-', $field);
            $revision = (int)$fieldinfo[1];
            // Update notes
            $notes[$revision] = $_POST[$field];
        }

        // Save record in database
        foreach ($notes as $revision => $value)
        {
            try
            {
                DBConnection::get()->query(
                    'UPDATE `' . DB_PREFIX . $this->type . '_revs`
                    SET `moderator_note` = :moderator_note
                    WHERE `addon_id` = :addon_id
                    AND `revision` = :revision',
                    DBConnection::NOTHING,
                    array(
                        ':moderator_note' => $value,
                        ':addon_id'       => $this->id,
                        ':revision'       => $revision
                    )
                );
            }
            catch(DBException $e)
            {
                throw new AddonException(_h('Failed to write add-on status.'));
            }
        }

        // Generate email
        $email_body = null;
        $notes = array_reverse($notes, true);
        foreach ($notes as $revision => $value)
        {
            $email_body .= "\n== Revision $revision ==\n";
            $value = strip_tags(str_replace('\r\n', "\n", $value));
            $email_body .= "$value\n\n";
        }

        // Get uploader email address
        try
        {
            $user = DBConnection::get()->query(
                'SELECT `name`,`email`
                FROM `' . DB_PREFIX . 'users`
                WHERE `id` = :user_id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                array(
                    ':user_id' => $this->uploaderId,
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Failed to find user record.'));
        }

        try
        {
            $mail = new SMail;
            $mail->addonNoteNotification($user['email'], $this->id, $email_body);
        }
        catch(Exception $e)
        {
            throw new AddonException('Failed to send email to user. ' . $e->getMessage());
        }

        Log::newEvent("Added notes to '{$this->name}'");
    }

    /**
     * Check if any of an addon's revisions have been approved
     *
     * @return bool
     */
    public function hasApprovedRevision()
    {
        foreach ($this->revisions as $rev)
        {
            if ($rev['status'] & F_APPROVED)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the status flags of an addon
     *
     * @param string $fields
     *
     * @throws AddonException
     */
    public function setStatus($fields)
    {
        $fields = explode(',', $fields);

        // Initialise the status field to its present values
        // (Remove any checkboxes that the user could have checked)
        $status = array();
        foreach ($this->revisions as $rev_n => $rev)
        {
            $mask = F_LATEST + F_ALPHA + F_BETA + F_RC;
            if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
            {
                $mask = $mask + F_APPROVED + F_INVISIBLE + F_DFSG + F_FEATURED;
            }

            $status[$rev_n] = ($rev['status'] & ~$mask);
        }

        // Iterate through each field
        foreach ($fields as $field)
        {
            if (!isset($_POST[$field]))
            {
                $_POST[$field] = null;
            }
            if ($field === 'latest')
            {
                $field_info = array('', (int)$_POST['latest']);
            }
            else
            {
                $field_info = explode('-', $field);
            }

            // Initialize the status of the current revision if it has
            // not been created yet.
            if (!isset($status[$field_info[1]]))
            {
                $status[$field_info[1]] = 0;
            }

            // Mark the "latest" revision
            if ($field === 'latest')
            {
                $status[(int)$_POST['latest']] += F_LATEST;
                continue;
            }

            // Update status values for all flags
            if ($_POST[$field] === 'on')
            {
                $revision = (int)$field_info[1];
                switch ($field_info[0])
                {
                    case 'approved':
                        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
                        {
                            break;
                        }
                        $status[$revision] += F_APPROVED;
                        break;

                    case 'invisible':
                        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
                        {
                            break;
                        }
                        $status[$revision] += F_INVISIBLE;
                        break;

                    case 'alpha':
                        $status[$revision] += F_ALPHA;
                        break;

                    case 'beta':
                        $status[$revision] += F_BETA;
                        break;

                    case 'rc':
                        $status[$revision] += F_RC;
                        break;

                    case 'dfsg':
                        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
                        {
                            break;
                        }
                        $status[$revision] += F_DFSG;
                        break;

                    case 'featured':
                        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
                        {
                            break;
                        }
                        $status[$revision] += F_FEATURED;
                        break;

                    default:
                        break;
                }
            }
        }

        // Loop through each addon revision
        foreach ($status as $revision => $value)
        {
            // Write new addon status
            try
            {
                DBConnection::get()->query(
                    'UPDATE `' . DB_PREFIX . $this->type . '_revs`
                    SET `status` = :status
                    WHERE `addon_id` = :addon_id
                    AND `revision` = :revision',
                    DBConnection::NOTHING,
                    array(
                        ':status'   => $value,
                        ':addon_id' => $this->id,
                        ':revision' => $revision
                    )
                );
            }
            catch(DBException $e)
            {
                throw new AddonException(_h('Failed to write add-on status.'));
            }
        }

        writeAssetXML();
        writeNewsXML();
        Log::newEvent("Set status for add-on '{$this->name}'");
    }

    /**
     * Factory method for the addon
     *
     * @param string $id
     *
     * @return static
     */
    public static function get($id)
    {
        return new static($id);
    }

    /**
     * Get the addon name
     *
     * @param int $id
     *
     * @return string empty string on error
     */
    public static function getNameByID($id)
    {
        $id = static::cleanId($id);

        try
        {
            $addon = DBConnection::get()->query(
                'SELECT `name`
                FROM `' . DB_PREFIX . 'addons`
                WHERE `id` = :id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                array(':id' => $id)
            );
        }
        catch(DBException $e)
        {
            return "";
        }

        if (empty($addon))
        {
            // silently fail
            return "";
        }

        return $addon['name'];
    }

    /**
     * Get the addon type
     *
     * @param int $id
     *
     * @return string empty string on error
     */
    public static function getTypeByID($id)
    {
        $id = static::cleanId($id);

        try
        {
            $addon = DBConnection::get()->query(
                'SELECT `type`
                FROM `' . DB_PREFIX . 'addons`
                WHERE `id` = :id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                array(':id' => $id)
            );
        }
        catch(DBException $e)
        {
            return "";
        }

        if (empty($addon))
        {
            // silently fail
            return "";
        }

        return $addon['type'];
    }

    /**
     * Check if the type is allowed
     *
     * @param string $type
     *
     * @return bool true if allowed and false otherwise
     */
    public static function isAllowedType($type)
    {
        return in_array($type, static::$allowedTypes);
    }


    /**
     * Get an array of allowed types
     *
     * @return array
     */
    public static function getAllowedTypes()
    {
        return static::$allowedTypes;
    }

    /**
     * Perform a cleaning operation on the id
     *
     * @param string $id what we want to clean
     *
     * @return string|bool
     */
    public static function cleanId($id)
    {
        if (!is_string($id))
        {
            return false;
        }

        $length = strlen($id);
        if ($length === 0)
        {
            return false;
        }
        $id = strtolower($id);

        // Validate all characters in addon id
        // Rather than using str_replace, and removing bad characters,
        // it makes more sense to only allow certain characters
        for ($i = 0; $i < $length; $i++)
        {
            $substr = substr($id, $i, 1);
            if (!preg_match('/^[a-z0-9\-_]$/i', $substr))
            {
                $substr = '-';
            }
            $id = substr_replace($id, $substr, $i, 1);
        }

        return $id;
    }

    /**
     * Search for an addon by its name or description
     *
     * @param string $search_query
     * @param bool   $search_description search also in description
     *
     * @throws AddonException
     * @return array Matching addon id, name and type
     */
    public static function search($search_query, $search_description = true)
    {
        try
        {

            $query =  "SELECT * FROM `" . DB_PREFIX . "addons` WHERE `name` LIKE :search_query";

            if($search_description)
            {
                $query .= " OR `description` LIKE :search_query";
            }

            $addons = DBConnection::get()->query(
                $query,
                DBConnection::FETCH_ALL,
                array(
                    ':search_query' => '%' . $search_query . '%'
                )
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Search failed!'));
        }

        return $addons;
    }

    /**
     * Get all the addon's of a type
     *
     * @param string $type
     * @param bool   $featuredFirst
     *
     * @return array
     */
    public static function getAddonList($type, $featuredFirst = false)
    {
        if (!static::isAllowedType($type))
        {
            return array();
        }

        try
        {
            $query = 'SELECT `a`.`id`, (`r`.`status` & ' . F_FEATURED . ') AS `featured`
                      FROM `' . DB_PREFIX . 'addons` `a`
                      LEFT JOIN `' . DB_PREFIX . $type . '_revs` `r`
                      ON `a`.`id` = `r`.`addon_id`
                      WHERE `a`.`type` = :type
                      AND `r`.`status` & :latest_bit ';
            if ($featuredFirst)
            {
                $query .= 'ORDER BY `featured` DESC, `a`.`name` ASC, `a`.`id` ASC';
            }
            else
            {
                $query .= 'ORDER BY `name` ASC, `id` ASC';
            }
            $list = DBConnection::get()->query(
                $query,
                DBConnection::FETCH_ALL,
                array(':type' => $type, ':latest_bit' => F_LATEST)
            );
            $return = array();
            foreach ($list as $addon)
            {
                $return[] = $addon['id'];
            }

            return $return;
        }
        catch(DBException $e)
        {
            return array();
        }
    }

    /**
     * Generate a random id based on the name
     *
     * @param string $type
     * @param string $name
     *
     * @return string the new id
     */
    public static function generateId($type, $name)
    {
        // TODO find usage for $type
        if (!is_string($name))
        {
            return false;
        }

        $addon_id = static::cleanId($name);
        if (!$addon_id)
        {
            return false;
        }

        // Check database
        while (static::exists($addon_id))
        {
            // If the addon id already exists, add an incrementing number to it
            $matches = array();
            if (preg_match('/^.+_([0-9]+)$/i', $addon_id, $matches))
            {
                $next_num = (int)$matches[1];
                $next_num++;
                $addon_id = str_replace($matches[1], $next_num, $addon_id);
            }
            else
            {
                $addon_id .= '_1';
            }
        }

        return $addon_id;
    }

    /**
     * Create a new add-on record and an initial revision
     * @global string $moderator_message Initial revision status message
     *                                   FIXME: put this in $attributes somewhere
     *
     * @param string  $type              Add-on type
     * @param array   $attributes        Contains properties of the add-on. Must have the
     *                                   following elements: name, designer, license, image, fileid, status, (arena)
     * @param string  $fileid            ID for revision file (see FIXME below)
     *
     * @throws AddonException
     *
     * @return Addon Object for newly created add-on
     */
    public static function create($type, $attributes, $fileid)
    {
        global $moderator_message;
        foreach ($attributes['missing_textures'] as $tex)
        {
            $moderator_message .= "Texture not found: $tex\n";
            echo '<span class="warning">' . h(
                    sprintf(_('Texture not found: %s'), $tex)
                ) . '</span><br />';
        }

        // Check if logged in
        if (!User::isLoggedIn())
        {
            throw new AddonException(_h('You must be logged in to create an add-on.'));
        }

        if (!static::isAllowedType($type))
        {
            throw new AddonException(_h('An invalid add-on type was provided.'));
        }

        $id = static::generateId($type, $attributes['name']);

        // Make sure the add-on doesn't already exist
        if (static::exists($id))
        {
            throw new AddonException(
                _h('An add-on with this ID already exists. Please try to upload your add-on again later.')
            );
        }

        // Make sure no revisions with this id exists
        // FIXME: Check if this id is redundant or not. Could just
        //        auto-increment this column if it is unused elsewhere.
        try
        {
            $rows = DBConnection::get()->query(
                'SELECT * FROM ' . DB_PREFIX . $type . '_revs WHERE `id` = :id',
                DBConnection::ROW_COUNT,
                array(':id' => $fileid)
            );
            if ($rows)
            {
                throw new AddonException(_h('The add-on you are trying to create already exists.'));
            }
        }
        catch(DBException $e)
        {
            throw new AddonException(sprintf('Failed to acces the %s_revs table.', $type));
        }


        echo _h('Creating a new add-on...') . '<br>';
        $fields_data = array(
            ":id" => $id,
            ":type" => $type,
            ":name" => $attributes['name'],
            ":uploader" => User::getLoggedId(),
            ":designer" => $attributes['designer'],
            ":license" =>  $attributes['license']
        );
        if ($type === 'tracks')
        {
            if ($attributes['arena'] === 'Y')
            {
                $fields_data[":props"] = '1';
            }
            else
            {
                $fields_data[":props"] = '0';
            }
        }
        try
        {
            DBConnection::get()->insert("addons", $fields_data);
        }
        catch(DBException $e)
        {
            throw new AddonException(_h('Your add-on could not be uploaded.'));
        }


        // Add the first revision
        $rev = 1;

        // Generate revision entry
        $fields_data = array(
            ":id" => $fileid,
            ":addon_id" => $id,
            ":fileid" => $attributes['fileid'],
            ":revision" => $rev,
            ":format" => $attributes['version'],
            ":image" =>  $attributes['image'],
            ":status" =>  $attributes['status']

        );
        if ($type === 'karts')
        {
            $fields_data[":icon"] = $attributes['image'];
        }

        // Add moderator message if available
        if (!empty($moderator_message))
        {
            $fields_data[":moderator_note"] = $moderator_message;
        }

        try
        {
            DBConnection::get()->insert($type . '_revs', $fields_data);
        }
        catch(DBException $e)
        {
            return false;
        }

        // Send mail to moderators
        moderator_email(
            'New Addon Upload',
            sprintf(
                "%s has uploaded a new %s '%s' %s",
                User::getLoggedUserName(),
                $type,
                $attributes['name'],
                (string)$id
            )
        );
        writeAssetXML();
        writeNewsXML();
        Log::newEvent("New add-on '{$attributes['name']}'");

        return new static($id);
    }

    /**
     * Check if an add-on of the specified ID exists
     *
     * @param string $addon_id Addon ID
     *
     * @return boolean
     */
    public static function exists($addon_id)
    {
        try
        {
            $num = DBConnection::get()->query(
                'SELECT `id`
                FROM `' . DB_PREFIX . 'addons`
                WHERE `id` = :addon_id',
                DBConnection::ROW_COUNT,
                array(':addon_id' => Addon::cleanId($addon_id))
            );
        }
        catch(DBException $e)
        {
            return false;
        }

        return ($num === 1);
    }
}
