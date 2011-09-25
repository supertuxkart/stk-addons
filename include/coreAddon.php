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
    /**
     * Ratings object, false if not initialized
     * @var Ratings
     */
    private $rating = false;

    function coreAddon($type)
    {
        $this->addonType = $type;
    }

    function selectById($id)
    {
        $icon = NULL;
        if ($this->addonType == 'karts')
            $icon = ' r.icon,';
        $querySql = 'SELECT a.*, r.fileid, r.creation_date AS revision_timestamp,
                r.revision, r.format, r.image,'.$icon.' r.status, r.moderator_note
            FROM `'.DB_PREFIX.'addons` `a`
            LEFT JOIN `'.DB_PREFIX.$this->addonType.'_revs` `r`
            ON `a`.`id` = `r`.`addon_id`
            WHERE `a`.`id` = \''.$id.'\'
            AND `a`.`type` = \''.$this->addonType.'\'
            AND `r`.`status` & '.F_LATEST;
        $this->reqSql = sql_query($querySql);
        if (!$this->reqSql)
        {
            echo mysql_error();
        }
        if (mysql_num_rows($this->reqSql) == 0)
        {
            echo htmlspecialchars(_('The requested addon does not exist.')).'<br />';
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
            AND `a`.`type` = \''.$this->addonType.'\'
            AND `r`.`status` & '.F_LATEST;
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
            ORDER BY `a`.`name` ASC, `a`.`id` ASC, `r`.`revision` ASC';
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
        if (!User::$logged_in)
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
	
    /**
     * Initialize the addon rating info
     */
    function getRatingInfo()
    {
        $this->rating = new Ratings($this->addonCurrent['id']);
            
        //create the string with the number of ratings (for use in the function below)
        if ($this->rating->getNumRatings() != 1) {
            $this->numRatingsString = $this->rating->getNumRatings().' Votes';
        } else {
            $this->numRatingsString = $this->rating->getNumRatings().' Vote';
        }
    }
	
    /** Print the information of the addon, it name, it description, it
      * version...
      */
    function writeInformation()
    {
        try {
            $mAddon = new Addon($this->addonCurrent['id']);
            $latestRev = $mAddon->getLatestRevision();
            $addonUser = new coreUser();
            $addonUser->selectById($mAddon->getUploader());

            echo '<div>';

            echo '<table border="0px" width="100%"><tr><td width="100%">';
            echo '<h1>'.htmlspecialchars(Addon::getName($mAddon->getId())).'</h1>';
            echo '</td><td align="right">';
            echo '<div id="avg-rating"><div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: '.$this->rating->getAvgRatingPercent().'%;"></div></div><p>'.$this->numRatingsString.'</p></div>';
            echo '</td></tr></table>';

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
            if (User::$logged_in && $mAddon->getUploader() == $_SESSION['userid'])
            {
                echo '<br /><form method="POST" action="upload.php?type='.$this->addonType.'&amp;name='.$mAddon->getId().'&amp;action=file">';
                echo '<input type="submit" value="'.htmlspecialchars(_('Upload Image')).'" />';
                echo '</form>';
            }
            echo '</div>';

            // Display badges for status flags
            if ($latestRev['status'] & F_FEATURED)
                echo '<span class="f_featured">'.htmlspecialchars(_('Featured')).'</span>';
            if ($latestRev['status'] & F_ALPHA)
                echo '<span class="f_alpha">'.htmlspecialchars(_('Alpha')).'</span>';
            if ($latestRev['status'] & F_BETA)
                echo '<span class="f_beta">'.htmlspecialchars(_('Beta')).'</span>';
            if ($latestRev['status'] & F_RC)
                echo '<span class="f_rc">'.htmlspecialchars(_('Release-Candidate')).'</span>';
            if ($latestRev['status'] & F_DFSG)
                echo '<span class="f_dfsg">'.htmlspecialchars(_('DFSG Compliant')).'</span>';
            echo '<br />'.$mAddon->getDescription().'
            <table class="info">';
            if ($this->addonType == 'arenas')
            {
                echo '<tr><td><strong>'.htmlspecialchars(_('Type:')).'</strong></td><td>'.htmlspecialchars(_('Arena')).'</td></tr>';
            }
            echo '<tr><td><strong>'.htmlspecialchars(_('Designer:')).'</strong></td><td>'.htmlspecialchars($mAddon->getDesigner()).'</td></tr>
            <tr><td><strong>'.htmlspecialchars(_('Upload date:')).'</strong></td><td>'.$latestRev['timestamp'].'</td></tr>
            <tr><td><strong>'.htmlspecialchars(_('Submitted by:')).'</strong></td><td><a href="users.php?user='.$addonUser->userCurrent['user'].'">'.htmlspecialchars($addonUser->userCurrent['name']).'</a></td></tr>
            <tr><td><strong>'.htmlspecialchars(_('Revision:')).'</strong></td><td>'.$this->addonCurrent['revision'].'</td></tr>
            <tr><td><strong>'.htmlspecialchars(_('Compatible with:')).'</strong></td><td>'.format_compat($this->addonCurrent['format'],$this->addonType).'</td></tr>';
            if (User::$logged_in) {
                echo '<tr><td><strong>'.htmlspecialchars(_('Your Rating: ')).'</strong></td><td>';
                if ($this->rating->getUserVote() !== false) {
                    if ($this->rating->getUserVote() != 1) {
                        echo htmlspecialchars($this->rating->getUserVote())." stars";
                    } else {
                        echo "1 star";
                    }
                } else {
                    echo '<span id="user-rating">';
                    echo '<a href="javascript:addRating(1,\''.$mAddon->getId().'\',\'user-rating\',\'avg-rating\');"><div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 33%"></div></div></a><br />'; // 1 star
                    echo '<a href="javascript:addRating(2,\''.$mAddon->getId().'\',\'user-rating\',\'avg-rating\');"><div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 66%"></div></div></a><br />'; // 2 stars
                    echo '<a href="javascript:addRating(3,\''.$mAddon->getId().'\',\'user-rating\',\'avg-rating\');"><div class="rating"><div class="emptystars"></div><div class="fullstars" style="width: 100%"></div></div></a>'; // 3 stars
                    echo '</span>';
                }
            }
            echo '</td></tr></table></div>';

            if ($latestRev['status'] & F_TEX_NOT_POWER_OF_2)
            {
                echo htmlspecialchars(_('Warning: This addon may not display correctly on some systems. It uses textures that may not be compatible with all video cards.'))."<br />\n";
            }

            // Get download path
            $file_path = $mAddon->getFile((int)$this->addonCurrent['revision']);
            if ($file_path !== false)
            {
                if (file_exists(UP_LOCATION.$file_path))
                {
                    echo '<a href="'.DOWN_LOCATION.$file_path.'"><img src="image/download.png" alt="Download" title="Download" /></a>';
                }
                else
                {
                    echo '<span class="error">'.htmlspecialchars(_('File not found.')).'</span><br />';
                }
            }
            else
            {
                echo '<span class="error">'.htmlspecialchars(_('File not found.')).'</span><br />';
            }

            echo '<br />
            <h3>'.htmlspecialchars(_('License')).'</h3>
            <textarea name="license" rows="4" cols="60">'.strip_tags($mAddon->getLicense()).'</textarea>
            <br /><br />';

            // Print a permanent reference link (permalink) to this addon
            echo '<h3>'.htmlspecialchars(_('Permalink')).'</h3>
            <a href="'.$mAddon->getLink().'">'.$mAddon->getLink().'</a><br /><br />';

            // List revisions
            echo '<h3>'.htmlspecialchars(_('Revisions')).'</h3>';

            // Add upload button to the right of the Revisions label
            if (User::$logged_in && $this->addonCurrent['uploader'] == $_SESSION['userid'])
            {
                echo '<div style="float: right;"><form method="POST" action="upload.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'].'">';
                echo '<input type="submit" value="'.htmlspecialchars(_('Upload Revision')).'" />';
                echo '</form></div>';
            }

            echo '<table>';
            $revisions = $mAddon->getAllRevisions();
            foreach ($revisions AS $rev_n => $revision) {
                if (!User::$logged_in) {
                    // Users not logged in cannot see unapproved addons
                    if (!($revision['status'] & F_APPROVED))
                        continue;
                } else {
                    // User is logged in
                    // If the user is not the uploader, or moderators, then they
                    // cannot see unapproved addons
                    if (($mAddon->getUploader() != $_SESSION['userid']
                            && !$_SESSION['role']['manageaddons'])
                            && !($revision['status'] & F_APPROVED))
                        continue;
                }
                
                // Display revisions
                echo '<tr><td>'.$revision['timestamp'].'</td><td>';
                // Get download path
                $file_path = $mAddon->getFile($rev_n);
                if ($file_path !== false)
                {
                    if (file_exists(UP_LOCATION.$file_path))
                    {
                        echo '<a href="'.DOWN_LOCATION.$file_path.'">';
                        printf(htmlspecialchars(_('Download revision %u')),$rev_n);
                        echo '</a>';
                    }
                    else
                    {
                        echo htmlspecialchars(_('Revision')).' '.$rev_n.' - '.htmlspecialchars(_('File not found.'));
                    }
                }
                else
                {
                    echo htmlspecialchars(_('Revision')).' '.$rev_n.' - '.htmlspecialchars(_('File not found.'));
                }
                echo '</td></tr>';
            }
            echo '</table><br />';
        
        }
        catch (AddonException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
        
        // Show list of images associated with this addon
        echo '<h3>'.htmlspecialchars(_('Images')).'</h3>';
        // Add upload button to the right of the Images label
        if (User::$logged_in && $this->addonCurrent['uploader'] == $_SESSION['userid'])
        {
            echo '<div style="float: right;"><form method="POST" action="upload.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'].'&amp;action=file">';
            echo '<input type="submit" value="'.htmlspecialchars(_('Upload Image')).'" />';
            echo '</form></div>';
        }
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
            if (User::$logged_in &&
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
            echo htmlspecialchars(_('No images have been uploaded for this addon yet.')).'<br />';
        }
        else
        {
            echo '<div class="image_thumbs">';
            foreach ($image_files AS $source_file)
            {
                if ($source_file['approved'] == 1)
                    $div_style = 'image_thumb_container';
                else
                    $div_style = 'image_thumb_container unapproved';
                echo '<div class="'.$div_style.'">';
                echo '<a href="'.DOWN_LOCATION.$source_file['file_path'].'" target="_blank" style="target-new: tab;">';
                echo '<img src="image.php?type=medium&amp;pic='.$source_file['file_path'].'" />';
                echo '</a><br />';
                if (User::$logged_in)
                {
                    if ($_SESSION['role']['manageaddons'])
                    {
                        if ($source_file['approved'] == 1)
                            echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=unapprove&amp;id='.$source_file['id'].'">'.htmlspecialchars(_('Unapprove')).'</a>';
                        else
                            echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=approve&amp;id='.$source_file['id'].'">'.htmlspecialchars(_('Approve')).'</a>';
                        echo '<br />';
                    }
                    if ($_SESSION['role']['manageaddons'] || $this->addonCurrent['uploader'] == $_SESSION['userid'])
                    {
                        if ($this->addonType == 'karts')
                        {
                            if ($this->addonCurrent['icon'] != $source_file['id'])
                            {
                                echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=seticon&amp;id='.$source_file['id'].'">'.htmlspecialchars(_('Set Icon')).'</a><br />';
                            }
                        }
                        if ($this->addonCurrent['image'] != $source_file['id'])
                        {
                            echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=setimage&amp;id='.$source_file['id'].'">'.htmlspecialchars(_('Set Image')).'</a><br />';
                        }
                        echo '<a href="'.$this->addonCurrent['permUrl'].'&amp;save=deletefile&amp;id='.$source_file['id'].'">'.htmlspecialchars(_('Delete File')).'</a><br />';
                    }
                }
                echo '</div>';
            }
            echo '</div>';
        }
        echo '<br />';

        // Show list of source files
        echo '<h3>'.htmlspecialchars(_('Source Files')).'</h3>';
        // Add upload button to the right of the Source Files label
        if (User::$logged_in && $this->addonCurrent['uploader'] == $_SESSION['userid'])
        {
            echo '<div style="float: right;"><form method="POST" action="upload.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'].'&amp;action=file">';
            echo '<input type="submit" value="'.htmlspecialchars(_('Upload Source File')).'" />';
            echo '</form></div>';
        }
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
            if (User::$logged_in &&
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
            echo htmlspecialchars(_('No source files have been uploaded for this addon yet.')).'<br />';
        }
        else
        {
            echo '<table>';
            $n = 1;
            foreach ($source_files AS $source_file)
            {
                echo '<tr>';
                $approved = NULL;
                if ($source_file['approved'] == 0) $approved = ' ('.htmlspecialchars(_('Not Approved')).')';
                echo '<td><strong>';
                printf(htmlspecialchars(_('Source File %u')),$n);
                echo '</strong>'.$approved.'</td>';
                echo '<td><a href="'.DOWN_LOCATION.$source_file['file_path'].'">'.htmlspecialchars(_('Download')).'</a>';
                if (User::$logged_in)
                {
                    if ($_SESSION['role']['manageaddons'])
                    {
                        if ($source_file['approved'] == 1)
                            echo ' | <a href="'.$this->addonCurrent['permUrl'].'&amp;save=unapprove&amp;id='.$source_file['id'].'">'.htmlspecialchars(_('Unapprove')).'</a>';
                        else
                            echo ' | <a href="'.$this->addonCurrent['permUrl'].'&amp;save=approve&amp;id='.$source_file['id'].'">'.htmlspecialchars(_('Approve')).'</a>';
                    }
                    if ($this->addonCurrent['uploader'] == $_SESSION['userid'] || $_SESSION['role']['manageaddons'])
                        echo ' | <a href="'.$this->addonCurrent['permUrl'].'&amp;save=deletefile&amp;id='.$source_file['id'].'">'.htmlspecialchars(_('Delete File')).'</a><br />';
                }
                $n++;
                echo '</td></tr>';
            }
            echo '</table><br />';
        }
    }

    /* FIXME: this function needs a lot of cleanup / a rewrite. */
    function writeConfig()
    {
        try {
            $cAddon = new Addon($this->addonCurrent['id']);
            // Check permission
            if (User::$logged_in == false)
                throw new AddonException('You must be logged in to see this.');
            if ($_SESSION['role']['manageaddons'] == false && $cAddon->getUploader() != $_SESSION['userid'])
                throw new AddonException(htmlspecialchars(_('You do not have the necessary privileges to perform this action.')));
            
            echo '<hr /><h3>'.htmlspecialchars(_('Configuration')).'</h3>';
            echo '<form name="changeProps" action="'.$cAddon->getLink().'&amp;save=props" method="POST">';
            
            // Edit designer
            $designer = ($cAddon->getDesigner() == htmlspecialchars(_('Unknown'))) ? NULL : $cAddon->getDesigner();
            echo '<label for="designer_field">'.htmlspecialchars(_('Designer:')).'</label><br />';
            echo '<input type="text" name="designer" id="designer_field" value="'.$designer.'" /><br />';
            echo '<br />';
            
            // Edit description
            echo '<label for="desc_field">'.htmlspecialchars(_('Description:')).'</label> ('.sprintf(htmlspecialchars(_('Max %u characters')),'140').')<br />';
            echo '<textarea name="description" id="desc_field" rows="4" cols="60"
                onKeyUp="textLimit(document.getElementById(\'desc_field\'),140);"
                onKeyDown="textLimit(document.getElementById(\'desc_field\'),140);">'.$this->addonCurrent['description'].'</textarea><br />';

            // Submit
            echo '<input type="submit" value="'.htmlspecialchars(_('Save Properties')).'" />';
            echo '</form><br />';
            
            // Delete addon
            if ($cAddon->getUploader() == $_SESSION['userid']
                    || $_SESSION['role']['manageaddons'])
                echo '<input type="button" value="'.htmlspecialchars(_('Delete Addon')).'"
                    onClick="confirm_delete(\''.$cAddon->getLink().'&amp;save=delete\')" /><br /><br />';

            // Set status flags
            echo '<strong>'.htmlspecialchars(_('Status Flags:')).'</strong><br />';
            echo '<form method="POST" action="'.$cAddon->getLink().'&amp;save=status">';
            echo '<table id="addon_flags" class="info"><thead><tr><th></th>';
            if ($_SESSION['role']['manageaddons'])
                echo '<th>'.img_label(htmlspecialchars(_('Approved'))).'</th>
                    <th>'.img_label(htmlspecialchars(_('Invisible'))).'</th>';
            echo '<th>'.img_label(htmlspecialchars(_('Alpha'))).'</th>
                <th>'.img_label(htmlspecialchars(_('Beta'))).'</th>
                <th>'.img_label(htmlspecialchars(_('Release-Candidate'))).'</th>
                <th>'.img_label(htmlspecialchars(_('Latest'))).'</th>';
            if ($_SESSION['role']['manageaddons'])
                echo '<th>'.img_label(htmlspecialchars(_('DFSG Compliant'))).'</th>
                    <th>'.img_label(htmlspecialchars(_('Featured'))).'</th>';
            echo '<th>'.img_label(htmlspecialchars(_('Invalid Textures'))).'</th>';
            echo '</tr></thead>';
            $fields = array();
            $fields[] = 'latest';
            foreach ($cAddon->getAllRevisions() AS $rev_n => $revision) {
                // Row Header
                echo '<tr><td style="text-align: center;">';
                printf(htmlspecialchars(_('Rev %u:')),$rev_n);
                echo '</td>';

                if ($_SESSION['role']['manageaddons'] == true)  {
                    // F_APPROVED
                    echo '<td>';
                    if ($revision['status'] & F_APPROVED)
                        echo '<input type="checkbox" name="approved-'.$rev_n.'" checked />';
                    else
                        echo '<input type="checkbox" name="approved-'.$rev_n.'" />';
                    echo '</td>';
                    $fields[] = 'approved-'.$rev_n;

                    // F_INVISIBLE
                    echo '<td>';
                    if ($revision['status'] & F_INVISIBLE)
                        echo '<input type="checkbox" name="invisible-'.$rev_n.'" checked />';
                    else
                        echo '<input type="checkbox" name="invisible-'.$rev_n.'" />';
                    echo '</td>';
                    $fields[] = 'invisible-'.$rev_n;
                }
                
                // F_ALPHA
                echo '<td>';
                if ($revision['status'] & F_ALPHA)
                    echo '<input type="checkbox" name="alpha-'.$rev_n.'" checked />';
                else
                    echo '<input type="checkbox" name="alpha-'.$rev_n.'" />';
                echo '</td>';
                $fields[] = 'alpha-'.$rev_n;

                // F_BETA
                echo '<td>';
                if ($revision['status'] & F_BETA)
                    echo '<input type="checkbox" name="beta-'.$rev_n.'" checked />';
                else
                    echo '<input type="checkbox" name="beta-'.$rev_n.'" />';
                echo '</td>';
                $fields[] = 'beta-'.$rev_n;

                // F_RC
                echo '<td>';
                if ($revision['status'] & F_RC)
                    echo '<input type="checkbox" name="rc-'.$rev_n.'" checked />';
                else
                    echo '<input type="checkbox" name="rc-'.$rev_n.'" />';
                echo '</td>';
                $fields[] = 'rc-'.$rev_n;

                // F_LATEST
                echo '<td>';
                if ($revision['status'] & F_LATEST)
                    echo '<input type="radio" name="latest" value="'.$rev_n.'" checked />';
                else
                    echo '<input type="radio" name="latest" value="'.$rev_n.'" />';
                echo '</td>';
                
                if ($_SESSION['role']['manageaddons'])
                {
                    // F_DFSG
                    echo '<td>';
                    if ($revision['status'] & F_DFSG)
                        echo '<input type="checkbox" name="dfsg-'.$rev_n.'" checked />';
                    else
                        echo '<input type="checkbox" name="dfsg-'.$rev_n.'" />';
                    echo '</td>';
                    $fields[] = 'dfsg-'.$rev_n;

                    // F_FEATURED
                    echo '<td>';
                    if ($revision['status'] & F_FEATURED)
                        echo '<input type="checkbox" name="featured-'.$rev_n.'" checked />';
                    else
                        echo '<input type="checkbox" name="featured-'.$rev_n.'" />';
                    echo '</td>';
                    $fields[] = 'featured-'.$rev_n;
                }
                
                // F_TEX_NOT_POWER_OF_2
                echo '<td>';
                if ($revision['status'] & F_TEX_NOT_POWER_OF_2)
                    echo '<input type="checkbox" name="texpower-'.$rev_n.'" checked disabled />';
                else
                    echo '<input type="checkbox" name="texpower-'.$rev_n.'" disabled />';
                echo '</td>';

                echo '</tr>';
            }
            echo '</table>';
            echo '<input type="hidden" name="fields" value="'.implode(',',$fields).'" />';
            echo '<input type="submit" value="'.htmlspecialchars(_('Save Changes')).'" />';
            echo '</form><br />';

            // Moderator notes
            echo '<strong>'.htmlspecialchars(_('Notes from Moderator to Submitter:')).'</strong><br />';
            if ($_SESSION['role']['manageaddons'])
                echo '<form method="POST" action="'.$cAddon->getLink().'&amp;save=notes">';
            $fields = array();
            foreach ($cAddon->getAllRevisions() AS $rev_n => $revision) {
                printf(htmlspecialchars(_('Rev %u:')).'<br />',$rev_n);
                echo '<textarea name="notes-'.$rev_n.'"
                    id="notes-'.$rev_n.'" rows="4" cols="60"
                    onKeyUp="textLimit(document.getElementById(\'notes-'.$rev_n.'\'),4000);"
                    onKeyDown="textLimit(document.getElementById(\'notes-'.$rev_n.'\'),4000);">';
                echo $revision['moderator_note'];
                echo '</textarea><br />';
                $fields[] = 'notes-'.$rev_n;
            }
            if ($_SESSION['role']['manageaddons'])
            {
                echo '<input type="hidden" name="fields" value="'.implode(',',$fields).'" />';
                echo '<input type="submit" value="'.htmlspecialchars(_('Save Notes')).'" />';
                echo '</form>';
            }
        }
        catch (AddonException $e) {
            echo '<span class="error">'.$e->getMessage().'</span><br />';
        }
    }

    function viewInformation($config = true)
    {
        // Make sure addon exists
        if (!$this->addonCurrent)
            return false;
        $this->getRatingInfo();
        $this->writeInformation();

        if (User::$logged_in == false)
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
        // Check if logged in
        if (!User::$logged_in) {
            return false;
        }

        // Make sure no addon with this id exists
        if(sql_exist($this->addonType.'_revs', 'id', $fileid))
        {
            echo '<span class="error">'.htmlspecialchars(_('The add-on you are trying to create already exists.')).'</span><br />';
            return false;
        }

        // Check if we're creating a new add-on
        if (!Addon::exists($addonid))
        {
            echo htmlspecialchars(_('Creating a new add-on...')).'<br />';
            $fields = array('id','type','name','uploader','designer','license');
            $values = array($addonid,$this->addonType,
                mysql_real_escape_string($attributes['name']),
                mysql_real_escape_string($_SESSION['userid']),
                mysql_real_escape_string($attributes['designer']),
                mysql_real_escape_string($attributes['license']));
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
            echo htmlspecialchars(_('This add-on already exists. Adding revision...')).'<br />';
            // Update the addon name
            if (!sql_update('addons',
                    'id',mysql_real_escape_string($addonid),
                    'name',mysql_real_escape_string($attributes['name'])))
            {
                echo '<span class="error">'.htmlspecialchars(_('Failed to update the name record for this add-on.')).'</span><br />';
            }
            // Update license file record
            if (!sql_update('addons',
                    'id',
                    mysql_real_escape_string($addonid),
                    'license',
                    mysql_real_escape_string($attributes['license'])))
            {
                echo '<span class="error">'.htmlspecialchars(_('Failed to update the license record for this add-on.')).'</span><br />';
            }
        }

        // Add the new revision
        $prevRevQuerySql = 'SELECT `revision` FROM '.DB_PREFIX.$this->addonType.'_revs
            WHERE `addon_id` = \''.$addonid.'\' ORDER BY `revision` DESC LIMIT 1';
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
        $values = array($fileid,$addonid,$attributes['fileid'],$rev,
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
                "{$_SESSION['user']} has uploaded a new file for the {$this->addonType} '{$attributes['name']}' ($addonid)");
        return true;
    }

    /** To get the permanent link of the current addon */ 
    function permalink()
    {
        return 'addons.php?type='.$this->addonType.'&amp;name='.$this->addonCurrent['id'];
    }
}

function format_compat($format,$filetype)
{
    // FIXME: This should not be hardcoded
    switch ($filetype)
    {
        default:
            return htmlspecialchars(_('Unknown'));
        case 'karts':
            if ($format == 1)
            {
                return 'Pre-0.7';
            }
            if ($format == 2)
            {
                return '0.7 - 0.7.2';
            }
            return htmlspecialchars(_('Unknown'));
            break;
        case 'tracks':
        case 'arenas':
            if ($format == 1 || $format == 2)
            {
                return 'Pre-0.7';
            }
            if ($format >= 3 && $format <= 5)
            {
                return '0.7 - 0.7.2';
            }
            return htmlspecialchars(_('Unknown'));
            break;
    }
    return htmlspecialchars(_('Unknown'));
}
?>
