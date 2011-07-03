<?php
/* copyright 2010 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
 This file is part of stkaddons.                                 
                                                                              
 stkaddons is free software: you can redistribute it and/or      
 modify it under the terms of the GNU General Public License as published by  
 the Free Software Foundation, either version 3 of the License, or (at your   
 option) any later version.                                                   
                                                                              
 stkaddons is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for    
 more details.                                                                
                                                                              
 You should have received a copy of the GNU General Public License along with 
 stkaddons.  If not, see <http://www.gnu.org/licenses/>.   */

class coreAddon
{
    //e.g. karts or tracks
    var $addonType;
    var $reqSql;
    var $addonCurrent;

    function coreAddon($type)
    {
        $this->addonType = $type;
    }

    function selectById($id, $rev = false)
    {
        $icon = NULL;
        if ($this->addonType == 'karts')
            $icon = ' r.icon,';
        if (!$rev)
        {
            $querySql = 'SELECT a.*, r.fileid, r.creation_date AS revision_timestamp,
                    r.revision, r.format, r.image,'.$icon.' r.status, r.moderator_note
                FROM `'.DB_PREFIX.'addons` `a`
                LEFT JOIN `'.DB_PREFIX.$this->addonType.'_revs` `r`
                ON `a`.`id` = `r`.`addon_id`
                WHERE `a`.`id` = \''.$id.'\'
                AND `a`.`type` = \''.$this->addonType.'\'
                AND `r`.`status` & '.F_LATEST;
        }
        else
        {
            $querySql = 'SELECT a.*, r.fileid, r.creation_date AS revision_timestamp,
                    r.revision, r.format, r.image,'.$icon.' r.status, r.moderator_note
                FROM '.DB_PREFIX.'addons a
                LEFT JOIN '.DB_PREFIX.$this->addonType.'_revs r
                ON a.id = r.addon_id
                WHERE a.id = \''.$id.'\'
                AND `a`.`type` = \''.$this->addonType.'\'';
        }
        $this->reqSql = sql_query($querySql);
        if (!$this->reqSql)
        {
            echo mysql_error();
        }
        if (mysql_num_rows($this->reqSql) == 0)
        {
            echo _('The requested addon does not exist.').'<br />';
        }

        $this->addonCurrent = sql_next($this->reqSql);
        if ($this->addonCurrent)
            $this->addonCurrent['permUrl'] = 'http://'.$_SERVER['SERVER_NAME'].
                    str_replace("addons-panel.php", "addons.php", $_SERVER['SCRIPT_NAME']).
                    '?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'];
    }

    function selectByUser($id)
    {
        $icon = NULL;
        if ($this->addonType == 'karts')
            $icon = ' r.icon,';
        $querySql = 'SELECT a.*, r.fileid,
                r.creation_date AS revision_timestamp, r.revision,
                r.format, r.image,'.$icon.' r.status, r.moderator_note
            FROM '.DB_PREFIX.'addons a
            LEFT JOIN '.DB_PREFIX.$this->addonType.'_revs r
            ON a.id = r.addon_id
            WHERE a.uploader = \''.$id.'\'
            AND `a`.`type` = \''.$this->addonType.'\'';
        $this->reqSql = sql_query($querySql);
        $this->addonCurrent = sql_next($this->reqSql);
        if ($this->addonCurrent)
            $this->addonCurrent['permUrl'] = 'http://'.$_SERVER['SERVER_NAME'].
                    str_replace("addons-panel.php", "addons.php", $_SERVER['SCRIPT_NAME']).
                    '?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'];
    }

    function loadAll()
    {
        $icon = NULL;
        if ($this->addonType == 'karts')
            $icon = ' r.icon,';
        $querySql = 'SELECT a.*, r.fileid, r.revision, r.format, r.image,'.$icon.' r.status
            FROM '.DB_PREFIX.'addons a
            LEFT JOIN '.DB_PREFIX.$this->addonType.'_revs r
            ON a.id = r.addon_id
            WHERE r.status & '.F_LATEST.'
            AND `a`.`type` = \''.$this->addonType.'\'
            ORDER BY `a`.`name` ASC, `a`.`id` ASC';
        $this->reqSql = sql_query($querySql);
        return $this->reqSql;
    }

    function next()
    {
        $this->addonCurrent = sql_next($this->reqSql);
        if(!$this->addonCurrent)
            return false;
        $this->addonCurrent['permUrl'] = 'http://'.$_SERVER['SERVER_NAME'].
                str_replace("addons-panel.php", "addons.php", $_SERVER['SCRIPT_NAME']).
                '?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'];
        return true;
    }

    /**
     * Change the approval value on the add-on revision
     * @global user $user
     * @return boolean Success
     */
    function approve()
    {
        global $user;
        if (!$user->logged_in)
            return false;
        if($_SESSION['role']['manageaddons'] != true)
            return false;

        /* if the addons is already available, we want to deactivate it :
            $is_available = abs(1 - 1) = 0
           else, it isn't and we want to activate it:
            $is_available = abs(0 - 1) = 1
         */
        $current_status = bindec($this->addonCurrrent['status']);
        if ($current_status & F_APPROVED)
        {
            $current_status = $current_status - F_APPROVED;
        }
        else
        {
            $current_status = $current_status + F_APPROVED;
        }
        if (!sql_update($this->addonType.'_revs', "id",
                   $this->addonCurrent['id'],
                   "status",
                   decbin($current_status)))
            return false;
        writeAssetXML();
        writeNewsXML();
        return true;
    }
    
    function set_image($image_id, $field = 'image')
    {
        if (!$_SESSION['role']['manageaddons'] && $this->addonCurrent['uploader'] != $_SESSION['userid'])
            return false;

        $set_image_query = 'UPDATE `'.DB_PREFIX.$this->addonType.'_revs`
            SET `'.$field.'` = '.(int)$image_id.'
            WHERE `addon_id` = \''.$this->addonCurrent['id'].'\'
            AND `status` & '.F_LATEST;
        $set_image_handle = sql_query($set_image_query);
        if (!$set_image_handle)
            return false;
        return true;
    }

    function delete_file($file_id)
    {
        if (!$_SESSION['role']['manageaddons'] && $this->addonCurrent['uploader'] != $_SESSION['userid'])
            return false;

        // Get file path
        $get_file_query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id.'
            LIMIT 1';
        $get_file_handle = sql_query($get_file_query);
        if (!$get_file_handle)
            return false;
        if (mysql_num_rows($get_file_handle) == 1)
        {
            $get_file = mysql_fetch_assoc($get_file_handle);
            if (file_exists(UP_LOCATION.$get_file['file_path']))
                unlink(UP_LOCATION.$get_file['file_path']);
        }

        // Delete file record
        $del_file_query = 'DELETE FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id;
        $del_file_handle = sql_query($del_file_query);
        if(!$del_file_handle)
            return false;
        writeAssetXML();
        writeNewsXML();
        return true;
    }

    /** Set an information of the addon.
        \param $info The name of the information (e.g. 'name', 'version')
        \param $value The new value of the information (e.g. 'Tux', 'Adiumy')
    */
    function setInformation($info, $value)
    {
        global $user;
        if (!$user->logged_in)
            return false;

        if ($_SESSION['role']['manageaddons'] != true && $this->addonCurrent['uploader'] != $_SESSION['userid'])
            return false;
        
        $info = mysql_real_escape_string($info);
        $value = mysql_real_escape_string($value);
        if (strlen($info) == 0)
            return false;
        
        $updateQuery = 'UPDATE `'.DB_PREFIX.'addons`
            SET `'.$info.'` = \''.$value.'\'
            WHERE `id` = \''.$this->addonCurrent['id'].'\'';
        $updateSql = sql_query($updateQuery);
        
        if (!$updateSql)
            return false;
        writeAssetXML();
        writeNewsXML();
        return true;
    }

    /** Remove the selected addons. */
    function remove()
    {
        global $user;
        if (!$user->logged_in)
            return false;
        if($_SESSION['role']['manageaddons'] != true)
            return false;

        // Remove files associated with this addon
        $get_files_query = 'SELECT * FROM `'.DB_PREFIX.'files`
            WHERE `addon_id` = \''.$this->addonCurrent['id'].'\'
            AND `addon_type` = \''.$this->addonType.'\'';
        $get_files_handle = sql_query($get_files_query);
        if (!$get_files_handle)
        {
            echo '<span class="error">'._('Failed to find files associated with this addon.').'</span><br />';
            return false;
        }
        $num_files = mysql_num_rows($get_files_handle);
        for ($i = 1; $i <= $num_files; $i++)
        {
            $get_file = mysql_fetch_assoc($get_files_handle);
            if (file_exists(UP_LOCATION.$get_file['file_path']) && !unlink(UP_LOCATION.$get_file['file_path']))
            {
                echo '<span class="error">'._('Failed to delete file:').' '.$get_file['file_path'].'</span><br />';
            }
        }
        
        // Remove file records associated with addon
        $remove_file_query = 'DELETE FROM `'.DB_PREFIX.'files`
            WHERE `addon_id` = \''.$this->addonCurrent['id'].'\'
            AND `addon_type` = \''.$this->addonType.'\'';
        $remove_file_handle = sql_query($remove_file_query);
        if (!$remove_file_handle)
        {
            echo '<span class="error">'._('Failed to remove file records for this addon.').'</span><br />';
        }

        // Get revisions
        $getRevsQuery = 'SELECT * FROM `'.DB_PREFIX.$this->addonType.'_revs`
            WHERE `addon_id` = \''.$this->addonCurrent['id'].'\'';
        $getRevsHandle = sql_query($getRevsQuery);
        if (!$getRevsHandle)
        {
            if ($_SESSION['role']['manageaddons'])
                echo mysql_error().'<br />';
            return false;
        }
        $num_revisions = mysql_num_rows($getRevsHandle);
        for ($i = 1; $i <= $num_revisions; $i++)
        {
            $getRevsResult = mysql_fetch_assoc($getRevsHandle);
            // Delete entry
            if (!sql_remove_where($this->addonType.'_revs', 'id', $getRevsResult['id']))
            {
                echo _('Failed to remove revision record.').'<br />';
                return false;
            }
        }

        // Remove addon entry
        if (!sql_remove_where('addons', 'id', $this->addonCurrent['id']))
        {
            echo '<span class="error">'._('Failed to remove addon.').'</span><br />';
            return false;
        }

        writeAssetXML();
        writeNewsXML();
        return true;
    }

    /** Print the information of the addon, it name, it description, it
      * version...
      */
    function writeInformation()
    {
        global $user;
        $addonUser = new coreUser();
        $addonUser->selectById($this->addonCurrent['uploader']);
        if ($this->addonCurrent['designer'] == NULL)
            $this->addonCurrent['designer'] = '<em>'._('Unknown').'</em>';
        if ($this->addonCurrent['description'] == NULL)
            $description = NULL;
        else
            $description = htmlentities ($this->addonCurrent['description']).'<br />';

        //div for jqery TODO:add jquery effects
        echo '<div id="accordion"><div>';

        // Get image
        $image_query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.$this->addonCurrent['image'].'
            AND `approved` = 1
            LIMIT 1';
        $image_handle = sql_query($image_query);
        echo '<div id="addon-image">';
        if ($image_handle && mysql_num_rows($image_handle) == 1)
        {
            $image_result = mysql_fetch_assoc($image_handle);
            echo '<img class="preview" src="image.php?type=big&amp;pic='.$image_result['file_path'].'" />';
        }
        // Add upload button below image (or in place of image)
        if ($user->logged_in && $this->addonCurrent['uploader'] == $_SESSION['userid'])
        {
            echo '<br /><form method="POST" action="upload.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'].'&amp;action=file">';
            echo '<input type="submit" value="'._('Upload Image').'" />';
            echo '</form>';
        }
        echo '</div>';

        echo '<h1>'.$this->addonCurrent['name'].'</h1><br />';

        // Display badges for status flags
        if ($this->addonCurrent['status'] & F_FEATURED)
                echo '<span class="f_featured">'._('Featured').'</span>';
        if ($this->addonCurrent['status'] & F_ALPHA)
                echo '<span class="f_alpha">'._('Alpha').'</span>';
        if ($this->addonCurrent['status'] & F_BETA)
                echo '<span class="f_beta">'._('Beta').'</span>';
        if ($this->addonCurrent['status'] & F_RC)
                echo '<span class="f_rc">'._('Release-Candidate').'</span>';
        if ($this->addonCurrent['status'] & F_DFSG)
                echo '<span class="f_dfsg">'._('DFSG Compliant').'</span>';
        echo '<br />'.$description.'
        <table>';
        if ($this->addonType == 'tracks' && $this->addonCurrent['props'] == 1)
        {
            echo '<tr><td><strong>'._('Type:').'</strong></td><td>'._('Arena').'</td></tr>';
        }
        echo '<tr><td><strong>'._('Designer:').'</strong></td><td>'.$this->addonCurrent['designer'].'</td></tr>
        <tr><td><strong>'._('Upload date:').'</strong></td><td>'.$this->addonCurrent['revision_timestamp'].'</td></tr>
        <tr><td><strong>'._('Submitted by:').'</strong></td><td><a href="users.php?user='.$addonUser->userCurrent['user'].'">'.$addonUser->userCurrent['name'].'</a></td></tr>
        <tr><td><strong>'._('Revision:').'</strong></td><td>'.$this->addonCurrent['revision'].'</td></tr>
        <tr><td><strong>'._('Compatible with:').'</strong></td><td>'.format_compat($this->addonCurrent['format'],$this->addonType).'</td></tr>
        </table></div>';
        
        // Get download path
        $file_path = get_file_path($this->addonCurrent['fileid']);
        if ($file_path !== false)
        {
            if (file_exists(UP_LOCATION.$file_path))
            {
                echo '<a href="'.DOWN_LOCATION.$file_path.'"><img src="image/download.png" alt="Download" title="Download" /></a>';
            }
            else
            {
                echo '<span class="error">'._('File not found.').'</span><br />';
            }
        }
        else
        {
            echo '<span class="error">'._('File not found.').'</span><br />';
        }

        echo '<br /><br /><br /><br />
        <strong>'._('License:').'</strong><br />
        <textarea name="license" rows="4" cols="60">'.strip_tags($this->addonCurrent['license']).'</textarea>
        <br /><br /><strong>'._('Permalink:').'</strong><br />
        '.$this->addonCurrent['permUrl'].'<br />';
        
        $addonRevs = new coreAddon($this->addonType);
        $addonRevs->selectById($this->addonCurrent['id'],true);
        echo '<strong>'._('Revisions:').'</strong><br />';
        echo '<table>';
        while ($addonRevs->addonCurrent)
        {
            // Don't list unapproved addons
            global $user;
            if (!$user->logged_in)
            {
                // Users not logged in cannot see unapproved addons
                if (!($addonRevs->addonCurrent['status'] & F_APPROVED))
                {
                    $addonRevs->next();
                    continue;
                }
            }
            else
            {
                // Logged in users who are not the uploader, or moderators
                // cannot see unapproved addons
                if (($addonRevs->addonCurrent['uploader'] != $_SESSION['userid']
                        && !$_SESSION['role']['manageaddons'])
                        && !($addonRevs->addonCurrent['status'] & F_APPROVED))
                {
                    $addonRevs->next();
                    continue;
                }
            }

            echo '<tr><td>'.$addonRevs->addonCurrent['revision_timestamp'].'</td><td>';
            // Get download path
            $file_path = get_file_path($addonRevs->addonCurrent['fileid']);
            if ($file_path !== false)
            {
                if (file_exists(UP_LOCATION.$file_path))
                {
                    echo '<a href="'.DOWN_LOCATION.$file_path.'">'._('Download revision').' '.$addonRevs->addonCurrent['revision'].'</a>';
                }
                else
                {
                    echo _('Revision').' '.$addonRevs->addonCurrent['revision'].' - '._('File not found.');
                }
            }
            else
            {
                echo _('Revision').' '.$addonRevs->addonCurrent['revision'].' - '._('File not found.');
            }
            echo '</td></tr>';
            $addonRevs->next();
        }
        echo '</table><br /><br />';
        
        // Show list of images associated with this addon
        echo '<h3>'._('Images').'</h3>';
        // Add upload button to the right of the Images label
        if ($user->logged_in && $this->addonCurrent['uploader'] == $_SESSION['userid'])
        {
            echo '<div style="float: right;"><form method="POST" action="upload.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'].'&amp;action=file">';
            echo '<input type="submit" value="'._('Upload Image').'" />';
            echo '</form></div>';
        }
        echo '<br /><br />';
        $imageFilesQuery = 'SELECT * FROM `'.DB_PREFIX.'files`
            WHERE `addon_type` = \''.$this->addonType.'\'
            AND `addon_id` = \''.$this->addonCurrent['id'].'\'
            AND `file_type` = \'image\'';
        $imageFilesHandle = sql_query($imageFilesQuery);
    
        // Create an array of all of the images that the current user can see
        $image_files = array();
        for ($i = 1; $i <= mysql_num_rows($imageFilesHandle); $i++)
        {
            $imageFilesResult = mysql_fetch_assoc($imageFilesHandle);
            if ($user->logged_in &&
                    ($this->addonCurrent['uploader'] == $_SESSION['userid']
                    || $_SESSION['role']['manageaddons']))
            {
                $image_files[] = $imageFilesResult;
                continue;
            }
            if ($imageFilesResult['approved'] == 1)
            {
                $image_files[] = $imageFilesResult;
            }
        }
        
        if (count($image_files) == 0)
        {
            echo _('No images have been uploaded for this addon yet.').'<br />';
        }
        else
        {
            foreach ($image_files AS $source_file)
            {
                if ($source_file['approved'] == 1)
                    $div_style = 'image_thumb_container';
                else
                    $div_style = 'image_thumb_container unapproved';
                echo '<div class="'.$div_style.'">';
                echo '<a href="'.DOWN_LOCATION.$source_file['file_path'].'">';
                echo '<img src="image.php?type=medium&amp;pic='.$source_file['file_path'].'" />';
                echo '</a><br />';
                if ($user->logged_in)
                {
                    if ($_SESSION['role']['manageaddons'])
                    {
                        if ($source_file['approved'] == 1)
                            echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=unapprove&amp;id='.$source_file['id'].'">'._('Unapprove').'</a>';
                        else
                            echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=approve&amp;id='.$source_file['id'].'">'._('Approve').'</a>';
                        echo '<br />';
                    }
                    if ($_SESSION['role']['manageaddons'] || $this->addonCurrent['uploader'] == $_SESSION['userid'])
                    {
                        if ($this->addonType == 'karts')
                        {
                            if ($this->addonCurrent['icon'] != $source_file['id'])
                            {
                                echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=seticon&amp;id='.$source_file['id'].'">'._('Set Icon').'</a><br />';
                            }
                        }
                        if ($this->addonCurrent['image'] != $source_file['id'])
                        {
                            echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=setimage&amp;id='.$source_file['id'].'">'._('Set Image').'</a><br />';
                        }
                        echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=deletefile&amp;id='.$source_file['id'].'">'._('Delete File').'</a><br />';
                    }
                }
                echo '</div>';
            }
        }
        echo '<br /><br />';

        // Show list of source files
        echo '<h3>'._('Source Files').'</h3>';
        // Add upload button to the right of the Source Files label
        if ($user->logged_in && $this->addonCurrent['uploader'] == $_SESSION['userid'])
        {
            echo '<div style="float: right;"><form method="POST" action="upload.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'].'&amp;action=file">';
            echo '<input type="submit" value="'._('Upload Source File').'" />';
            echo '</form></div>';
        }
        echo '<br /><br />';
        $sourceFilesQuery = 'SELECT * FROM `'.DB_PREFIX.'files`
            WHERE `addon_type` = \''.$this->addonType.'\'
            AND `addon_id` = \''.$this->addonCurrent['id'].'\'
            AND `file_type` = \'source\'';
        $sourceFilesHandle = sql_query($sourceFilesQuery);
    
        // Create an array of all of the source files that the current user can see
        $source_files = array();
        for ($i = 1; $i <= mysql_num_rows($sourceFilesHandle); $i++)
        {
            $sourceFilesResult = mysql_fetch_assoc($sourceFilesHandle);
            if ($user->logged_in &&
                    ($this->addonCurrent['uploader'] == $_SESSION['userid']
                    || $_SESSION['role']['manageaddons']))
            {
                $source_files[] = $sourceFilesResult;
                continue;
            }
            if ($sourceFilesResult['approved'] == 1)
            {
                $source_files[] = $sourceFilesResult;
            }
        }
        
        if (count($source_files) == 0)
        {
            echo _('No source files have been uploaded for this addon yet.').'<br />';
        }
        else
        {
            echo '<table>';
            $n = 1;
            foreach ($source_files AS $source_file)
            {
                echo '<tr>';
                $approved = NULL;
                if ($source_file['approved'] == 0) $approved = ' ('._('Not Approved').')';
                echo '<td><strong>';
                printf(_('Source File %s'),$n);
                echo '</strong>'.$approved.'</td>';
                echo '<td><a href="'.DOWN_LOCATION.$source_file['file_path'].'">'._('Download').'</a>';
                if ($user->logged_in)
                {
                    if ($_SESSION['role']['manageaddons'])
                    {
                        if ($source_file['approved'] == 1)
                            echo ' | <a href="'.$this->addonCurrent['permUrl'].'&amp;save=unapprove&amp;id='.$source_file['id'].'">'._('Unapprove').'</a>';
                        else
                            echo ' | <a href="'.$this->addonCurrent['permUrl'].'&amp;save=approve&amp;id='.$source_file['id'].'">'._('Approve').'</a>';
                    }
                }
                echo '</td></tr>';
            }
            echo '</table>';
        }
    }

    /* FIXME: this function needs a lot of cleanup / a rewrite. */
    function writeConfig()
    {
        // Check permission
        global $user;
        if ($user->logged_in == false)
            return false;
        if ($_SESSION['role']['manageaddons'] == false && $this->addonCurrent['uploader'] != $_SESSION['userid'])
            return false;

        echo '<hr /><h3>Configuration</h3>';
        // Edit designer
        echo '<form name="changeProps" action="'.$this->addonCurrent['permUrl'].'&amp;save=props" method="POST">';
        echo '<strong>'._('Designer:').'</strong><br />';
        // FIXME: Find a cleaner way to check this
        if ($this->addonCurrent['designer'] == '<em>'._('Unknown').'</em>')
                $this->addonCurrent['designer'] = NULL;
        echo '<input type="text" name="designer" id="designer_field" value="'.$this->addonCurrent['designer'].'" /><br />';
        echo '<br />';
        echo '<strong>'._('Description:').'</strong> ('._('Max 140 characters').')<br />';
        echo '<textarea name="description" id="desc_field" rows="4" cols="60">'.$this->addonCurrent['description'].'</textarea><br />';
        echo '<input type="submit" value="'._('Save Properties').'" />';
        echo '</form><br />';

        // Add revision
        if ($this->addonCurrent['uploader'] == $_SESSION['userid'])
        {
            echo '<form method="POST" action="upload.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'].'">';
            echo '<input type="submit" value="'._('Upload Revision').'" /></form><br /><Br />';
        }
        
        // Delete addon
        if ($this->addonCurrent['uploader'] == $_SESSION['userid'] || $_SESSION['role']['manageaddons'])
            echo '<input type="button" value="'._('Delete Addon').'" onClick="confirm_delete(\''.$this->addonCurrent['permUrl'].'&amp;save=delete\')" /><br /><br />';

        // Set status flags
        echo '<strong>'._('Status Flags:').'</strong><br />';
        echo '<form method="POST" action="'.$this->addonCurrent['permUrl'].'&amp;save=status">';
        echo '<table id="addon_flags"><tr><th></th>';
        if ($_SESSION['role']['manageaddons'])
        {
            echo '<th>'.img_label(_('Approved')).'</th>
                <th>'.img_label(_('Invisible')).'</th>';
        }
        echo '<th>'.img_label(_('Alpha')).'</th>
            <th>'.img_label(_('Beta')).'</th>
            <th>'.img_label(_('Release-Candidate')).'</th>
            <th>'.img_label(_('Latest')).'</th>';
        if ($_SESSION['role']['manageaddons'])
            echo '<th>'.img_label(_('DFSG Compliant')).'</th>
                <th>'.img_label(_('Featured')).'</th>';
        echo '<th>'.img_label(_('Invalid Textures')).'</th>';
        echo '</tr>';
        $addonRevs = new coreAddon($this->addonType);
        $addonRevs->selectById($this->addonCurrent['id'],true);
        $fields = array();
        $fields[] = 'latest';
        while ($addonRevs->addonCurrent)
        {
            echo '<tr><td style="text-align: center;">Rev '.$addonRevs->addonCurrent['revision'].'</td>';

            if ($_SESSION['role']['manageaddons'] == true)
            {
                // F_APPROVED
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_APPROVED)
                {
                    echo '<input type="checkbox" name="approved-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="approved-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'approved-'.$addonRevs->addonCurrent['revision'];
                
                // F_INVISIBLE
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_INVISIBLE)
                {
                    echo '<input type="checkbox" name="invisible-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="invisible-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'invisible-'.$addonRevs->addonCurrent['revision'];
            }

            // F_ALPHA
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_ALPHA)
            {
                echo '<input type="checkbox" name="alpha-'.$addonRevs->addonCurrent['revision'].'" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="alpha-'.$addonRevs->addonCurrent['revision'].'" />';
            }
            echo '</td>';
            $fields[] = 'alpha-'.$addonRevs->addonCurrent['revision'];

            // F_BETA
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_BETA)
            {
                echo '<input type="checkbox" name="beta-'.$addonRevs->addonCurrent['revision'].'" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="beta-'.$addonRevs->addonCurrent['revision'].'" />';
            }
            echo '</td>';
            $fields[] = 'beta-'.$addonRevs->addonCurrent['revision'];

            // F_RC
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_RC)
            {
                echo '<input type="checkbox" name="rc-'.$addonRevs->addonCurrent['revision'].'" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="rc-'.$addonRevs->addonCurrent['revision'].'" />';
            }
            echo '</td>';
            $fields[] = 'rc-'.$addonRevs->addonCurrent['revision'];

            // F_LATEST
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_LATEST)
            {
                echo '<input type="radio" name="latest" value="'.$addonRevs->addonCurrent['revision'].'" checked />';
            }
            else
            {
                echo '<input type="radio" name="latest" value="'.$addonRevs->addonCurrent['revision'].'" />';
            }
            echo '</td>';

            if ($_SESSION['role']['manageaddons'])
            {
                // F_DFSG
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_DFSG)
                {
                    echo '<input type="checkbox" name="dfsg-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="dfsg-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'dfsg-'.$addonRevs->addonCurrent['revision'];
                
                // F_FEATURED
                echo '<td>';
                if ($addonRevs->addonCurrent['status'] & F_FEATURED)
                {
                    echo '<input type="checkbox" name="featured-'.$addonRevs->addonCurrent['revision'].'" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="featured-'.$addonRevs->addonCurrent['revision'].'" />';
                }
                echo '</td>';
                $fields[] = 'featured-'.$addonRevs->addonCurrent['revision'];
            }

            // F_TEX_NOT_POWER_OF_2
            echo '<td>';
            if ($addonRevs->addonCurrent['status'] & F_TEX_NOT_POWER_OF_2)
            {
                echo '<input type="checkbox" name="texpower-'.$addonRevs->addonCurrent['revision'].'" checked disabled />';
            }
            else
            {
                echo '<input type="checkbox" name="texpower-'.$addonRevs->addonCurrent['revision'].'" disabled />';
            }
            echo '</td>';
            
            echo '</tr>';
            $addonRevs->next();
        }
        echo '</table>';
        echo '<input type="hidden" name="fields" value="'.implode(',',$fields).'" />';
        echo '<input type="submit" value="'._('Save Changes').'" />';
        echo '</form><br />';
        
        // Moderator notes
        echo '<strong>'._('Notes from Moderator to Submitter:').'</strong><br />';
        if ($_SESSION['role']['manageaddons'])
            echo '<form method="POST" action="'.$this->addonCurrent['permUrl'].'&amp;save=notes">';
        $addonRevs = new coreAddon($this->addonType);
        $addonRevs->selectById($this->addonCurrent['id'],true);
        $fields = array();
        while ($addonRevs->addonCurrent)
        {
            echo 'Rev '.$addonRevs->addonCurrent['revision'].':<br />';
            echo '<textarea name="notes-'.$addonRevs->addonCurrent['revision'].'" rows="4" cols="60">';
            echo $addonRevs->addonCurrent['moderator_note'];
            echo '</textarea><br />';
            $fields[] = 'notes-'.$addonRevs->addonCurrent['revision'];
            $addonRevs->next();
        }
        if ($_SESSION['role']['manageaddons'])
        {
            echo '<input type="hidden" name="fields" value="'.implode(',',$fields).'" />';
            echo '<input type="submit" value="'._('Save Notes').'" />';
            echo '</form>';
        }
    }

    function viewInformation($config = true)
    {
        // Make sure addon exists
        if (!$this->addonCurrent)
            return false;
        
        $this->writeInformation();

        global $user;
        if ($user->logged_in == false)
            return false;
        //write configuration for the submiter and administrator
        if(($_SESSION['role']['manageaddons'] == true || $this->addonCurrent['uploader'] == $_SESSION['userid']) && $config)
        {
            $this->writeConfig();
        }
    }

    function addAddon($fileid, $addonid, $attributes)
    {
        global $moderator_message;
	global $user;
        // Check if logged in
        if (!$user->logged_in) {
            return false;
        }

        // Make sure no addon with this id exists
        if(sql_exist($this->addonType.'_revs', 'id', $fileid))
        {
            echo '<span class="error">'._('The add-on you are trying to create already exists.').'</span><br />';
            return false;
        }

        // Check if we're creating a new add-on
        if (!sql_exist('addons', 'id', $addonid))
        {
            echo _('Creating a new add-on...').'<br />';
            $fields = array('id','type','name','uploader','designer','license');
            $values = array($addonid,$this->addonType,$attributes['name'],$_SESSION['userid'],$attributes['designer'],$attributes['license']);
            if ($this->addonType == 'tracks')
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
            echo _('This add-on already exists. Adding revision...').'<br />';
            // Update license file record
            if (!sql_update('addons',
                    'id',
                    mysql_real_escape_string($addonid),
                    'license',
                    mysql_real_escape_string($attributes['license'])))
            {
                echo '<span class="error">'._('Failed to update the license record for this add-on.').'</span><br />';
            }
        }

        // Add the new revision
        $prevRevQuerySql = 'SELECT `revision` FROM '.DB_PREFIX.$this->addonType.'_revs
            WHERE `addon_id` = \''.$addonid.'\' ORDER BY `revision` DESC LIMIT 1';
        $reqSql = sql_query($prevRevQuerySql);
        if (!$reqSql)
        {
            echo '<span class="error">'._('Failed to check for previous add-on revisions.').'</span><br />';
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
        $values = array($fileid,$addonid,$attributes['fileid'],$rev,$attributes['version'],$attributes['image'],$attributes['status']);
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
                "{S_SESSION['user']} has uploaded a new file for the {$this->addonType} \'{$attributes['name']}\' ($addonid)");
        return true;
    }

    /** To get the permanent link of the current addon */ 
    function permalink()
    {
        return 'addons.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'];
    }
}

function addon_id_clean($string)
{
    if (!is_string($string))
        return false;
    $length = strlen($string);
    if ($length == 0)
        return false;
    $string = strtolower($string);
    // Validate all characters in addon id
    // Rather than using str_replace, and removing bad characters,
    // it makes more sense to only allow certain characters
    for ($i = 0; $i<$length; $i++)
    {
        $substr = substr($string,$i,1);
        if (!preg_match('/^[a-z0-9\-_]$/i',$substr))
            $substr = '-';
        $string = substr_replace($string,$substr,$i,1);
    }
    return $string;
}

function addon_name($addon_id)
{
    $addon_id = addon_id_clean($addon_id);
    $query = 'SELECT `name` FROM `'.DB_PREFIX.'addons`
        WHERE `id` = \''.$addon_id.'\' LIMIT 1';
    $handle = sql_query($query);
    if (!$handle)
        return false;
    if (mysql_num_rows($handle) == 0)
        return false;
    $result = mysql_fetch_assoc($handle);
    return $result['name'];
}

function update_status($type,$addon_id,$fields)
{
    if ($type != 'karts' && $type != 'tracks')
    {
        echo '<span class="error">'._('Invalid addon type.').'</span><br />';
        return false;
    }
    $addon_id = addon_id_clean($addon_id);
    $fields = explode(',',$fields);
    $status = array();
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
            FROM `'.DB_PREFIX.$type.'_revs`
            WHERE `addon_id` = \''.$addon_id.'\'
            AND `revision` = '.$revision;
        $getStatusSql = sql_query($getStatusQuery);
        if (!$getStatusSql)
            return false;
        $getStatusResult = mysql_fetch_assoc($getStatusSql);
        if ($getStatusResult['status'] & F_TEX_NOT_POWER_OF_2)
            $value += F_TEX_NOT_POWER_OF_2;
        
        // Write new addon
        $query = 'UPDATE `'.DB_PREFIX.$type.'_revs`
            SET `status` = '.$value.'
            WHERE `addon_id` = \''.$addon_id.'\'
            AND `revision` = '.$revision;
        $reqSql = sql_query($query);
        if (!$reqSql)
            $error = 1;
    }
    writeAssetXML();
    writeNewsXML();
    if ($error != 1)
        return true;
    return false;
}

function get_file_path($file_id)
{
    // Validate input
    if (!is_numeric($file_id))
        return false;
    if ($file_id == 0)
        return false;

    // Look up file path from database
    $query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
        WHERE `id` = '.(int)$file_id.'
        LIMIT 1';
    $handle = sql_query($query);
    if (mysql_num_rows($handle) == 0)
        return false;
    $file = mysql_fetch_assoc($handle);
    return $file['file_path'];
}

function update_addon_notes($type,$addon_id,$fields)
{
    if (!$_SESSION['role']['manageaddons'])
        return false;
    if ($type != 'karts' && $type != 'tracks')
    {
        echo '<span class="error">'._('Invalid addon type.').'</span><br />';
        return false;
    }
    $addon_id = addon_id_clean($addon_id);
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
        // Make sure addon exists
        $addon = new coreAddon($type);
        $addon->selectById($addon_id);
        if (!$addon)
            return false;
        $query = 'UPDATE `'.DB_PREFIX.$type.'_revs`
            SET `moderator_note` = \''.$value.'\'
            WHERE `addon_id` = \''.$addon_id.'\'
            AND `revision` = '.$revision;
        $reqSql = sql_query($query);
        if (!$reqSql)
            $error = 1;
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
    $user = $addon->addonCurrent['uploader'];
    $userQuery = 'SELECT `name`,`email` FROM `'.DB_PREFIX.'users`
        WHERE `id` = '.(int)$user.' LIMIT 1';
    $userHandle = sql_query($userQuery);
    if (!$userHandle)
        $error = 1;
    else
    {
        $result = mysql_fetch_assoc($userHandle);
        sendMail($result['email'],
                'moderatorNotification',
                array($addon->addonCurrent['name'],
                SITE_ROOT.$addon->permalink(),
                $email_body,
                $result['name']));
    }

    if ($error != 1)
        return true;
    return false;
}

function format_compat($format,$filetype)
{
    // FIXME: This should not be hardcoded
    switch ($filetype)
    {
        default:
            return _('Unknown');
        case 'karts':
            if ($format == 1)
            {
                return 'Pre-0.7';
            }
            if ($format == 2)
            {
                return '0.7 - 0.7.2';
            }
            return _('Unknown');
            break;
        case 'tracks':
            if ($format == 1 || $format == 2)
            {
                return 'Pre-0.7';
            }
            if ($format >= 3 && $format <= 5)
            {
                return '0.7 - 0.7.2';
            }
            return _('Unknown');
            break;
    }
    return _('Unknown');
}

function approve_file($file_id,$approve = 'approve')
{
    if ($approve == 'approve')
        $approve = 1;
    else
        $approve = 0;

    if (!$_SESSION['role']['manageaddons'])
    {
        echo '<span class="error">'._('Insufficient permissions.').'</span><br />';
        return false;
    }

    $approve_query = 'UPDATE `'.DB_PREFIX.'files`
        SET `approved` = '.$approve.'
        WHERE `id` = '.(int)$file_id;
    $approve_handle = sql_query($approve_query);
    if (!$approve_handle)
    {
        return false;
    }
    writeAssetXML();
    writeNewsXML();
    return true;
}
?>
