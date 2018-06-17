<?php
/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
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
 * Class Addon
 */
class Addon extends Base
{
    /**
     * The type id for the kart
     */
    const KART = 1;

    /**
     * The type id for the track
     */
    const TRACK = 2;

    /**
     * The type id for the arena
     */
    const ARENA = 3;

    /**
     * Sort type for featured
     */
    const SORT_FEATURED = "featured";

    /**
     * Sort type for alphabetical
     */
    const SORT_ALPHABETICAL = "alphabetical";

    /**
     * Sort type for date
     */
    const SORT_DATE = "date";

    /**
     * Sort ascending
     */
    const ORDER_ASC = "asc";

    /**
     * Sort descending
     */
    const ORDER_DESC = "desc";

    /**
     * Default value for no image set for icon/image fields.
     */
    const NO_IMAGE = 0;

    /**
     * @var string
     */
    private $id;

    /**
     * @var int
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $uploader_id;

    /** The addon creation date
     * @var string
     */
    private $date_creation;

    /**
     * @var string
     */
    private $designer;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $license;

    /**
     * @var string
     */
    private $include_min;

    /**
     * @var string
     */
    private $include_max;

    /**
     * @var int
     */
    private $image = 0;

    /**
     * @var int
     */
    private $icon = 0;

    /**
     * @var string
     */
    private $permalink;

    /**
     * @var array
     */
    private $revisions = [];

    /**
     * @var int
     */
    private $latest_revision = 0;

    /**
     * Load the revisions from the database into the current instance
     *
     * @throws AddonException
     */
    private function loadRevisions()
    {
        try
        {
            $revisions = DBConnection::get()->query(
                'SELECT *
                FROM `{DB_VERSION}_addon_revisions`
                WHERE `addon_id` = :id
                ORDER BY `revision` ASC',
                DBConnection::FETCH_ALL,
                [':id' => $this->id]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('load revisions')), ErrorType::ADDON_DB_EXCEPTION);
        }

        if (!$revisions)
        {
            throw new AddonException(
                _h('No revisions of this add-on exist. This should never happen.'),
                ErrorType::ADDON_REVISION_MISSING
            );
        }

        foreach ($revisions as $rev)
        {
            $current_rev = [
                'file'           => $rev['file_id'],
                'format'         => $rev['format'],
                'image'          => (int)$rev['image_id'],
                'icon'           => isset($rev['icon_id']) ? (int)$rev['icon_id'] : 0,
                'moderator_note' => $rev['moderator_note'],
                'revision'       => (int)$rev['revision'],
                'status'         => $rev['status'],
                'timestamp'      => $rev['creation_date']
            ];

            // revision is latest
            if (static::isLatest($current_rev['status']))
            {
                $this->latest_revision = $current_rev['revision'];
                $this->image = $current_rev['image'];
                $this->icon = $current_rev['icon'];
            }

            $this->revisions[$current_rev['revision']] = $current_rev;
        }

        if (!$this->latest_revision)
        {
            throw new AddonException(
                _h("Did not found latest revision (possibly wrong status). This should never happen")
            );
        }
    }

    /**
     * Instance constructor
     *
     * @param array $data           the addon data retrieved from the database
     * @param bool  $load_revisions load also the revisions
     *
     * @throws AddonException
     */
    private function __construct(array $data, $load_revisions = true)
    {
        $this->id = (string)static::cleanId($data['id']);
        $this->type = (int)$data['type'];
        $this->name = $data['name'];
        $this->uploader_id = (int)$data['uploader'];
        $this->date_creation = $data['creation_date'];
        $this->designer = $data['designer'];
        $this->description = $data['description'];
        $this->license = $data['license'];
        $this->permalink = static::buildPermalink($this->type, $this->id);
        $this->include_min = $data['min_include_ver'];
        $this->include_max = $data['max_include_ver'];

        $this->image = static::NO_IMAGE;
        $this->icon = static::NO_IMAGE;

        // load revisions
        if ($load_revisions)
        {
            $this->loadRevisions();
        }
    }


    /**
     * Helper method to add a revision to the database
     *
     * @param array  $missing_textures  a list of missing textures, useful for building moderator message
     * @param int    $revision          the addon revision
     * @param int    $file_id           the file archive that contains the addon data
     * @param int    $format            the version specifier
     * @param int    $image_id          the image file identifier
     * @param int    $status            the status of the addon
     * @param string $moderator_message
     * @param string $exception_message message to throw back when the insertion fails
     *
     * @throws AddonException
     */
    private function addRevision(
        array $missing_textures,
        $revision,
        $file_id,
        $format,
        $image_id,
        $status,
        $moderator_message,
        $exception_message
    ) {
        foreach ($missing_textures as $tex)
        {
            $moderator_message .= "Texture not found: $tex\n";
        }

        // Add revision entry
        $fields_data = [
            ":addon_id" => $this->id,
            ":file_id"  => $file_id,
            ":revision" => $revision, // the next revision number
            ":format"   => $format,
            ":image_id" => $image_id,
            ":status"   => $status
        ];

        if ($this->type === static::KART)
        {
            $fields_data[":icon_id"] = $image_id;
        }

        // Add moderator message if available
        if ($moderator_message)
        {
            $fields_data[":moderator_note"] = $moderator_message;
        }

        try
        {
            DBConnection::get()->insert('addon_revisions', $fields_data);
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db($exception_message));
        }
    }

    /**
     * Create an add-on revision. Do not use this method to create the first addon revision
     *
     * @param array  $attributes contains name, designer, license, version, image, file_id, status, missing_textures
     * @param string $moderator_message
     *
     * @throws AddonException|FileSystemException|FileException
     */
    public function createRevision(array $attributes, $moderator_message)
    {
        // New revision can set a new addon name and a new license
        // Update the addon name
        $this->setName($attributes['name']);

        // Update license file record
        $this->setLicense($attributes['license']);

        // Compare with new image
        try
        {
            $file_image = File::getFromID($attributes['image']);
        }
        catch (FileException $e)
        {
            throw new AddonException($e->getMessage());
        }

        $new_hash = md5_file(UP_PATH . $file_image->getPath());
        // Prevent duplicate images from being created.
        foreach ($this->getImageHashes() as $image)
        {
            // Skip image that was just uploaded
            if ($image['id'] === $attributes['image'])
            {
                continue;
            }

            // Image already exists in the database
            if ($new_hash === $image['hash'])
            {
                $file_image->delete();
                $attributes['image'] = $image['id'];
                break;
            }
        }

        // add revision
        $this->addRevision(
            $attributes['missing_textures'],
            $this->getMaxRevisionID() + 1,
            $attributes['file_id'],
            $attributes['version'],
            $attributes['image'],
            $attributes['status'],
            $moderator_message,
            _('create add-on revision')
        );

        StkLog::newEvent("New add-on revision for '{$attributes['name']}'");
    }

    /**
     * Create first addon revision
     *
     * @param array  $attributes contains name, designer, license, version, image, file_id, status, missing_textures
     * @param string $moderator_message
     *
     * @throws AddonException
     */
    public function createRevisionFirst(array $attributes, $moderator_message)
    {
        $this->addRevision(
            $attributes['missing_textures'],
            1,
            $attributes['file_id'],
            $attributes['version'],
            $attributes['image'],
            $attributes['status'],
            $moderator_message,
            _('create first add-on revision')
        );
    }

    /**
     * Delete an add-on record and all associated files and ratings
     *
     *
     * @throws AddonException
     */
    public function delete()
    {
        // Remove cache files for this add-on
        Cache::clearAddon($this->id);

        // Remove files associated with this addon
        try
        {
            $files = File::getAllAddon($this->id);

            // Remove after database
            foreach ($files as $file)
            {
                $file->delete();
            }
        }
        catch (FileException $e)
        {
            throw new AddonException($e->getMessage());
        }
        catch (Exception $e)
        {
            throw new AddonException($e->getMessage());
        }

        // Remove addon entry
        // No need to remove files or revisions, as they are removed by database constraints
        try
        {
            DBConnection::get()->delete("addons", "`id` = :id", [":id" => $this->id]);
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('remove addon')));
        }

        StkLog::newEvent("Deleted add-on '{$this->name}'");
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
        if ($rev < 1 || !isset($this->revisions[$rev]))
        {
            throw new AddonException(_h('The revision you are trying to delete does not exist.'));
        }
        if (count($this->revisions) === 1)
        {
            throw new AddonException(_h('You cannot delete the last revision of an add-on.'));
        }
        if (static::isLatest($this->revisions[$rev]['status']))
        {
            throw new AddonException(
                _h(
                    'You cannot delete the latest revision of an add-on. Please mark a different revision to be the latest revision first.'
                )
            );
        }

        // Queue addon file for deletion
        try
        {
            File::queueDelete($this->revisions[$rev]['file']);
        }
        catch (FileException $e)
        {
            throw new AddonException(_h('The add-on file could not be queued for deletion.'));
        }

        // Remove the revision record from the database
        try
        {
            DBConnection::get()->delete(
                "addon_revisions",
                "`addon_id` = :id AND `revision` = :revision",
                [
                    ':id'       => $this->id,
                    ':revision' => $rev
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('delete a revision')));
        }

        StkLog::newEvent('Deleted revision ' . $rev . ' of \'' . $this->name . '\'');
    }

    /**
     * Get the id of the addon
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get the id of the image
     *
     * @return int
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Get the id of the icon
     *
     * @return int
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @return string
     */
    public function getDateCreation()
    {
        return $this->date_creation;
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
        if (!$this->designer) return _h('Unknown');

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
     * @return array
     */
    public function getLatestRevision()
    {
        return $this->revisions[$this->latest_revision];
    }

    /**
     * Get the maximum revision number
     *
     * @return int
     */
    public function getMaxRevisionID()
    {
        return max(array_keys($this->revisions));
    }

    /**
     * Get the current status of the latest revision
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->revisions[$this->latest_revision]['status'];
    }

    /**
     * Get the addon type
     *
     * @return int
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
     * @return int|null the id of the uploader
     */
    public function getUploaderId()
    {
        return $this->uploader_id;
    }

    /**
     * Does this addon has a valid uploader?
     * @return bool
     */
    public function hasUploader()
    {
        return $this->uploader_id != null;
    }

    /**
     * Get the minimum stk version that this addon supports
     *
     * @return string
     */
    public function getIncludeMin()
    {
        return $this->include_min;
    }

    /**
     * Get the maximum stk version that this addon supports
     *
     * @return string
     */
    public function getIncludeMax()
    {
        return $this->include_max;
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

        // TODO, optimize, use one query
        // Look up file ID
        try
        {
            $file_id_lookup = DBConnection::get()->query(
                'SELECT `file_id`
                FROM `{DB_VERSION}_addon_revisions`
                WHERE `addon_id` = :addon_id
                AND `revision` = :revision',
                DBConnection::FETCH_FIRST,
                [
                    ':addon_id' => $this->id,
                    ':revision' => $revision
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('look up file ID')));
        }

        if (!$file_id_lookup)
        {
            throw new AddonException(_h('There is no add-on found with the specified revision number.'));
        }

        $file_id = $file_id_lookup['file_id'];

        // Look up file path from database
        try
        {
            $file_path = File::getFromID($file_id)->getPath();
        }
        catch (FileException $e)
        {
            throw new AddonException(_h('The requested file does not have an associated file record.'));
        }

        return $file_path;
    }

    /**
     * Get the md5sums of all the image files of this addon
     *
     * @param string $path_prefix the path to the file prefix
     *
     * @throws AddonException
     * @return array of associative arrays with keys 'id', 'path', 'hash'
     */
    public function getImageHashes($path_prefix = UP_PATH)
    {
        $return = [];
        foreach ($this->getImages() as $image)
        {
            $return[] = [
                'id'   => $image->getId(),
                'path' => $image->getPath(),
                'hash' => md5_file($path_prefix . $image->getPath())
            ];
        }

        return $return;
    }

    /**
     * Get the image files associated with this addon
     *
     * @return File[]
     */
    public function getImages()
    {
        return File::getAllAddon($this->id, File::IMAGE);
    }

    /**
     * Get all of the source files associated with an addon
     *
     * @return File[]
     */
    public function getSourceFiles()
    {
        return File::getAllAddon($this->id, File::SOURCE);
    }

    /**
     * Set the add-on's description
     *
     * @param string $description
     *
     * @return static
     * @throws AddonException
     */
    public function setDescription($description)
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `{DB_VERSION}_addons`
                 SET `description` = :description
                 WHERE `id` = :id',
                DBConnection::NOTHING,
                [
                    ':description' => $description,
                    ':id'          => $this->id
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the description')));
        }

        $this->description = $description;

        return $this;
    }

    /**
     * Set the add-on's designer
     *
     * @param string $designer
     *
     * @return static
     * @throws AddonException
     */
    public function setDesigner($designer)
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `{DB_VERSION}_addons`
                SET `designer` = :designer
                WHERE `id` = :id',
                DBConnection::NOTHING,
                [
                    ':designer' => $designer,
                    ':id'       => $this->id
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the designer')));
        }

        $this->designer = $designer;

        return $this;
    }


    /**
     * Helper method to set the image or icon
     *
     * @param int    $image_or_icon_id
     * @param string $field can be image_id or icon_id
     *
     * @return Addon
     * @throws AddonException
     */
    private function setImageOrIcon($image_or_icon_id, $field)
    {
        try
        {
            DBConnection::get()->query(
                "UPDATE `{DB_VERSION}_addon_revisions`
                SET `" . $field . "` = :image_or_icon
                WHERE `addon_id` = :addon_id
                AND `status` & " . F_LATEST,
                DBConnection::NOTHING,
                [
                    ':image_or_icon' => $image_or_icon_id,
                    ':addon_id'      => $this->id
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the image/icon')));
        }

        return $this;
    }

    /**
     * Set the image for the latest revision of this add-on.
     *
     * @param int $image_id
     *
     * @return Addon
     * @throws AddonException
     */
    public function setImage($image_id)
    {
        return $this->setImageOrIcon($image_id, 'image_id');
    }


    /**
     * Set the icon for the latest revision of a kart
     * Only useful for karts
     *
     * @param int $icon_id
     *
     * @throws AddonException on database error or addon type does not have an icon
     * @return Addon
     */
    public function setIcon($icon_id)
    {
        if ($this->type !== static::KART)
        {
            throw new AddonException(_h("This addon type does not have an icon associated with it"));
        }

        return $this->setImageOrIcon($icon_id, 'icon_id');
    }

    /**
     * Set the addon include versions
     *
     * @param string $start_ver
     * @param string $end_ver
     *
     * @return static
     * @throws AddonException
     */
    public function setIncludeVersions($start_ver, $end_ver)
    {
        try
        {
            Validate::versionString($start_ver);
            Validate::versionString($end_ver);
        }
        catch (ValidateException $e)
        {
            throw new AddonException($e->getMessage());
        }

        try
        {
            DBConnection::get()->query(
                'UPDATE `{DB_VERSION}_addons`
                SET `min_include_ver` = :start_ver, `max_include_ver` = :end_ver
                WHERE `id` = :addon_id',
                DBConnection::NOTHING,
                [
                    ':addon_id'  => $this->id,
                    ':start_ver' => $start_ver,
                    ':end_ver'   => $end_ver
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('set the min/max include versions')));
        }

        $this->include_min = $start_ver;
        $this->include_max = $end_ver;

        return $this;
    }

    /**
     * Set the license of this addon
     *
     * @param string $license
     *
     * @return static
     * @throws AddonException
     */
    public function setLicense($license)
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `{DB_VERSION}_addons`
                SET `license` = :license
                WHERE `id` = :addon_id',
                DBConnection::NOTHING,
                [
                    ':license'  => $license,
                    ':addon_id' => $this->id
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the license')));
        }

        $this->license = $license;

        return $this;
    }

    /**
     * Set the name of this addon
     *
     * @param string $name
     *
     * @return static
     * @throws AddonException
     */
    public function setName($name)
    {
        try
        {
            DBConnection::get()->query(
                'UPDATE `{DB_VERSION}_addons`
                SET `name` = :name
                WHERE `id` = :addon_id',
                DBConnection::NOTHING,
                [
                    ':name'     => $name,
                    ':addon_id' => $this->id
                ]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the name')));
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Set notes on this addon
     *
     * @param array $notes an array with key and int marking the revision note and value the note itself
     *
     * @return Addon
     * @throws AddonException
     */
    public function setNotes(array $notes)
    {
        // Save record in database
        foreach ($notes as $revision => $value)
        {
            // check if revision is valid
            if (!isset($this->revisions[$revision]))
            {
                throw new AddonException(sprintf("Revision %d does not exist", (int)$revision));
            }

            try
            {
                DBConnection::get()->query(
                    'UPDATE `{DB_VERSION}_addon_revisions`
                    SET `moderator_note` = :moderator_note
                    WHERE `addon_id` = :addon_id
                    AND `revision` = :revision',
                    DBConnection::NOTHING,
                    [
                        ':moderator_note' => $value,
                        ':addon_id'       => $this->id,
                        ':revision'       => $revision
                    ]
                );
            }
            catch (DBException $e)
            {
                throw new AddonException(exception_message_db(_('update add-on status')));
            }
        }

        // Generate email
        $email_body = '';
        $notes = array_reverse($notes, true);
        foreach ($notes as $revision => $value)
        {
            $email_body .= "\n== Revision $revision ==\n";
            $value = strip_tags(str_replace('\r\n', "\n", $value));
            $email_body .= "$value\n\n";
        }

        // TODO maybe move refactor
        // Get uploader email address
        try
        {
            $user = DBConnection::get()->query(
                'SELECT `username`, `email`
                FROM `{DB_VERSION}_users`
                WHERE `id` = :user_id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                [':user_id' => $this->uploader_id],
                [':user_id' => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('find user record')));
        }

        try
        {
            StkMail::get()->addonNoteNotification($user['email'], $this->name, $email_body);
        }
        catch (StkMailException $e)
        {
            throw new AddonException('Failed to send email to user. ' . $e->getMessage());
        }

        StkLog::newEvent("Added notes to '{$this->name}'");

        return $this;
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
            if (static::isApproved($rev['status']))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the status flags of an addon
     *
     * @param array  $pool   where each field is located
     * @param string $fields hold all the fields in a comma separated array
     *
     * @return static
     * @throws AddonException
     */
    public function setStatus(array $pool, $fields)
    {
        $has_permission = User::hasPermission(AccessControl::PERM_EDIT_ADDONS);

        // do not have permission
        if (!$this->isUserOwner() && !$has_permission)
        {
            throw new AddonException(_h("You do not have the permission to change this addon's status"));
        }

        // Initialise the status field to its present values
        // (Remove any checkboxes that the user could have checked)
        $status = [];
        foreach ($this->revisions as $rev_n => $rev)
        {
            $mask = F_LATEST + F_ALPHA + F_BETA + F_RC;
            if ($has_permission)
            {
                $mask = $mask + F_APPROVED + F_INVISIBLE + F_DFSG + F_FEATURED;
            }

            $status[$rev_n] = ($rev['status'] & ~$mask); // reset all bits that the user has access on
        }

        // Iterate through each field
        $fields = explode(',', $fields);
        foreach ($fields as $field)
        {
            $revision = 0;
            $flag = "";
            $is_checked = $is_latest = false;

            if (isset($pool[$field])) // field is on most likely
            {
                if ($pool[$field] === 'on')
                {
                    $is_checked = true;
                }

                if ($field === 'latest') // latest field
                {
                    $revision = (int)$pool['latest'];
                    $is_latest = true;
                }
                else // normal field
                {
                    // flag-rev_n
                    $temp_field = explode('-', $field);
                    $flag = $temp_field[0];
                    $revision = (int)$temp_field[1];
                }
            }

            // valid revision, not 0
            if ($revision)
            {
                // Initialize the status of the current revision if it has not been created yet.
                if (!isset($status[$revision]))
                {
                    $status[$revision] = 0;
                }

                // Mark the "latest" revision, we treat it specially, because only a revision can be latest
                if ($is_latest)
                {
                    $status[$revision] += F_LATEST;
                    continue;
                }
            }

            // is not checked
            if (!$is_checked)
            {
                continue;
            }

            // Update status values for all flags
            switch ($flag)
            {
                case 'approved':
                    if (!$has_permission)
                    {
                        break;
                    }
                    $status[$revision] += F_APPROVED;
                    break;

                case 'invisible':
                    if (!$has_permission)
                    {
                        break;
                    }
                    $status[$revision] += F_INVISIBLE;
                    break;

                case 'dfsg':
                    if (!$has_permission)
                    {
                        break;
                    }
                    $status[$revision] += F_DFSG;
                    break;

                case 'featured':
                    if (!$has_permission)
                    {
                        break;
                    }
                    $status[$revision] += F_FEATURED;
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

                default:
                    break;
            }
        }

        // Loop through each addon revision
        foreach ($status as $revision => $value)
        {
            // Write new addon status
            try
            {
                DBConnection::get()->query(
                    'UPDATE `{DB_VERSION}_addon_revisions`
                    SET `status` = :status
                    WHERE `addon_id` = :addon_id
                    AND `revision` = :revision',
                    DBConnection::NOTHING,
                    [
                        ':status'   => $value,
                        ':addon_id' => $this->id,
                        ':revision' => $revision
                    ],
                    [
                        ':status'   => DBConnection::PARAM_INT,
                        ':revision' => DBConnection::PARAM_INT
                    ]
                );
            }
            catch (DBException $e)
            {
                throw new AddonException(exception_message_db(_('update add-on status')));
            }
        }

        StkLog::newEvent("Set status for add-on '{$this->name}'");

        return $this;
    }

    /**
     * @return AddonException
     */
    public static function getException()
    {
        return new AddonException();
    }

    /**
     * Factory method for the addon
     *
     * @param string $addon_id
     * @param bool   $load_revisions flag that indicates to load the addon revisions
     *
     * @return Addon
     * @throws AddonException
     */
    public static function get($addon_id, $load_revisions = true)
    {
        $data = static::getFromField(
            "SELECT * FROM `{DB_VERSION}_addons`",
            "id",
            $addon_id,
            DBConnection::PARAM_STR,
            _h('The requested add-on does not exist.')
        );

        return new Addon($data, $load_revisions);
    }

    /**
     * Build the permalink for an an addon name and type
     *
     * @param int    $addon_type
     * @param string $addon_name
     * @param string $prefix
     *
     * @return string
     */
    public static function buildPermalink($addon_type, $addon_name, $prefix = ROOT_LOCATION)
    {
        return $prefix . 'addons.php?type=' . static::typeToString($addon_type) . '&amp;name=' . $addon_name;
    }

    /**
     * Get the addon name,
     * This method will silently fail
     *
     * @param string $id the addon id
     *
     * @return string empty string on error
     */
    public static function getNameByID($id)
    {
        // TODO refactor
        if (!$id)
        {
            return "";
        }

        $id = static::cleanId($id);

        try
        {
            $addon = DBConnection::get()->query(
                'SELECT `name`
                FROM `{DB_VERSION}_addons`
                WHERE `id` = :id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                [':id' => $id]
            );
        }
        catch (DBException $e)
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
     * This method will silently fail
     *
     * @param string $id the addon id
     *
     * @return string empty string on error
     */
    public static function getTypeByID($id)
    {
        if (!$id)
        {
            return "";
        }

        $id = static::cleanId($id);

        try
        {
            $addon = DBConnection::get()->query(
                'SELECT `type`
                FROM `{DB_VERSION}_addons`
                WHERE `id` = :id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                [':id' => $id]
            );
        }
        catch (DBException $e)
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
     * @param int $type
     *
     * @return bool true if allowed and false otherwise
     */
    public static function isAllowedType($type)
    {
        return in_array($type, static::getAllowedTypes(), true);
    }

    /**
     * Get an array of allowed types
     * @return array
     */
    public static function getAllowedTypes()
    {
        return [static::KART, static::TRACK, static::ARENA];
    }

    /**
     * Return the appropriate addon type for the string provided
     *
     * @param string $string
     *
     * @return int
     */
    public static function stringToType($string)
    {
        switch ($string)
        {
            case 'kart':
            case 'karts':
                return static::KART;

            case 'track':
            case 'tracks':
                return static::TRACK;

            case 'arena':
            case 'arenas':
                return static::ARENA;

            default:
                return (int)$string;
        }
    }

    /**
     * Return the appropriate addon string for the type provided
     *
     * @param int $type
     *
     * @return string
     */
    public static function typeToString($type)
    {
        switch ($type)
        {
            case static::KART:
                return "karts";

            case static::TRACK:
                return "tracks";

            case static::ARENA:
                return "arenas";

            default:
                return sprintf("<type '%s' not recognized>", h($type));
        }
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
            user_error("ID is not a string");

            return false;
        }

        $length = mb_strlen($id);
        if (!$length)
        {
            return false;
        }
        $id = mb_strtolower($id);

        // Validate all characters in addon id
        // Rather than using str_replace, and removing bad characters,
        // it makes more sense to only allow certain characters
        for ($i = 0; $i < $length; $i++)
        {
            $substr = mb_substr($id, $i, 1);
            if (!preg_match('/^[a-z0-9\-_]$/i', $substr))
            {
                $substr = '-';
            }
            $id = substr_replace($id, $substr, $i, 1);
        }

        return $id;
    }

    /**
     * Filter an array of addons for the addon menu template
     *
     * @param Addon[] $addons
     * @param string  $current_id the current selected addon
     *
     * @return array
     */
    public static function filterMenuTemplate($addons, $current_id = null)
    {
        $has_permission = User::hasPermission(AccessControl::PERM_EDIT_ADDONS);
        $template_addons = [];

        foreach ($addons as $addon)
        {
            // Approved?
            if ($addon->hasApprovedRevision())
            {
                $class = '';
            }
            else if ($has_permission || $addon->isUserOwner())
            {
                // not approved, see of we are logged in and we have permission
                $class = ' disabled';
            }
            else
            {
                // do not show
                continue;
            }

            // Get link icon
            $icon = IMG_LOCATION . 'track-icon.png';
            if ($addon->getType() === static::KART)
            {
                $icon = IMG_LOCATION . 'kart-icon.png';

                // Make sure an icon file is set for kart
                $icon_id = $addon->getIcon();
                if ($icon_id !== Addon::NO_IMAGE)
                {
                    $im = Cache::getImage($icon_id, StkImage::SIZE_SMALL);
                    if ($im['exists'] && $im['is_approved'])
                    {
                        $icon = $im['url'];
                    }
                }
            }

            // is currently selected
            if ($current_id && $current_id === $addon->getId())
            {
                $class .= " active";
            }

            $template_addons[] = [
                "id"          => $addon->getId(),
                "class"       => $class,
                "is_featured" => static::isFeatured($addon->getStatus()),
                "name"        => h($addon->getName()),
                "real_url"    => $addon->getPermalink(),
                "image_src"   => $icon,
                "disp"        => URL::rewriteFromConfig($addon->getPermalink())
            ];
        }

        return $template_addons;
    }

    /**
     * Search for an addon by its name or description
     *
     * @param string $search_query the search query
     * @param string $type         the addon type as a string or 'all' to search all addons
     * @param array  $search_flags an array of flags
     *
     * @throws AddonException
     * @return Addon[] array of addons
     */
    public static function search($search_query, $type, array $search_flags)
    {
        // validate
        if (!$search_query)
        {
            throw new AddonException(_h("The search term is empty"));
        }
        if (!$search_flags)
        {
            throw new AddonException(_h("No search field specified"));
        }
        $type_int = static::stringToType($type);
        if (!static::isAllowedType($type_int) && $type !== "all")
        {
            throw new AddonException(sprintf("Invalid search type = %s is not recognized ", $type));
        }

        // build query
        $query = "SELECT * FROM `{DB_VERSION}_addons` WHERE";
        if ($type !== "all") // search for specific addon
        {
            $query .= sprintf(" (`type` = '%d') AND", $type_int);
        }

        // check search flags
        $query .= " (";
        $flags_part = [];
        foreach ($search_flags as $flag)
        {
            if (!in_array($flag, ["name", "description", "designer"]))
            {
                throw new AddonException(sprintf("search flag = %s is invalid", h($flag)));
            }

            $flags_part[] = sprintf("`%s` LIKE :search_query", $flag);
        }
        $query .= implode(" OR ", $flags_part);
        $query .= ")";

        try
        {
            $addons = DBConnection::get()->query(
                $query,
                DBConnection::FETCH_ALL,
                [':search_query' => '%' . $search_query . '%']
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('search for add-on')));
        }

        $return_addons = [];
        foreach ($addons as $addon)
        {
            try
            {
                $return_addons[] = new static($addon);
            }
            catch (AddonException $e)
            {
                if ($e->getCode() == ErrorType::ADDON_REVISION_MISSING)
                {
                    // ignore corrupt addon
                    Debug::addMessage("No revision for addon = " . $addon['id']);
                }
                else
                {
                    throw $e;
                }
            }
        }

        return $return_addons;
    }

    /**
     * Get all the addon's of a type
     *
     * @param int    $addon_type   type of addon
     * @param int    $limit        the number of results
     * @param int    $current_page current page that the user is on
     * @param string $sort_type    the sort type
     * @param string $sort_order   the sort order, ASC or DESC
     *
     * @throws AddonException
     * @return Addon[] array of addons
     */
    public static function getAll($addon_type, $limit = -1, $current_page = 1, $sort_type = "", $sort_order = "")
    {
        if (!static::isAllowedType($addon_type))
        {
            throw new AddonException(_h("Invalid addon type"));
        }

        // build query
        $query = 'SELECT `a`.`id`, (`r`.`status` & ' . F_FEATURED . ') AS `featured`, `r`.`creation_date` as `date`
                  FROM `{DB_VERSION}_addons` `a`
                  LEFT JOIN `{DB_VERSION}_addon_revisions` `r`
                  ON `a`.`id` = `r`.`addon_id`
                  WHERE `a`.`type` = :type
                  AND `r`.`status` & :latest_bit ';
        $db_params = [
            ':type'       => $addon_type,
            ':latest_bit' => F_LATEST // retrieve only the latest addons
        ];
        $db_types = [];

        // apply sorting
        switch ($sort_order)
        {
            case static::ORDER_ASC:
                $sort_direction = "ASC";
                break;

            case static::ORDER_DESC:
                $sort_direction = "DESC";
                break;

            default: // default
                $sort_direction = "ASC";
                break;

        }
        switch ($sort_type)
        {
            case static::SORT_FEATURED:
                $query .= 'ORDER BY `featured` DESC, `a`.`name` ASC, `a`.`id` ASC';
                break;

            case static::SORT_DATE:
                $query .= sprintf('ORDER BY `date` %s', $sort_direction);
                break;

            case static::SORT_ALPHABETICAL:
            default: // sort by name by default
                $query .= sprintf('ORDER BY `name` %s, `id` %s', $sort_direction, $sort_direction);
                break;
        }

        // apply pagination
        if ($limit > 0)
        {
            $query .= " LIMIT :limit OFFSET :offset";
            $db_params[":limit"] = $limit;
            $db_params[":offset"] = ($current_page - 1) * $limit;
            $db_types[":limit"] = $db_types[":offset"] = DBConnection::PARAM_INT;
        }

        try
        {
            $addons = DBConnection::get()->query(
                $query,
                DBConnection::FETCH_ALL,
                $db_params,
                $db_types
            );
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_("SELECT ALL addons FROM the DATABASE")));
        }

        $return = [];
        foreach ($addons as $addon)
        {
            // TODO select all addons with one SQL query
            $return[] = static::get($addon['id']);
        }

        return $return;
    }

    /**
     * Gets all the addons that belong to this user
     *
     * @param int $user_id
     *
     * @throws AddonException
     * @return Addon[] array of addons that belong to this user
     */
    public static function getAllAddonsOfUser($user_id)
    {
        $addons_data = [];
        $addons = [];
        try
        {
            $addons_data = DBConnection::get()->query(
                'SELECT *
                FROM `{DB_VERSION}_addons`
                WHERE `uploader` = :user_id',
                DBConnection::FETCH_ALL,
                [':user_id' => $user_id],
                [':user_id' => DBConnection::PARAM_INT]
            );

        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('get all addons of user')));
        }

        foreach ($addons_data as $data)
        {
            $addons[] = new static($data, false);
        }

        return $addons;
    }

    /**
     * Generate a random id based on the name
     *
     * @param string $name
     *
     * @return string the new id
     */
    public static function generateId($name)
    {
        $addon_id = static::cleanId($name);
        if (!$addon_id)
        {
            throw new InvalidArgumentException('Invalid addon ID.');
        }

        // Check database
        while (static::exists($addon_id))
        {
            // If the addon id already exists, add an incrementing number to it
            $matches = [];
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
     * Create a new add-on record. To create the first addon revision, call $this->createRevisionFirst
     *
     * @param string $id                 Addon ID
     * @param int $type               Add-on type
     * @param array  $attributes         Contains properties of the add-on. Must have the
     *                                   following elements: name, designer, license, image, file_id, status,
     *                                   missing_textures
     *
     * @return Addon
     * @throws AddonException
     */
    public static function create($id, $type, array $attributes)
    {
        // validate
        if (!static::isAllowedType($type))
        {
            throw new AddonException(_h('An invalid add-on type was provided.'));
        }

        // add addon to database
        $fields_data = [
            ":id"       => $id,
            ":type"     => $type,
            ":name"     => $attributes['name'],
            ":uploader" => User::getLoggedId(),
            ":designer" => $attributes['designer'],
            ":license"  => $attributes['license']
        ];
        if ($type === static::TRACK)
        {
            // TODO find usage or delete this
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
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_('create your add-on')));
        }
        StkLog::newEvent("New add-on '{$attributes['name']}'");

        return static::get($id, false);
    }

    /**
     * Wrapper for StkMail::moderatorNotification which sends an email to the moderators list
     *
     * @param string $title the email subject
     * @param string $body  the email body in html
     *
     * @throws AddonException
     */
    public static function sendMailModerator($title, $body)
    {
        try
        {
            StkMail::get()->moderatorNotification($title, h($body));
        }
        catch (StkMailException $e)
        {
            throw new AddonException($e->getMessage());
        }
    }

    /**
     * Send moderator notification for a new addon upload
     *
     * @param null|string $username the username who uploaded the addon, if null the default will be the currently
     *                              logged in user
     *
     * @throws AddonException
     */
    public function sendMailModeratorNewAddon($username = null)
    {
        if (!$username) $username = User::getLoggedUserName();

        static::sendMailModerator(
            'New Addon Upload',
            sprintf(
                "%s has uploaded a new %s '%s' %s",
                $username,
                static::typetoString($this->type),
                $this->name,
                $this->id
            )
        );
    }

    /**
     * Send moderator notification for a new revision upload
     *
     * @param null|string $username the username who uploaded the addon, if null the default will be the currently
     *                              logged in user
     *
     * @throws AddonException
     */
    public function sendMailModeratorNewRevision($username = null)
    {
        if (!$username) $username = User::getLoggedUserName();

        static::sendMailModerator(
            'New Addon Revision Upload',
            sprintf(
                "%s has uploaded a new revision for %s '%s' %s",
                $username,
                static::typeToString($this->type),
                $this->name,
                $this->id
            )
        );
    }

    /**
     * Check if an add-on of the specified ID exists
     *
     * @param string $id Addon ID
     *
     * @return bool
     */
    public static function exists($id)
    {
        if (!$id)
        {
            return false;
        }

        return static::existsField("addons", "id", static::cleanId($id), DBConnection::PARAM_STR);
    }

    /**
     * Get the total number of addons of a type
     *
     * @param int $type the addon type
     *
     * @return int
     * @throws AddonException
     */
    public static function count($type)
    {
        Assert::true(static::isAllowedType($type));

        try
        {
            $count = DBConnection::get()->count("addons", "`type` = :type", [":type" => $type]);
        }
        catch (DBException $e)
        {
            throw new AddonException(exception_message_db(_("count the number of addons")));
        }

        return $count;
    }

    /**
     * Checks if the current logged in user can modify the addon
     *
     * @throw AddonException
     */
    public function checkUserEditPermissions()
    {
        // Check if logged in
        if (!User::isLoggedIn())
        {
            throw new AddonException(_h('You must be logged in to perform this action.'));
        }

        // Make sure user has permission to upload a new revision for this add-on
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && !$this->isUserOwner())
        {
            throw new AddonException(_h('You do not have the necessary permissions to perform this action.'));
        }
    }

    /**
     * See if thee current logged in user is the owner of this addon(aka the uploader, creator)
     *
     * @return bool
     */
    public function isUserOwner()
    {
        return $this->uploader_id === User::getLoggedId();
    }

    /**
     * If addon is approved
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isApproved($status)
    {
        return (bool)($status & F_APPROVED);
    }

    /**
     * If addon is in alpha
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isAlpha($status)
    {
        return (bool)($status & F_ALPHA);
    }

    /**
     * If addon is in beta
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isBeta($status)
    {
        return (bool)($status & F_BETA);
    }

    /**
     * If addon is in release candidate
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isReleaseCandidate($status)
    {
        return (bool)($status & F_RC);
    }

    /**
     * If addon is invisible
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isInvisible($status)
    {
        return (bool)($status & F_INVISIBLE);
    }

    /**
     * If addon is Debian Free Software Guidelines compliant
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isDFSGCompliant($status)
    {
        return (bool)($status & F_DFSG);
    }

    /**
     * If addon is featured
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isFeatured($status)
    {
        return (bool)($status & F_FEATURED);
    }

    /**
     * If addon is latest
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isLatest($status)
    {
        return (bool)($status & F_LATEST);
    }

    /**
     * If texture is not a power of two, the the texture is invalid
     *
     * @param int $status
     *
     * @return bool
     */
    public static function isTextureInvalid($status)
    {
        return (bool)($status & F_TEX_NOT_POWER_OF_2);
    }
}
