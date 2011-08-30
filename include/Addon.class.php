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
    
    public function getType() {
        return $type;
    }
    
    public static function getName($id)
    {
        if ($id == false)
            return false;
        $id = addon_id_clean($id);
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
}
?>
