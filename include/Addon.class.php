<?php
/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
class Addon extends Base
{
    const KART = "karts";

    const TRACK = "tracks";

    const ARENA = "arenas";

    const SORT_FEATURED = "featured";

    const SORT_ALPHABETICAL = "alphabetical";

    const SORT_DATE = "date";

    const ORDER_ASC = "asc";

    const ORDER_DESC = "desc";

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
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
     * @var int
     */
    private $include_min;

    /**
     * @var int
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
    private $latest_revision;


    /**
     * @param string $message
     *
     * @throws AddonException
     */
    protected static function throwException($message)
    {
        throw new AddonException($message);
    }

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
                FROM `' . DB_PREFIX . $this->type . '_revs`
                WHERE `addon_id` = :id
                ORDER BY `revision` ASC',
                DBConnection::FETCH_ALL,
                [':id' => $this->id]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('load revisions')));
        }

        if (!$revisions)
        {
            throw new AddonException(_h('No revisions of this add-on exist. This should never happen.'));
        }

        foreach ($revisions as $rev)
        {
            $current_rev = [
                'file'           => $rev['fileid'],
                'format'         => $rev['format'],
                'image'          => $rev['image'],
                'icon'           => (isset($rev['icon'])) ? $rev['icon'] : 0,
                'moderator_note' => $rev['moderator_note'],
                'revision'       => $rev['revision'],
                'status'         => $rev['status'],
                'timestamp'      => $rev['creation_date']
            ];

            // revision is latest
            if (Addon::isLatest($current_rev['status']))
            {
                $this->latest_revision = (int)$rev['revision'];
                $this->image = $rev['image'];
                $this->icon = (isset($rev['icon'])) ? $rev['icon'] : 0;
            }

            $this->revisions[$rev['revision']] = $current_rev;
        }

        if (!$this->latest_revision)
        {
            throw new AddonException(_h("Did not found latest revision (possibly wrong status). This should never happen"));
        }
    }

    /**
     * Instance constructor
     *
     * @param string $id             the addon id
     * @param array  $data           the addon data
     * @param bool   $load_revisions load also the revisions
     *
     * @throws AddonException
     */
    private function __construct($id, $data, $load_revisions = true)
    {
        $this->id = (string)static::cleanId($id);
        $this->type = $data['type'];
        $this->name = $data['name'];
        $this->uploader_id = (int)$data['uploader'];
        $this->date_creation = $data['creation_date'];
        $this->designer = $data['designer'];
        $this->description = $data['description'];
        $this->license = $data['license'];
        $this->permalink = ROOT_LOCATION . 'addons.php?type=' . $this->type . '&amp;name=' . $this->id;
        $this->include_min = $data['min_include_ver'];
        $this->include_max = $data['max_include_ver'];

        // load revisions
        if ($load_revisions)
        {
            $this->loadRevisions();
        }
    }

    /**
     * Create an add-on revision
     *
     * @param array  $attributes
     * @param string $file_id
     * @param string $moderator_message
     *
     * @throws AddonException
     */
    public function createRevision($attributes, $file_id, $moderator_message = "")
    {
        $this->checkUserEditPermissions();

        foreach ($attributes['missing_textures'] as $tex)
        {
            $moderator_message .= "Texture not found: $tex\n";
        }

        // Make sure an add-on file with this id does not exist
        if (static::existsRevision($file_id, $this->type))
        {
            throw new AddonException(_h('The file you are trying to create already exists.'));
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
                File::deleteFile($attributes['image']);
                $attributes['image'] = $images[$i]['id'];
                break;
            }
        }

        // Calculate the next revision number
        $rev = max(array_keys($this->revisions)) + 1;

        // Add revision entry
        $fields_data = [
            ":id"       => $file_id,
            ":addon_id" => $this->id,
            ":fileid"   => $attributes['fileid'],
            ":revision" => $rev,
            ":format"   => $attributes['version'],
            ":image"    => $attributes['image'],
            ":status"   => $attributes['status']
        ];

        if ($this->type === static::KART)
        {
            $fields_data[":icon"] = $attributes['image'];
        }

        // Add moderator message if available
        if ($moderator_message)
        {
            $fields_data[":moderator_note"] = $moderator_message;
        }

        try
        {
            DBConnection::get()->insert($this->type . '_revs', $fields_data);
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('create add-on revision')));
        }

        writeXML();
        try
        {
            // Send mail to moderators
            SMail::get()->moderatorNotification(
                'New Addon Upload',
                h(sprintf(
                    "%s has uploaded a new revision for %s '%s' %s",
                    User::getLoggedUserName(),
                    $this->type,
                    $attributes['name'],
                    (string)$this->id
                ))
            );
        }
        catch(SMailException $e)
        {
            throw new AddonException($e->getMessage());
        }

        Log::newEvent("New add-on revision for '{$attributes['name']}'");
    }

    /**
     * Delete an add-on record and all associated files and ratings
     *
     * @throws AddonException
     */
    public function delete()
    {
        $this->checkUserEditPermissions();

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
                [":id" => $this->id]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('find files associated with this addon')));
        }

        foreach ($files as $file)
        {
            try
            {
                File::deleteFileFS(UP_PATH . $file['file_path']);
            }
            catch(FileException $e)
            {
                throw new AddonException(_h('Failed to delete file:') . ' ' . h($file['file_path']));
            }
        }

        // Remove file records associated with addon
        try
        {
            DBConnection::get()->delete("files", "`addon_id` = :id", [":id" => $this->id]);
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('remove file records for this addon')));
        }

        // Remove addon entry
        // FIXME: The two queries above should be included with this one
        // in a transaction, or database constraints added so that the two
        // queries above are no longer needed.
        try
        {
            DBConnection::get()->delete("addons", "`id` = :id", [":id" => $this->id]);
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('remove addon')));
        }

        writeXML();
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
        $this->checkUserEditPermissions();

        if (!File::deleteFile($file_id))
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
        $this->checkUserEditPermissions();

        $rev = (int)$rev;
        if ($rev < 1 || !isset($this->revisions[$rev]))
        {
            throw new AddonException(_h('The revision you are trying to delete does not exist.'));
        }
        if (count($this->revisions) === 1)
        {
            throw new AddonException(_h('You cannot delete the last revision of an add-on.'));
        }
        if (Addon::isLatest($this->revisions[$rev]['status']))
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
            DBConnection::get()->delete(
                $this->type . '_revs',
                "`addon_id` = :id AND `revision` = :revision",
                [
                    ':addon_id' => $this->id,
                    ':revision' => $rev
                ]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('delete revisions')));
        }

        Log::newEvent('Deleted revision ' . $rev . ' of \'' . $this->name . '\'');
        writeXML();
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
    public function getDateCreation()
    {
        return $this->date_creation;
    }

    /**
     * @return int
     */
    public function getIcon()
    {
        return $this->icon;
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
        if (!$this->designer)
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
     * @return array
     */
    public function getLatestRevision()
    {
        return $this->revisions[$this->latest_revision];
    }

    /**
     * Get the last revision  id number
     *
     * @return int
     */
    public function getLatestRevisionID()
    {
        return $this->latest_revision;
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
        return $this->uploader_id;
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

        // Look up file ID
        try
        {
            $file_id_lookup = DBConnection::get()->query(
                'SELECT `fileid`
                FROM `' . DB_PREFIX . $this->type . '_revs`
                WHERE `addon_id` = :addon_id
                AND `revision` = :revision',
                DBConnection::FETCH_FIRST,
                [
                    ':addon_id' => $this->id,
                    ':revision' => $revision
                ]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('look up file ID')));
        }

        if (!$file_id_lookup)
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
                [':id' => $file_id]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('search for file')));
        }

        if (!$file)
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
                [':addon_id' => $this->id]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('fetch associated images associated')));
        }

        $return = [];
        foreach ($paths as $path)
        {
            $return[] = [
                'id'   => $path['id'],
                'path' => $path['file_path'],
                'hash' => md5_file(UP_PATH . $path['file_path'])
            ];
        }

        return $return;
    }

    /**
     * Get the image files associated with this addon
     * This method will silently fail
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
                [
                    ':addon_id'  => $this->id,
                    ':file_type' => 'image'
                ]
            );
        }
        catch(DBException $e)
        {
            return [];
        }

        return $result;
    }

    /**
     * Get all of the source files associated with an addon
     * This method will silently fail
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
                [
                    ':addon_id'  => $this->id,
                    ':file_type' => 'source'
                ]
            );
        }
        catch(DBException $e)
        {
            return [];
        }

        return $result;
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
        $this->checkUserEditPermissions();

        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'addons`
                 SET `description` = :description
                 WHERE `id` = :id',
                DBConnection::NOTHING,
                [
                    ':description' => $description,
                    ':id'          => $this->id
                ]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the description')));
        }

        $this->description = $description;

        writeXML();

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
        $this->checkUserEditPermissions();

        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'addons`
                SET `designer` = :designer
                WHERE `id` = :id',
                DBConnection::NOTHING,
                [
                    ':designer' => $designer,
                    ':id'       => $this->id
                ]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the designer')));
        }

        $this->designer = $designer;

        writeXML();
        return $this;
    }

    /**
     * Set the image for the latest revision of this add-on.
     *
     * @param integer $image_id
     * @param string  $field
     *
     * @return static
     * @throws AddonException
     */
    public function setImage($image_id, $field = 'image')
    {
        $this->checkUserEditPermissions();

        try
        {
            DBConnection::get()->query(
                "UPDATE `" . DB_PREFIX . $this->type . "_revs`
                SET `" . $field . "` = :image_id
                WHERE `addon_id` = :addon_id
                AND `status` & " . F_LATEST,
                DBConnection::NOTHING,
                [
                    ':image_id' => $image_id,
                    ':addon_id' => $this->id
                ]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the image')));
        }

        return $this;
    }

    /**
     * Set the addon include versions
     *
     * @param $start_ver
     * @param $end_ver
     *
     * @return static
     * @throws AddonException
     */
    public function setIncludeVersions($start_ver, $end_ver)
    {
        $this->checkUserEditPermissions();

        Validate::versionString($start_ver);
        Validate::versionString($end_ver);

        try
        {
            DBConnection::get()->query(
                'UPDATE `' . DB_PREFIX . 'addons`
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
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('set the min/max include versions')));
        }

        $this->include_min = $start_ver;
        $this->include_max = $end_ver;

        writeXML();

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
                'UPDATE `' . DB_PREFIX . 'addons`
                SET `license` = :license
                WHERE `id` = :addon_id',
                DBConnection::NOTHING,
                [
                    ':license'  => $license,
                    ':addon_id' => $this->id
                ]
            );
        }
        catch(DBException $e)
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
                'UPDATE `' . DB_PREFIX . 'addons`
                SET `name` = :name
                WHERE `id` = :addon_id',
                DBConnection::NOTHING,
                [
                    ':name'     => $name,
                    ':addon_id' => $this->id
                ]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('update the name')));
        }

        $this->name = $name;

        return $this;
    }

    /**
     * Set notes on this addon
     *
     * @param string $fields
     *
     * @return static
     * @throws AddonException
     */
    public function setNotes($fields)
    {
        $this->checkUserEditPermissions();

        $fields = explode(',', $fields);
        $notes = [];
        foreach ($fields as $field)
        {
            // TODO remove post fields
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
                    [
                        ':moderator_note' => $value,
                        ':addon_id'       => $this->id,
                        ':revision'       => $revision
                    ]
                );
            }
            catch(DBException $e)
            {
                throw new AddonException(exception_message_db(_('update add-on status')));
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
                [
                    ':user_id' => $this->uploader_id,
                ]
            );
        }
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('find user record')));
        }

        try
        {
            SMail::get()->addonNoteNotification($user['email'], $this->name, $email_body);
        }
        catch(SMailException $e)
        {
            throw new AddonException('Failed to send email to user. ' . $e->getMessage());
        }

        Log::newEvent("Added notes to '{$this->name}'");

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
            if (Addon::isApproved($rev['status']))
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
     * @return static
     * @throws AddonException
     */
    public function setStatus($fields)
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

            // TODO remove post fields
            if (isset($_POST[$field])) // field is on most likely
            {
                if ($_POST[$field] === 'on')
                {
                    $is_checked = true;
                }

                if ($field === 'latest') // latest field
                {
                    $revision = (int)$_POST['latest'];
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
                    'UPDATE `' . DB_PREFIX . $this->type . '_revs`
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
            catch(DBException $e)
            {
                throw new AddonException(exception_message_db(_('update add-on status')));
            }
        }

        writeXML();
        Log::newEvent("Set status for add-on '{$this->name}'");

        return $this;
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
        $data = static::getFromField("addons", "id", $addon_id, DBConnection::PARAM_STR, _h('The requested add-on does not exist.'));

        return new Addon($data["id"], $data, $load_revisions);
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
                FROM `' . DB_PREFIX . 'addons`
                WHERE `id` = :id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                [':id' => $id]
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
     * This method will sielntly fail
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
                FROM `' . DB_PREFIX . 'addons`
                WHERE `id` = :id
                LIMIT 1',
                DBConnection::FETCH_FIRST,
                [':id' => $id]
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
            trigger_error("ID is not a string");

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
     * @param string  $type addon type
     *
     * @return array
     */
    public static function filterMenuTemplate($addons, $type)
    {
        $has_permission = User::hasPermission(AccessControl::PERM_EDIT_ADDONS);
        $template_addons = [];

        foreach ($addons as $addon)
        {
            // Get link icon
            if ($addon->getType() === Addon::KART)
            {
                // Make sure an icon file is set for kart
                if ($addon->getImage(true) != 0)
                {
                    $im = Cache::getImage($addon->getImage(true), SImage::SIZE_SMALL);
                    if ($im['exists'] && $im['approved'])
                    {
                        $icon = $im['url'];
                    }
                    else
                    {
                        $icon = IMG_LOCATION . 'kart-icon.png';
                    }
                }
                else
                {
                    $icon = IMG_LOCATION . 'kart-icon.png';
                }
            }
            else
            {
                $icon = IMG_LOCATION . 'track-icon.png';
            }

            // Approved?
            if ($addon->hasApprovedRevision())
            {
                $class = '';
            }
            elseif ($has_permission || $addon->isUserOwner())
            {
                // not approved, see of we are logged in and we have permission
                $class = ' disabled';
            }
            else
            {
                // do not show
                continue;
            }

            $real_url = sprintf("addons.php?type=%s&amp;name=%s", $type, $addon->getId());
            $template_addons[] = [
                "id"          => $addon->getId(),
                "class"       => $class,
                "is_featured" => Addon::isFeatured($addon->getStatus()),
                "name"        => h($addon->getName()),
                "real_url"    => $real_url,
                "image_src"   => $icon,
                "disp"        => File::rewrite($real_url)
            ];
        }

        return $template_addons;
    }

    /**
     * Search for an addon by its name or description
     *
     * @param string $search_query the search query
     * @param string $type         the addon type
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
        if (!Addon::isAllowedType($type) && $type !== "all")
        {
            throw new AddonException(sprintf("Invalid search type = %s is not recognized", $type));
        }

        // build query
        $query = "SELECT id FROM `" . DB_PREFIX . "addons` WHERE";

        // check addon type
        if ($type !== "all")
        {
            $query .= sprintf(" (`type` = '%s') AND", $type);
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
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_('search for add-on')));
        }

        $return_addons = [];
        foreach ($addons as $addon)
        {
            $return_addons[] = static::get($addon["id"]);
        }

        return $return_addons;
    }

    /**
     * Get all the addon's of a type
     *
     * @param string $addon_type   type of addon
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
                  FROM `' . DB_PREFIX . 'addons` `a`
                  LEFT JOIN `' . DB_PREFIX . $addon_type . '_revs` `r`
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
        catch(DBException $e)
        {
            throw new AddonException(exception_message_db(_("select all addons from the database")));
        }

        $return = [];
        foreach ($addons as $addon)
        {
            // TODO select all users with one SQL query
            $return[] = static::get($addon['id']);
        }

        return $return;
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
     * Create a new add-on record and an initial revision
     *
     * @param string $type               Add-on type
     * @param array  $attributes         Contains properties of the add-on. Must have the
     *                                   following elements: name, designer, license, image, fileid, status, (arena)
     * @param string $fileid             ID for revision file (see FIXME below)
     * @param string $moderator_message
     *
     * @throws AddonException
     */
    public static function create($type, $attributes, $fileid, $moderator_message)
    {
        // validate
        if (!User::isLoggedIn())
        {
            throw new AddonException(_h('You must be logged in to create an add-on.'));
        }
        if (!User::hasPermission(AccessControl::PERM_ADD_ADDON))
        {
            throw new AddonException(_h('You do not have the necessary permissions to upload a addon'));
        }
        if (!static::isAllowedType($type))
        {
            throw new AddonException(_h('An invalid add-on type was provided.'));
        }

        foreach ($attributes['missing_textures'] as $tex)
        {
            $moderator_message .= "Texture not found: $tex\n";
        }
        $id = static::generateId($attributes['name']);

        // Make sure the add-on doesn't already exist
        if (static::exists($id))
        {
            throw new AddonException(_h('An add-on with this ID already exists.'));
        }

        // Make sure no revisions with this id exists
        // FIXME: Check if this id is redundant or not. Could just
        //        auto-increment this column if it is unused elsewhere.
        if (static::existsRevision($fileid, $type))
        {
            throw new AddonException(_h('The add-on you are trying to create already exists.'));
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
            throw new AddonException(exception_message_db(_('create your add-on')));
        }

        // Add the first revision
        $rev = 1;

        // Generate revision entry
        $fields_data = [
            ":id"       => $fileid,
            ":addon_id" => $id,
            ":fileid"   => $attributes['fileid'],
            ":revision" => $rev,
            ":format"   => $attributes['version'],
            ":image"    => $attributes['image'],
            ":status"   => $attributes['status']

        ];
        if ($type === static::KART)
        {
            $fields_data[":icon"] = $attributes['image'];
        }

        // Add moderator message if available
        if ($moderator_message)
        {
            $fields_data[":moderator_note"] = $moderator_message;
        }

        try
        {
            DBConnection::get()->insert($type . '_revs', $fields_data);
        }
        catch(DBException $e)
        {
            throw new AddonException($e->getMessage());
        }


        writeXML();
        try
        {
            // Send mail to moderators
            SMail::get()->moderatorNotification(
                'New Addon Upload',
                h(sprintf(
                    "%s has uploaded a new %s '%s' %s",
                    User::getLoggedUserName(),
                    $type,
                    $attributes['name'],
                    (string)$id
                ))
            );
        }
        catch(SMailException $e)
        {
            throw new AddonException($e->getMessage());
        }

        Log::newEvent("New add-on '{$attributes['name']}'");
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
        return static::existsField("addons", "id", Addon::cleanId($id), DBConnection::PARAM_STR);
    }

    /**
     * Check if an addon revision exists in the database
     *
     * @param string $id   Addon ID
     * @param string $type addon type
     *
     * @return bool
     */
    public static function existsRevision($id, $type)
    {
        return static::existsField($type . "_revs", "id", $id, DBConnection::PARAM_STR);
    }

    /**
     * Get the total number of addons of a type
     *
     * @param string $type the addon type
     *
     * @return int
     * @throws AddonException
     */
    public static function count($type)
    {
        assert(static::isAllowedType($type));

        try
        {
            $count = DBConnection::get()->count("addons", "`type` = :type", [":type" => $type]);
        }
        catch(DBException $e)
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
     * @return bool
     */
    public static function isTextureInvalid($status)
    {
        return (bool)($status & F_TEX_NOT_POWER_OF_2);
    }
}
