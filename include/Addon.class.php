<?php
/**
 * Copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
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

require_once(INCLUDE_DIR . 'DBConnection.class.php');
require_once(INCLUDE_DIR . "sql.php");

class Addon
{
    public static $allowedTypes = array('karts', 'tracks', 'arenas');

    private $type;

    private $id;

    private $uploaderID;

    private $image = 0;

    private $icon = 0;

    private $name;

    private $uploaderId;

    private $creationDate;

    private $designer;

    private $description;

    private $license;

    private $permalink;

    private $revisions = array();

    private $latestRevision;

    private $minInclude;

    private $maxInclude;

    /**
     * Instance constructor
     *
     * @param string $id
     *
     * @throws AddonException
     */
    public function Addon($id)
    {
        $this->id = Addon::cleanId($id);

        try
        {
            $result = DBConnection::get()->query(
                    'SELECT `type`,`name`,`uploader`,`creation_date`,
                     `designer`,`description`,`license`,`min_include_ver`,`max_include_ver`
                     FROM `' . DB_PREFIX . 'addons`
                     WHERE `id` = :id',
                    DBConnection::FETCH_ALL,
                    array(':id' => (string)$this->id)
            );
        }
        catch(DBException $e)
        {
            throw new AddonException('Failed to read the requested add-on\'s information.');
        }

        if (empty($result))
        {
            throw new AddonException(htmlspecialchars(_('The requested add-on does not exist.')));
        }

        $this->type = $result[0]['type'];
        $this->name = $result[0]['name'];
        $this->uploaderId = $result[0]['uploader'];
        $this->creationDate = $result[0]['creation_date'];
        $this->designer = $result[0]['designer'];
        $this->description = $result[0]['description'];
        $this->license = $result[0]['license'];
        $this->permalink = SITE_ROOT . 'addons.php?type=' . $this->type . '&amp;name=' . $this->id;
        $this->minInclude = $result[0]['min_include_ver'];
        $this->maxInclude = $result[0]['max_include_ver'];

        // Get revisions
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
            throw new AddonException('Failed to read the requested add-on\'s revision information.');
        }

        if (empty($revisions))
        {
            throw new AddonException('No revisions of this add-on exist. This should never happen.');
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
     * Create a new add-on record and an intial revision
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
            echo '<span class="warning">' . htmlspecialchars(
                            sprintf(_('Texture not found: %s'), $tex)
                    ) . '</span><br />';
        }

        // Check if logged in
        if (!User::$logged_in)
        {
            throw new AddonException('You must be logged in to create an add-on.');
        }

        if (!Addon::isAllowedType($type))
        {
            throw new AddonException('An invalid add-on type was provided.');
        }

        $id = Addon::generateId($type, $attributes['name']);

        // Make sure the add-on doesn't already exist
        if (Addon::exists($id))
        {
            throw new AddonException(htmlspecialchars(
                    _('An add-on with this ID already exists. Please try to upload your add-on again later.')
            ));
        }

        // Make sure no revisions with this id exists
        // FIXME: Check if this id is redundant or not. Could just
        //        auto-increment this column if it is unused elsewhere.
        try
        {
            $rows = DBConnection::get()->query(
                    'SELECT * FROM ' . DB_PREFIX . $type . '_revs WHERE `id` = :id',
                    DBConnection::ROW_COUNT,
                    array(':id' => (string)$fileid)
            );
            if ($rows)
            {
                throw new AddonException(htmlspecialchars(_('The add-on you are trying to create already exists.')));
            }
        }
        catch(DBException $e)
        {
            throw new AddonException(sprintf('Failed to acces the %s_revs table.', $type));
        }


        echo htmlspecialchars(_('Creating a new add-on...')) . '<br />';
        $fields = array('id', 'type', 'name', 'uploader', 'designer', 'license');
        $values = array(
                $id,
                $type,
                $attributes['name'],
                User::$user_id,
                $attributes['designer'],
                $attributes['license']
        );
        if ($type === 'tracks')
        {
            $fields[] = 'props';
            if ($attributes['arena'] === 'Y')
            {
                $values[] = '1';
            }
            else
            {
                $values[] = '0';
            }
        }
        try
        {
            DBConnection::get()->insert("addons", array_combine($fields, $values));
        }
        catch(DBException $e)
        {
            throw new AddonException(htmlspecialchars(_('Your add-on could not be uploaded.')));
        }


        // Add the first revision
        $rev = 1;

        // Generate revision entry
        $fields = array('id', 'addon_id', 'fileid', 'revision', 'format', 'image', 'status');
        $values = array(
                $fileid,
                $id,
                $attributes['fileid'],
                $rev,
                $attributes['version'],
                $attributes['image'],
                $attributes['status']
        );
        if ($type === 'karts')
        {
            $fields[] = 'icon';
            $values[] = $attributes['image'];
        }

        // Add moderator message if available
        if (!empty($moderator_message))
        {
            $fields[] = 'moderator_note';
            $values[] = $moderator_message;
        }
        try
        {
            DBConnection::get()->insert($type . '_revs', array_combine($fields, $values));
        }
        catch(DBException $e)
        {
            return false;
        }

        // Send mail to moderators
        moderator_email(
                'New Addon Upload',
                "{$_SESSION['user']} has uploaded a new {$type} '{$attributes['name']}' ($id)"
        );
        writeAssetXML();
        writeNewsXML();
        Log::newEvent("New add-on '{$attributes['name']}'");

        return new Addon($id);
    }

    /**
     * Create an add-on revision
     *
     * @param array  $attributes
     * @param string $fileid
     *
     * @throws AddonException
     */
    public function createRevision($attributes, $fileid)
    {
        global $moderator_message;
        foreach ($attributes['missing_textures'] as $tex)
        {
            $moderator_message .= "Texture not found: $tex\n";
            echo '<span class="warning">' . htmlspecialchars(
                            sprintf(_('Texture not found: %s'), $tex)
                    ) . '</span><br />';
        }

        // Check if logged in
        if (!User::$logged_in)
        {
            throw new AddonException('You must be logged in to create an add-on revision.');
        }

        // Make sure an add-on file with this id does not exist
        try
        {
            $rows = DBConnection::get()->query(
                    'SELECT * FROM ' . DB_PREFIX . $this->type . '_revs WHERE `id` = :id',
                    DBConnection::ROW_COUNT,
                    array(':id' => (string)$fileid)
            );
            if ($rows)
            {
                throw new AddonException(htmlspecialchars(_('The file you are trying to create already exists.')));
            }
        }
        catch(DBException $e)
        {
            throw new AddonException(sprintf('Failed to acces the %s_revs table.', $this->type));
        }

        // Make sure user has permission to upload a new revision for this add-on
        if (User::$user_id !== $this->uploaderId && !$_SESSION['role']['manageaddons'])
        {
            throw new AddonException(htmlspecialchars(
                    _('You do not have the necessary permissions to perform this action.')
            ));
        }

        // Update the addon name
        $this->setName($attributes['name']);

        // Update license file record
        $this->setLicense($attributes['license']);

        // Prevent duplicate images from being created.
        $images = $this->getImageHashes();

        // Compare with new image
        $new_image = File::getPath($attributes['image']);
        $new_hash = md5_file(UP_LOCATION . $new_image);
        for ($i = 0; $i < count($images); $i++)
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
        $fields = array('id', 'addon_id', 'fileid', 'revision', 'format', 'image', 'status');
        $values = array(
                $fileid,
                $this->id,
                $attributes['fileid'],
                $rev,
                $attributes['version'],
                $attributes['image'],
                $attributes['status']
        );
        if ($this->type === 'karts')
        {
            $fields[] = 'icon';
            $values[] = $attributes['image'];
        }

        // Add moderator message if available
        if (!empty($moderator_message))
        {
            $fields[] = 'moderator_note';
            $values[] = $moderator_message;
        }
        try
        {
            DBConnection::get()->insert($this->type . '_revs', array_combine($fields, $values));
        }
        catch(DBException $e)
        {
            throw new AddonException('Failed to create add-on revision.');
        }

        // Send mail to moderators
        moderator_email(
                'New Addon Upload',
                "{$_SESSION['user']} has uploaded a new revision for {$this->type} '{$attributes['name']}' ($this->id)"
        );
        writeAssetXML();
        writeNewsXML();
        Log::newEvent("New add-on revision for '{$attributes['name']}'");
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

            return ($num === 1);
        }
        catch(DBException $e)
        {
            return false;
        }
    }

    /**
     * Delete an add-on record and all associated files and ratings
     *
     * @throws AddonException
     */
    public function delete()
    {
        if (!User::$logged_in)
        {
            throw new AddonException(htmlentities(_('You must be logged in to perform this action.')));
        }

        if ($_SESSION['role']['manageaddons'] !== true && User::$user_id !== $this->uploaderId)
        {
            throw new AddonException(htmlentities(
                    _('You do not have the necessary permissions to perform this action.')
            ));
        }

        // Remove cache files for this add-on
        Cache::clearAddon($this->id);

        // Remove files associated with this addon
        $get_files_query = 'SELECT *
            FROM `' . DB_PREFIX . "files`
            WHERE `addon_id` = '$this->id'";
        $get_files_handle = sql_query($get_files_query);
        if (!$get_files_handle)
        {
            throw new AddonException(htmlspecialchars(_('Failed to find files associated with this addon.')));
        }

        $num_files = mysql_num_rows($get_files_handle);
        for ($i = 1; $i <= $num_files; $i++)
        {
            $get_file = mysql_fetch_assoc($get_files_handle);
            if (file_exists(UP_LOCATION . $get_file['file_path']) && !unlink(UP_LOCATION . $get_file['file_path']))
            {
                echo '<span class="error">' . htmlspecialchars(
                                _('Failed to delete file:')
                        ) . ' ' . $get_file['file_path'] . '</span><br />';
            }
        }

        // Remove file records associated with addon
        $remove_file_query = 'DELETE FROM `' . DB_PREFIX . 'files`
            WHERE `addon_id` = \'' . $this->id . '\'';
        $remove_file_handle = sql_query($remove_file_query);
        if (!$remove_file_handle)
        {
            echo '<span class="error">' . htmlspecialchars(
                            _('Failed to remove file records for this addon.')
                    ) . '</span><br />';
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
            throw new AddonException(htmlspecialchars(_('Failed to remove addon.')));
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
        if (!$_SESSION['role']['manageaddons'] && $this->uploaderID !== User::$user_id)
        {
            throw new AddonException(htmlspecialchars(
                    _('You do not have the necessary permissions to perform this action.')
            ));
        }

        if (!File::delete($file_id))
        {
            throw new AddonException(htmlspecialchars(_('Failed to delete file.')));
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
        if (!$_SESSION['role']['manageaddons'] && $this->uploaderID !== User::$user_id)
        {
            throw new AddonException(htmlspecialchars(
                    _('You do not have the necessary permissions to perform this action.')
            ));
        }
        $rev = (int)$rev;
        if ($rev < 1 || !isset($this->revisions[$rev]))
        {
            throw new AddonException(htmlspecialchars(_('The revision you are trying to delete does not exist.')));
        }
        if (count($this->revisions) === 1)
        {
            throw new AddonException(htmlspecialchars(_('You cannot delete the last revision of an add-on.')));
        }
        if (($this->revisions[$rev]['status'] & F_LATEST))
        {
            throw new AddonException(htmlspecialchars(
                    _(
                            'You cannot delete the latest revision of an add-on. Please mark a different revision to be the latest revision first.'
                    )
            ));
        }

        // Queue addon file for deletion
        if (!File::queueDelete($this->revisions[$rev]['file']))
        {
            throw new AddonException(htmlspecialchars(_('The add-on file could not be queued for deletion.')));
        }

        // Remove the revision record from the database
        $query = 'DELETE FROM `' . DB_PREFIX . $this->type . '_revs`
            WHERE `addon_id` = \'' . $this->id . '\'
            AND `revision` = ' . $rev;
        $handle = sql_query($query);
        if (!$handle)
        {
            throw new AddonException(htmlspecialchars(_('The add-on revision could not be deleted.')));
        }

        Log::newEvent('Deleted revision ' . $rev . ' of \'' . $this->name . '\'');
        writeAssetXML();
        writeNewsXML();
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

        $addon_id = Addon::cleanId($name);
        if (!$addon_id)
        {
            return false;
        }

        // Check database
        while (Addon::exists($addon_id))
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
     * Get all the addon's of a type
     *
     * @param string $type
     * @param bool $featuredFirst
     *
     * @return array
     */
    public static function getAddonList($type, $featuredFirst = false)
    {
        if (!Addon::isAllowedType($type))
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
     * Get all the revisions
     *
     * @return array
     */
    public function getAllRevisions()
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
        return htmlspecialchars($this->description);
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
            return htmlspecialchars(_('Unknown'));
        }

        return $this->designer;
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
            throw new AddonException('An invalid revision was provided.');
        }

        // Look up file ID
        $look_up_query = 'SELECT `fileid`
            FROM `' . DB_PREFIX . $this->type . '_revs`
            WHERE `addon_id` = \'' . $this->id . '\'
            AND `revision` = ' . $revision;
        $look_up_handle = sql_query($look_up_query);
        if (!$look_up_handle)
        {
            throw new AddonException('Failed to look up file ID');
        }
        if (mysql_num_rows($look_up_handle) === 0)
        {
            throw new AddonException('There is no add-on found with the specified revision number.');
        }
        $look_up = mysql_fetch_assoc($look_up_handle);

        $file_id = $look_up['fileid'];

        // Look up file path from database
        $query = 'SELECT `file_path` FROM `' . DB_PREFIX . 'files`
            WHERE `id` = ' . (int)$file_id . '
            LIMIT 1';
        $handle = sql_query($query);
        if (!$handle)
        {
            throw new AddonException('Failed to search for the file in the database.');
        }
        if (mysql_num_rows($handle) === 0)
        {
            throw new AddonException('The requested file does not have an associated file record.');
        }
        $file = mysql_fetch_assoc($handle);

        return $file['file_path'];
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
     * Get the md5sums of all the image files of this addon
     *
     * @throws AddonException
     *
     * @return array
     */
    public function getImageHashes()
    {
        $paths_query = 'SELECT `id`, `file_path`
            FROM `' . DB_PREFIX . 'files`
            WHERE `addon_id` = \'' . $this->id . '\'
            AND `file_type` = \'image\'
            LIMIT 50';
        $paths_handle = sql_query($paths_query);
        if (!$paths_handle)
        {
            throw new AddonException('DB error when fetching images associated with this add-on.');
        }
        if (mysql_num_rows($paths_handle) === 0)
        {
            return array();
        }

        $return = array();
        for ($i = 0; $i < mysql_num_rows($paths_handle); $i++)
        {
            $result = mysql_fetch_assoc($paths_handle);
            $row = array(
                    'id'   => $result['id'],
                    'path' => $result['file_path'],
                    'hash' => md5_file(UP_LOCATION . $result['file_path'])
            );
            $return[] = $row;
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
                            ':file_type' => (string)'image'
                    )
            );

            return $result;
        }
        catch(DBException $e)
        {
            return array();
        }
    }

    /**
     * Get the minimum supertuxkart version that this addon supports
     *
     * @return string
     */
    public function getIncludeMin()
    {
        return $this->minInclude;
    }

    /**
     * Get the maximum supertuxkart version that this addon supports
     *
     * @return string
     */
    public function getIncludeMax()
    {
        return $this->maxInclude;
    }

    /**
     * Get the addon name
     *
     * @param int $id
     *
     * @return string
     */
    public static function getName($id)
    {
        if ($id === false)
        {
            return false;
        }
        $id = Addon::cleanId($id);
        $query = 'SELECT `name`
            FROM `' . DB_PREFIX . 'addons`
            WHERE `id` = \'' . $id . '\'
            LIMIT 1';
        $handle = sql_query($query);
        if (!$handle)
        {
            return false;
        }
        if (mysql_num_rows($handle) === 0)
        {
            return false;
        }
        $result = mysql_fetch_assoc($handle);

        return $result['name'];
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

            return $result;
        }
        catch(DBException $e)
        {
            return array();
        }
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
        if (in_array($type, Addon::$allowedTypes))
        {
            return true;
        }

        return false;
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
     *
     * @throws AddonException
     *
     * @return array Matching addon id, name and type
     */
    public static function search($search_query)
    {
        $search_query = mysql_real_escape_string($search_query);

        $query = 'SELECT `id`, `name`, `type`
            FROM `' . DB_PREFIX . "addons`
            WHERE `name` LIKE '%$search_query%'
            OR `description` LIKE '%$search_query%'";

        $handle = sql_query($query);
        if (!$handle)
        {
            throw new AddonException(htmlspecialchars(_('Search failed!')));
        }

        $result = array();
        for ($i = 0; $i < mysql_num_rows($handle); $i++)
        {
            $result[] = mysql_fetch_assoc($handle);
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
        if (!User::$logged_in || (!$_SESSION['role']['manageaddons'] && $this->uploaderId !== User::$user_id))
        {
            throw new AddonException(htmlspecialchars(
                    _('You do not have the neccessary permissions to perform this action.')
            ));
        }

        $updateQuery = 'UPDATE `' . DB_PREFIX . 'addons`
            SET `description` = \'' . mysql_real_escape_string(strip_tags($description)) . '\'
            WHERE `id` = \'' . $this->id . '\'';
        $updateSql = sql_query($updateQuery);

        if (!$updateSql)
        {
            throw new AddonException(htmlspecialchars(_('Failed to update the description record for this add-on.')));
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
        if (!User::$logged_in || (!$_SESSION['role']['manageaddons'] && $this->uploaderId !== User::$user_id))
        {
            throw new AddonException(htmlspecialchars(
                    _('You do not have the neccessary permissions to perform this action.')
            ));
        }

        $updateQuery = 'UPDATE `' . DB_PREFIX . 'addons`
            SET `designer` = \'' . mysql_real_escape_string(strip_tags($designer)) . '\'
            WHERE `id` = \'' . $this->id . '\'';
        $updateSql = sql_query($updateQuery);

        if (!$updateSql)
        {
            throw new AddonException(htmlspecialchars(_('Failed to update the designer record for this add-on.')));
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
        if (!$_SESSION['role']['manageaddons'] && $this->uploaderId !== User::$user_id)
        {
            throw new AddonException(htmlspecialchars(
                    _('You do not have the neccessary permissions to perform this action.')
            ));
        }

        $set_image_query = 'UPDATE `' . DB_PREFIX . $this->type . '_revs`
            SET `' . $field . '` = ' . (int)$image_id . '
            WHERE `addon_id` = \'' . $this->id . '\'
            AND `status` & ' . F_LATEST;
        $set_image_handle = sql_query($set_image_query);
        if (!$set_image_handle)
        {
            throw new AddonException(htmlspecialchars(_('Failed to update the image record for this add-on.')));
        }
    }

    public function setIncludeVersions($start_ver, $end_ver)
    {
        if (!$_SESSION['role']['manageaddons'])
        {
            throw new AddonException(htmlspecialchars(
                    _('You do not have the neccessary permissions to perform this action.')
            ));
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
            throw new AddonException('An error occurred while setting the min/max include versions.');
        }
    }

    /**
     * Set the license of this addon
     *
     * @param string $license
     *
     * @throws AddonException
     */
    private function setLicense($license)
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
            throw new AddonException(htmlspecialchars(_('Failed to update the license record for this add-on.')));
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
    private function setName($name)
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
            throw new AddonException(htmlspecialchars(_('Failed to update the name record for this add-on.')));
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
        if (!$_SESSION['role']['manageaddons'])
        {
            throw new AddonException(htmlspecialchars(
                    _('You do not have the neccessary permissions to perform this action.')
            ));
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
            $notes[$revision] = mysql_real_escape_string($_POST[$field]);
        }

        // Save record in database
        foreach ($notes as $revision => $value)
        {
            $query = 'UPDATE `' . DB_PREFIX . $this->type . '_revs`
                SET `moderator_note` = \'' . $value . '\'
                WHERE `addon_id` = \'' . $this->id . '\'
                AND `revision` = ' . $revision;
            $reqSql = sql_query($query);
            if (!$reqSql)
            {
                throw new AddonException('Failed to set moderator note.');
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
        $user = $this->uploaderId;
        $userQuery = 'SELECT `name`,`email` FROM `' . DB_PREFIX . 'users`
            WHERE `id` = ' . (int)$user . ' LIMIT 1';
        $userHandle = sql_query($userQuery);
        if (!$userHandle)
        {
            throw new AddonException('Failed to find user record.');
        }

        $result = mysql_fetch_assoc($userHandle);
        try
        {
            $mail = new SMail;
            $mail->addonNoteNotification($result['email'], $this->id, $email_body);
        }
        catch(Exception $e)
        {
            throw new AddonException('Failed to send email to user. ' . $e->getMessage());
        }
        Log::newEvent("Added notes to '{$this->name}'");
    }

    /**
     * Get the id of the uploader
     *
     * @return int the id of the uploader
     */
    public function getUploader()
    {
        return $this->uploaderId;
    }

    /**
     * Check if any of an addon's revisions have been approved
     *
     * @return boolean
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
            if ($_SESSION['role']['manageaddons'])
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
                $fieldinfo = array('', (int)$_POST['latest']);
            }
            else
            {
                $fieldinfo = explode('-', $field);
            }

            // Initialize the status of the current revision if it has
            // not been created yet.
            if (!isset($status[$fieldinfo[1]]))
            {
                $status[$fieldinfo[1]] = 0;
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
                $revision = (int)$fieldinfo[1];
                switch ($fieldinfo[0])
                {
                    default:
                        break;
                    case 'approved':
                        if (!$_SESSION['role']['manageaddons'])
                        {
                            break;
                        }
                        $status[$revision] += F_APPROVED;
                        break;
                    case 'invisible':
                        if (!$_SESSION['role']['manageaddons'])
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
                        if (!$_SESSION['role']['manageaddons'])
                        {
                            break;
                        }
                        $status[$revision] += F_DFSG;
                        break;
                    case 'featured':
                        if (!$_SESSION['role']['manageaddons'])
                        {
                            break;
                        }
                        $status[$revision] += F_FEATURED;
                        break;
                }
            }
        }

        // Loop through each addon revision
        foreach ($status as $revision => $value)
        {
            // Write new addon status
            $query = 'UPDATE `' . DB_PREFIX . $this->type . '_revs`
                SET `status` = ' . $value . '
                WHERE `addon_id` = \'' . $this->id . '\'
                AND `revision` = \'' . $revision . '\'';
            $reqSql = sql_query($query);
            if (!$reqSql)
            {
                throw new AddonException('Failed to write add-on status.');
            }
        }
        writeAssetXML();
        writeNewsXML();
        Log::newEvent("Set status for add-on '{$this->name}'");
    }
}
