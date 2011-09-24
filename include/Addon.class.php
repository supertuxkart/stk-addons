<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
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

class Addon {
    public static $allowedTypes = array('karts','tracks','arenas');
    private $type;
    private $id;
    private $name;
    private $uploaderId;
    private $creationDate;
    private $designer;
    private $description;
    private $license;
    private $permalink;
    private $revisions = array();
    private $latestRevision;
    
    /**
     * Instance constructor
     * @param string $id 
     */
    public function Addon($id) {
        $id = Addon::cleanId($id);
        $this->id = $id;

        $query = 'SELECT `type`,`name`,`uploader`,`creation_date`,
                `designer`,`description`,`license`
            FROM `'.DB_PREFIX."addons`
            WHERE `id` = '$id'";

        $handle = sql_query($query);
        if (!$handle)
            throw new AddonException('Failed to read the requested add-on\'s information.');
        
        if (mysql_num_rows($handle) === 0)
            throw new AddonException(htmlspecialchars(_('The requested add-on does not exist.')));

        $result = mysql_fetch_assoc($handle);
        $this->type = $result['type'];
        $this->name = $result['name'];
        $this->uploaderId = $result['uploader'];
        $this->creationDate = $result['creation_date'];
        $this->designer = $result['designer'];
        $this->description = $result['description'];
        $this->license = $result['license'];
        $this->permalink = SITE_ROOT.'addons.php?type='.$this->type.'&amp;name='.$this->id;
        
        // Get revisions
        $revsQuery = 'SELECT *
            FROM `'.DB_PREFIX.$this->type."_revs`
            WHERE `addon_id` = '$this->id'
            ORDER BY `revision` ASC";
        $revsHandle = sql_query($revsQuery);
        if (!$revsHandle)
            throw new AddonException('Failed to read the requested add-on\'s revision information.');

        if (mysql_num_rows($revsHandle) === 0)
            throw new AddonException('No revisions of this add-on exist. This should never happen.');

        for ($i = 1; $i <= mysql_num_rows($revsHandle); $i++) {
            $rev = mysql_fetch_assoc($revsHandle);
            $currentRev = array(
                'file'          => $rev['fileid'],
                'format'        => $rev['format'],
                'image'         => $rev['image'],
                'moderator_note'=> $rev['moderator_note'],
                'status'        => $rev['status'],
                'timestamp'     => $rev['creation_date']
            );
            if ($currentRev['status'] & F_LATEST)
                $this->latestRevision = $rev['revision'];
            $this->revisions[$rev['revision']] = $currentRev;
        }
    }

    public function create($type, $id, $fileid, $attributes)
    {
        global $moderator_message;

        // Check if logged in
        if (!User::$logged_in)
            throw new AddonException('You must be logged in to create an add-on.');
        
        if (!Addon::isAllowedType($type))
            throw new AddonException('An invalid add-on type was provided.');
        
        $this->type = $type;

        // If the addon doesn't exist, create it
        if (!sql_exist('addons', 'id', $id))
        {
            echo htmlspecialchars(_('Creating a new add-on...')).'<br />';
            $fields = array('id','type','name','uploader','designer','license');
            $values = array($id,$this->type,
                mysql_real_escape_string($attributes['name']),
                mysql_real_escape_string(User::$user_id),
                mysql_real_escape_string($attributes['designer']),
                mysql_real_escape_string($attributes['license']));
            if ($this->type == 'tracks')
            {
                $fields[] = 'props';
                if ($attributes['arena'] == 'Y')
                    $values[] = '1';
                else
                    $values[] = '0';
            }
            if (!sql_insert('addons',$fields,$values))
                return false;
        }
        else
        {
            echo htmlspecialchars(_('This add-on already exists. Adding revision...')).'<br />';
            // Update the addon name
            if (!sql_update('addons',
                    'id',mysql_real_escape_string($id),
                    'name',mysql_real_escape_string($attributes['name'])))
            {
                echo '<span class="error">'.htmlspecialchars(_('Failed to update the name record for this add-on.')).'</span><br />';
            }
            // Update license file record
            if (!sql_update('addons',
                    'id',
                    mysql_real_escape_string($id),
                    'license',
                    mysql_real_escape_string($attributes['license'])))
            {
                echo '<span class="error">'.htmlspecialchars(_('Failed to update the license record for this add-on.')).'</span><br />';
            }
        }

        // Add the new revision
        $prevRevQuerySql = 'SELECT `revision` FROM '.DB_PREFIX.$this->addonType.'_revs
            WHERE `addon_id` = \''.$id.'\' ORDER BY `revision` DESC LIMIT 1';
        $reqSql = sql_query($prevRevQuerySql);
        if (!$reqSql)
        {
            echo '<span class="error">'.htmlspecialchars(_('Failed to check for previous add-on revisions.')).'</span><br />';
            return false;
        }
        if (mysql_num_rows($reqSql) == 0)
        {
            $rev = 1;
        }
        else
        {
            $result = mysql_fetch_assoc($reqSql);
            $rev = $result['revision'] + 1;
        }
        // Add revision entry
        $fields = array('id','addon_id','fileid','revision','format','image','status');
        $values = array($fileid,$id,$attributes['fileid'],$rev,
            mysql_real_escape_string($attributes['version']),
            $attributes['image'],$attributes['status']);
        if ($this->addonType == 'karts')
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
        if (!sql_insert($this->addonType.'_revs',$fields,$values))
            return false;
        // Send mail to moderators
        moderator_email('New Addon Upload',
                "{$_SESSION['user']} has uploaded a new file for the {$this->addonType} \'{$attributes['name']}\' ($id)");
        return true;
    }
    
    public function createRevision($id, $fileid, $attributes) {
        // Check if logged in
        if (!User::$logged_in)
            throw new AddonException('You must be logged in to create an add-on revision.');
        
        if (!$this->type)
            throw new AddonException('The add-on type was not set yet.');

        // Make sure an add-on file with this id does not exist
        if(sql_exist($this->type.'_revs', 'id', $fileid))
            throw new AddonException(htmlspecialchars(_('The file you are trying to create already exists.')));
    }
    
    /**
     * Check if an add-on of the specified ID exists
     * @param string $addon_id Addon ID
     * @return boolean
     */
    public static function exists($addon_id) {
        if (!sql_exist('addons', 'id', Addon::cleanId($addon_id))) {
            return false;
        }
        return true;
    }
    
    /**
     * Delete an add-on record and all associated files and ratings
     */
    public function delete() {
        if (!User::$logged_in)
            throw new AddonException(htmlentities(_('You must be logged in to perform this action.')));

        if($_SESSION['role']['manageaddons'] != true)
            throw new AddonException(htmlentities(_('You do not have the necessary permissions to perform this action.')));

        // Remove files associated with this addon
        $get_files_query = 'SELECT *
            FROM `'.DB_PREFIX."files`
            WHERE `addon_id` = '$this->id'";
        $get_files_handle = sql_query($get_files_query);
        if (!$get_files_handle)
            throw new AddonException(htmlspecialchars(_('Failed to find files associated with this addon.')));

        $num_files = mysql_num_rows($get_files_handle);
        for ($i = 1; $i <= $num_files; $i++)
        {
            $get_file = mysql_fetch_assoc($get_files_handle);
            if (file_exists(UP_LOCATION.$get_file['file_path']) && !unlink(UP_LOCATION.$get_file['file_path']))
                echo '<span class="error">'.htmlspecialchars(_('Failed to delete file:')).' '.$get_file['file_path'].'</span><br />';
        }
        
        // Remove file records associated with addon
        $remove_file_query = 'DELETE FROM `'.DB_PREFIX.'files`
            WHERE `addon_id` = \''.$this->id.'\'';
        $remove_file_handle = sql_query($remove_file_query);
        if (!$remove_file_handle)
            echo '<span class="error">'.htmlspecialchars(_('Failed to remove file records for this addon.')).'</span><br />';

        // Remove ratings associated with add-on
        $ratings = new Ratings($this->id);
        if (!$ratings->delete())
            echo '<span class="error">'.htmlspecialchars(_('Failed to remove ratings for this add-on.')).'</span><br />';
        
        // Delete revisions
        $revsQuery = 'DELETE FROM `'.DB_PREFIX.$this->type.'_revs`
            WHERE `addon_id` = \''.$this->id.'\'';
        $revsHandle = sql_query($revsQuery);
        if (!$revsHandle)
            throw new AddonException(htmlspecialchars(_('Failed to remove add-on revisions.')));

        // Remove addon entry
        if (!sql_remove_where('addons', 'id', $this->id))
            throw new AddonException(htmlspecialchars(_('Failed to remove addon.')));

        writeAssetXML();
        writeNewsXML();
    }
    
    public function deleteFile($file_id) {
        if (!$_SESSION['role']['manageaddons'] && $this->uploaderID != User::$user_id)
            throw new AddonException(htmlentities(_('You do not have the necessary permissions to perform this action.')));
        
        if (!File::delete($file_id))
            throw new AddonException(htmlentities(_('Failed to delete file.')));
    }

    public static function generateId($type,$name)
    {
        if (!is_string($name))
            return false;

        $addon_id = Addon::cleanId($name);
        if (!$addon_id)
            return false;

        // Check database
        while(sql_exist('addons', 'id', $addon_id))
        {
            // If the addon id already exists, add an incrementing number to it
            if (preg_match('/^.+_([0-9]+)$/i', $addon_id, $matches))
            {
                $next_num = (int)$matches[1];
                $next_num++;
                $addon_id = str_replace($matches[1],$next_num,$addon_id);
            }
            else
            {
                $addon_id .= '_1';
            }
        }
        return $addon_id;
    }
    
    public function getAllRevisions() {
        return $this->revisions;
    }
    
    public function getDescription() {
        return htmlentities($this->description);
    }
    
    public function getDesigner() {
        if ($this->designer == NULL)
            return htmlspecialchars(_('Unknown'));
        return $this->designer;
    }
    
    public function getLatestRevision() {
        return $this->revisions[$this->latestRevision];
    }
    
    public function getType() {
        return $type;
    }
    
    public function getLicense() {
        return $this->license;
    }
    
    public function getLink() {
        return $this->permalink;
    }

    /**
     * Get the path to the requested add-on file
     * @param integer $revision Revision number
     * @return string File path relative to the asset directory
     */
    public function getFile($revision) {
        if (!is_int($revision))
            throw new AddonException('An invalid revision was provided.');

        // Look up file ID
        $look_up_query = 'SELECT `fileid`
            FROM `'.DB_PREFIX.$this->type.'_revs`
            WHERE `addon_id` = \''.$this->id.'\'
            AND `revision` = '.$revision;
        $look_up_handle = sql_query($look_up_query);
        if (!$look_up_handle)
            throw new AddonException('Failed to look up file ID');
        if (mysql_num_rows($look_up_handle) === 0)
            throw new AddonException('There is no add-on found with the specified revision number.');
        $look_up = mysql_fetch_assoc($look_up_handle);
        
        $file_id = $look_up['fileid'];

        // Look up file path from database
        $query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id.'
            LIMIT 1';
        $handle = sql_query($query);
        if (!$handle)
            throw new AddonException('Failed to search for the file in the database.');
        if (mysql_num_rows($handle) == 0)
            throw new AddonException('The requested file does not have an associated file record.');
        $file = mysql_fetch_assoc($handle);
        return $file['file_path'];
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function getImage($icon = false) {
        if ($icon === false)
            return $this->image;
        return $this->icon;
    }

    public static function getName($id)
    {
        if ($id == false)
            return false;
        $id = Addon::cleanId($id);
        $query = 'SELECT `name`
            FROM `'.DB_PREFIX.'addons`
            WHERE `id` = \''.$id.'\'
            LIMIT 1';
        $handle = sql_query($query);
        if (!$handle)
            return false;
        if (mysql_num_rows($handle) == 0)
            return false;
        $result = mysql_fetch_assoc($handle);
        return $result['name'];
    }
    
    public static function isAllowedType($type) {
        if (in_array($type, Addon::$allowedTypes)) {
            return true;
        }
        return false;
    }
    
    public static function cleanId($id) {
        if (!is_string($id))
            return false;
        $length = strlen($id);
        if ($length == 0)
            return false;
        $id = strtolower($id);
        // Validate all characters in addon id
        // Rather than using str_replace, and removing bad characters,
        // it makes more sense to only allow certain characters
        for ($i = 0; $i<$length; $i++)
        {
            $substr = substr($id,$i,1);
            if (!preg_match('/^[a-z0-9\-_]$/i',$substr))
                $substr = '-';
            $id = substr_replace($id,$substr,$i,1);
        }
        return $id;
    }
    
    public function setNotes($fields) {
        if (!$_SESSION['role']['manageaddons'])
            throw new AddonException(htmlspecialchars(_('You do not have the neccessary permissions to perform this action.')));

        $fields = explode(',',$fields);
        $notes = array();
        foreach ($fields AS $field)
        {
            if (!isset($_POST[$field]))
                $_POST[$field] = NULL;
            $fieldinfo = explode('-',$field);
            $revision = (int)$fieldinfo[1];
            // Update notes
            $notes[$revision] = mysql_real_escape_string($_POST[$field]);
        }
        $error = 0;
        // Save record in database
        foreach ($notes AS $revision => $value)
        {
            $query = 'UPDATE `'.DB_PREFIX.$this->type.'_revs`
                SET `moderator_note` = \''.$value.'\'
                WHERE `addon_id` = \''.$this->id.'\'
                AND `revision` = '.$revision;
            $reqSql = sql_query($query);
            if (!$reqSql)
                throw new AddonException('Failed to set moderator note.');
        }

        // Generate email
        $email_body = NULL;
        foreach ($notes AS $revision => $value)
        {
            $email_body .= '<strong>Revision '.$revision.'</strong><br /><br />';
            $value = nl2br(strip_tags($value));
            $email_body .= $value.'<br /><br />';
        }
        // Get uploader email address
        $user = $this->uploaderId;
        $userQuery = 'SELECT `name`,`email` FROM `'.DB_PREFIX.'users`
            WHERE `id` = '.(int)$user.' LIMIT 1';
        $userHandle = sql_query($userQuery);
        if (!$userHandle)
            throw new AddonException('Failed to find user record.');

        $result = mysql_fetch_assoc($userHandle);
        sendMail($result['email'],
                'moderatorNotification',
                array($this->name,
                $this->permalink,
                $email_body,
                $result['name']));
    }
    
    public function getUploader() {
        return $this->uploaderId;
    }
    
    public function setStatus($fields) {
        $fields = explode(',',$fields);
        $status = array();
        // Iterate through each field
        foreach ($fields AS $field)
        {
            if (!isset($_POST[$field]))
                $_POST[$field] = NULL;
            if ($field == 'latest')
                $fieldinfo = array('',(int)$_POST['latest']);
            else
                $fieldinfo = explode('-',$field);
            // Initialize the status of the current revision if it has
            // not been created yet.
            if (!isset($status[$fieldinfo[1]]))
                $status[$fieldinfo[1]] = 0;
            if ($field == 'latest')
            {
                $status[(int)$_POST['latest']] += F_LATEST;
                continue;
            }
            // Update status values for all flags
            if ($_POST[$field] == 'on')
            {
                $revision = (int)$fieldinfo[1];
                switch ($fieldinfo[0])
                {
                    default: break;
                    case 'approved':
                        $status[$revision] += F_APPROVED;
                        break;
                    case 'invisible':
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
                        $status[$revision] += F_DFSG;
                        break;
                    case 'featured':
                        $status[$revision] += F_FEATURED;
                        break;
                }
            }
        }
        $error = 0;
        foreach ($status AS $revision => $value)
        {
            // Check if F_TEX_NOT_POWER_OF_2 is set in database
            $getStatusQuery = 'SELECT `status`
                FROM `'.DB_PREFIX.$this->type.'_revs`
                WHERE `addon_id` = \''.$this->id.'\'
                AND `revision` = '.$revision;
            $getStatusSql = sql_query($getStatusQuery);
            if (!$getStatusSql)
                throw new AddonException('Failed to read status from the database.');
            
            $getStatusResult = mysql_fetch_assoc($getStatusSql);
            if ($getStatusResult['status'] & F_TEX_NOT_POWER_OF_2)
                $value += F_TEX_NOT_POWER_OF_2;

            // Write new addon
            $query = 'UPDATE `'.DB_PREFIX.$this->type.'_revs`
                SET `status` = '.$value.'
                WHERE `addon_id` = \''.$this->id.'\'
                AND `revision` = '.$revision;
            $reqSql = sql_query($query);
            if (!$reqSql)
                throw new AddonException('Failed to write add-on status.');
        }
        writeAssetXML();
        writeNewsXML();
    }
}
?>
