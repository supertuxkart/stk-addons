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

class AddonViewer
{
    /**
     * @var Addon Current addon
     */
    private $addon;
    private $latestRev;
    /**
     * @var Ratings
     */
    private $rating = false;

    /**
     * Constructor
     * @param string $id Add-On ID
     */
    function AddonViewer($id) {
        $this->addon = new Addon($id);
        $this->latestRev = $this->addon->getLatestRevision();
        $this->rating = new Ratings($id);
    }

    function __toString() {
        $return = '';
        try {
            $return .= $this->displayHeader();
            $return .= $this->displayRating();
            $return .= $this->displayImage();
            $return .= AddonViewer::badges($this->addon->getStatus());
            $return .= $this->displayInformation();
            $return .= $this->displayDLButton();
            $return .= $this->displayLicense();
            $return .= $this->displayLink();
            $return .= $this->displayRevisions();
            $return .= $this->displayImageList();
            $return .= $this->displaySourceFiles();
            
            if (User::$logged_in) {
                //write configuration for the submiter and administrator
                if($_SESSION['role']['manageaddons'] == true
                        || $this->addon->getUploader() == User::$user_id)
                {
                    $return .= $this->displayConfig();
                }
            }
        }
        catch (Exception $e) {
            $return .= '<span class="error">'.$e->getMessage().'</span><br />';
        }
        return $return;
    }

    private function displayDLButton() {
        // Get download path
        $file_path = $this->addon->getFile((int)$this->latestRev['revision']);
        if ($file_path !== false) {
            if (file_exists(UP_LOCATION.$file_path)) {
		$button_text = htmlspecialchars(sprintf(_('Download %s'),$this->addon->getName($this->addon->getId())));
		$shrink_text = (strlen($button_text) > 20) ? 'style="font-size: 1.1em !important;"' : NULL;
		$string = '<div id="dl_button">';
		$string .= '<div class="left"></div><div class="center" '.$shrink_text.'>';
                $string .= '<a href="'.DOWN_LOCATION.$file_path.'" rel="nofollow">'.$button_text.'</a>';
		$string .= '</div><div class="right"></div>';
		$string .= '</div><br />';
            } else {
                $string = '<span class="error">'.htmlspecialchars(_('File not found.')).'</span><br />';
            }
        } else {
            $string = '<span class="error">'.htmlspecialchars(_('File not found.')).'</span><br />';
        }
        return $string;
    }
    
    private function displayHeader() {
        return '<h1>'.htmlspecialchars(Addon::getName($this->addon->getId()));
    }
    
    private function displayImage() {
        // Get image
        $query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.$this->addon->getImage().'
            AND `approved` = 1
            LIMIT 1';
        $image_handle = sql_query($query);
        $string = '<div id="addon-image">';
        if ($image_handle && mysql_num_rows($image_handle) == 1)
        {
            $image_result = mysql_fetch_assoc($image_handle);
            $string .= '<img class="preview" src="'.SITE_ROOT.'image.php?type=big&amp;pic='.$image_result['file_path'].'" />';
        }
        // Add upload button below image (or in place of image)
        if (User::$logged_in && $this->addon->getUploader() == $_SESSION['userid'])
        {
            $string .= '<br /><form method="POST" action="'.SITE_ROOT.'upload.php?type='.$this->addon->getType().'&amp;name='.$this->addon->getId().'&amp;action=file">';
            $string .= '<input type="submit" value="'.htmlspecialchars(_('Upload Image')).'" />';
            $string .= '</form>';
        }
        $string .= '</div>';
        return $string;
    }
    
    private function displayImageList() {
        ob_start();
        echo '<h3>'.htmlspecialchars(_('Images')).'</h3>';
        // Add upload button to the right of the Images label
        if (User::$logged_in && $this->addon->getUploader() == $_SESSION['userid'])
        {
            echo '<div style="float: right;"><form method="POST" action="'.SITE_ROOT.'upload.php?type='.$this->addon->getType().'&amp;name='.$this->addon->getId().'&amp;action=file">';
            echo '<input type="submit" value="'.htmlspecialchars(_('Upload Image')).'" />';
            echo '</form></div>';
        }
        $imageFilesQuery = 'SELECT * FROM `'.DB_PREFIX.'files`
            WHERE `addon_id` = \''.$this->addon->getId().'\'
            AND `file_type` = \'image\'';
        $imageFilesHandle = sql_query($imageFilesQuery);
    
        // Create an array of all of the images that the current user can see
        $image_files = array();
        for ($i = 1; $i <= mysql_num_rows($imageFilesHandle); $i++) {
            $imageFilesResult = mysql_fetch_assoc($imageFilesHandle);
            if (User::$logged_in &&
                    ($this->addon->getUploader() == $_SESSION['userid']
                    || $_SESSION['role']['manageaddons']))
            {
                $image_files[] = $imageFilesResult;
                continue;
            }
            if ($imageFilesResult['approved'] == 1) {
                $image_files[] = $imageFilesResult;
            }
        }
        
        if (count($image_files) == 0) {
            echo htmlspecialchars(_('No images have been uploaded for this addon yet.')).'<br />';
            return ob_get_clean();
        }

        echo '<div class="image_thumbs">';
        foreach ($image_files AS $source_file) {
            if ($source_file['approved'] == 1)
                $div_style = 'image_thumb_container';
            else
                $div_style = 'image_thumb_container unapproved';
            echo '<div class="'.$div_style.'">';
            echo '<a href="'.DOWN_LOCATION.$source_file['file_path'].'" target="_blank" style="target-new: tab;">';
            echo '<img src="'.SITE_ROOT.'image.php?type=medium&amp;pic='.$source_file['file_path'].'" />';
            echo '</a><br />';
            if (User::$logged_in) {
                if ($_SESSION['role']['manageaddons']) {
                    if ($source_file['approved'] == 1)
                        echo '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=unapprove&amp;id='.$source_file['id']).'">'.htmlspecialchars(_('Unapprove')).'</a>';
                    else
                        echo '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=approve&amp;id='.$source_file['id']).'">'.htmlspecialchars(_('Approve')).'</a>';
                    echo '<br />';
                }
                if ($_SESSION['role']['manageaddons'] || $this->addon->getUploader() == $_SESSION['userid']) {
                    if ($this->addon->getType() == 'karts') {
                        if ($this->addon->getImage(true) != $source_file['id']) {
                            echo '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=seticon&amp;id='.$source_file['id']).'">'.htmlspecialchars(_('Set Icon')).'</a><br />';
                        }
                    }
                    if ($this->addon->getImage() != $source_file['id']) {
                        echo '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=setimage&amp;id='.$source_file['id']).'">'.htmlspecialchars(_('Set Image')).'</a><br />';
                    }
                    echo '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=deletefile&amp;id='.$source_file['id']).'">'.htmlspecialchars(_('Delete File')).'</a><br />';
                }
            }
            echo '</div>';
        }
        echo '</div>';
        echo '<br />';
        $string = ob_get_clean();
        return $string;
    }
    
    private function displayInformation() {
        $string = '<br /><span id="addon-description">'.$this->addon->getDescription().'</span>
        <table class="info">';
        if ($this->addon->getType() == 'arenas')
        {
            $string .= '<tr><td><strong>'.htmlspecialchars(_('Type:')).'</strong></td><td>'.htmlspecialchars(_('Arena')).'</td></tr>';
        }
        $latestRev = $this->addon->getLatestRevision();
        $addonUser = new coreUser();
        $addonUser->selectById($this->addon->getUploader());
        $string .= '<tr><td><strong>'.htmlspecialchars(_('Designer:')).'</strong></td><td>'.htmlspecialchars($this->addon->getDesigner()).'</td></tr>
        <tr><td><strong>'.htmlspecialchars(_('Upload date:')).'</strong></td><td>'.$latestRev['timestamp'].'</td></tr>
        <tr><td><strong>'.htmlspecialchars(_('Submitted by:')).'</strong></td><td><a href="'.SITE_ROOT.'users.php?user='.$addonUser->userCurrent['user'].'">'.htmlspecialchars($addonUser->userCurrent['name']).'</a></td></tr>
        <tr><td><strong>'.htmlspecialchars(_('Revision:')).'</strong></td><td>'.$latestRev['revision'].'</td></tr>
        <tr><td><strong>'.htmlspecialchars(_('Compatible with:')).'</strong></td><td>'.format_compat($latestRev['format'],$this->addon->getType()).'</td></tr>';
        if (User::$logged_in) {
            $string .= '<tr><td><strong>'.htmlspecialchars(_('Your Rating: ')).'</strong></td><td>';
            $string .= $this->rating->displayUserRating();
	}
        $string .= '</td></tr></table>';

        if ($latestRev['status'] & F_TEX_NOT_POWER_OF_2)
        {
            $string .= htmlspecialchars(_('Warning: This addon may not display correctly on some systems. It uses textures that may not be compatible with all video cards.'))."<br />\n";
        }
        return $string;
    }
    
    private function displayLicense() {
        $string = '<br />
            <h3>'.htmlspecialchars(_('License')).'</h3>
            <textarea name="license" rows="4" cols="60">'.htmlspecialchars($this->addon->getLicense()).'</textarea>
            <br /><br />';
        return $string;
    }
    
    private function displayLink() {
        // Print a permanent reference link (permalink) to this addon
        return '<h3>'.htmlspecialchars(_('Permalink')).'</h3>
        <a href="'.File::rewrite($this->addon->getLink()).'">'.File::rewrite($this->addon->getLink()).'</a><br /><br />';
    }
    
    private function displayRating() {
        $string = <<< EOL
            <div id="rating-container">
                <div class="rating">
                    <div class="emptystars"></div>
                    <div class="fullstars" style="width: {$this->rating->getAvgRatingPercent()}%;"></div>
                </div>
                <p>{$this->rating->getRatingString()}</p>
            </div></h1>
EOL;
        return $string;
    }

    private function displayRevisions() {
        ob_start();
        echo '<h3>'.htmlspecialchars(_('Revisions')).'</h3>';

        // Add upload button to the right of the Revisions label
        if (User::$logged_in && ($this->addon->getUploader() == User::$user_id || $_SESSION['role']['manageaddons']))
        {
            echo '<div style="float: right;"><form method="POST" action="'.SITE_ROOT.'upload.php?type='.$this->addon->getType().'&amp;name='.$this->addon->getId().'">';
            echo '<input type="submit" value="'.htmlspecialchars(_('Upload Revision')).'" />';
            echo '</form></div>';
        }

        echo '<table>';
        $revisions = $this->addon->getAllRevisions();
        foreach ($revisions AS $rev_n => $revision) {
            if (!User::$logged_in) {
                // Users not logged in cannot see unapproved addons
                if (!($revision['status'] & F_APPROVED))
                    continue;
            } else {
                // User is logged in
                // If the user is not the uploader, or moderators, then they
                // cannot see unapproved addons
                if (($this->addon->getUploader() != $_SESSION['userid']
                        && !$_SESSION['role']['manageaddons'])
                        && !($revision['status'] & F_APPROVED))
                    continue;
            }

            // Display revisions
            echo '<tr><td>'.$revision['timestamp'].'</td><td>';
            // Get download path
            $file_path = $this->addon->getFile($rev_n);
            if ($file_path !== false) {
                if (file_exists(UP_LOCATION.$file_path)) {
                    echo '<a href="'.DOWN_LOCATION.$file_path.'" rel="nofollow">';
                    printf(htmlspecialchars(_('Download revision %u')),$rev_n);
                    echo '</a>';
                } else {
                    echo htmlspecialchars(_('Revision')).' '.$rev_n.' - '.htmlspecialchars(_('File not found.'));
                }
            } else {
                echo htmlspecialchars(_('Revision')).' '.$rev_n.' - '.htmlspecialchars(_('File not found.'));
            }
            echo '</td></tr>';
        }
        echo '</table><br />';
        $string = ob_get_clean();
        return $string;
    }
    
    /**
     * Generate HTML for the list of source files for an add-on
     * @return string 
     */
    private function displaySourceFiles() {
        ob_start();
        echo '<h3>'.htmlspecialchars(_('Source Files')).'</h3>';
        // Add upload button to the right of the Source Files label
        if (User::$logged_in && $this->addon->getUploader() == $_SESSION['userid'])
        {
            echo '<div style="float: right;"><form method="POST" action="'.SITE_ROOT.'upload.php?type='.$this->addon->getType().'&amp;name='.$this->addon->getId().'&amp;action=file">';
            echo '<input type="submit" value="'.htmlspecialchars(_('Upload Source File')).'" />';
            echo '</form></div>';
        }
        
        // Search database for source files
        $query = 'SELECT * FROM `'.DB_PREFIX."files`
            WHERE `addon_type` = '{$this->addon->getType()}'
            AND `addon_id` = '{$this->addon->getId()}'
            AND `file_type` = 'source'";
        $sourceFilesHandle = sql_query($query);
    
        // Create an array of all of the source files that the current user can see
        $source_files = array();
        for ($i = 1; $i <= mysql_num_rows($sourceFilesHandle); $i++) {
            $sourceFilesResult = mysql_fetch_assoc($sourceFilesHandle);
            if (User::$logged_in &&
                    ($this->addon->getUploader() == $_SESSION['userid']
                    || $_SESSION['role']['manageaddons'])) {
                $source_files[] = $sourceFilesResult;
                continue;
            }
            if ($sourceFilesResult['approved'] == 1) {
                $source_files[] = $sourceFilesResult;
            }
        }

        // Check for 0 entries
        if (count($source_files) == 0) {
            echo htmlspecialchars(_('No source files have been uploaded for this addon yet.')).'<br />';
            $string = ob_get_clean();
            return $string;
        }
        
        // Generate the table of entries
        echo '<table>';
        $n = 1;
        foreach ($source_files AS $source_file) {
            echo '<tr>';
            $approved = NULL;
            if ($source_file['approved'] == 0) $approved = ' ('.htmlspecialchars(_('Not Approved')).')';
            printf('<td><strong>'.htmlspecialchars(_('Source File %u')).'</strong>'.$approved.'</td>',$n);
            echo '<td><a href="'.DOWN_LOCATION.$source_file['file_path'].'" rel="nofollow">'.htmlspecialchars(_('Download')).'</a>';
            if (User::$logged_in)
            {
                if ($_SESSION['role']['manageaddons'])
                {
                    if ($source_file['approved'] == 1)
                        echo ' | <a href="'.File::rewrite($this->addon->getLink().'&amp;save=unapprove&amp;id='.$source_file['id']).'">'.htmlspecialchars(_('Unapprove')).'</a>';
                    else
                        echo ' | <a href="'.File::rewrite($this->addon->getLink().'&amp;save=approve&amp;id='.$source_file['id']).'">'.htmlspecialchars(_('Approve')).'</a>';
                }
                if ($this->addon->getUploader() == $_SESSION['userid'] || $_SESSION['role']['manageaddons'])
                    echo ' | <a href="'.File::rewrite($this->addon->getLink().'&amp;save=deletefile&amp;id='.$source_file['id']).'">'.htmlspecialchars(_('Delete File')).'</a><br />';
            }
            $n++;
            echo '</td></tr>';
        }
        echo '</table><br />';
        $string = ob_get_clean();
        return $string;
    }
    
    /**
     * Output HTML to display flag badges
     * @param integer $status The 'ststus' value to interperet
     */
    private static function badges($status) {
        $string = '';
        if ($status & F_FEATURED)
            $string .= '<span class="badge f_featured">'.htmlspecialchars(_('Featured')).'</span>';
        if ($status & F_ALPHA)
            $string .= '<span class="badge f_alpha">'.htmlspecialchars(_('Alpha')).'</span>';
        if ($status & F_BETA)
            $string .= '<span class="badge f_beta">'.htmlspecialchars(_('Beta')).'</span>';
        if ($status & F_RC)
            $string .= '<span class="badge f_rc">'.htmlspecialchars(_('Release-Candidate')).'</span>';
        if ($status & F_DFSG)
            $string .= '<span class="badge f_dfsg">'.htmlspecialchars(_('DFSG Compliant')).'</span>';
        return $string;
    }

    private function displayConfig() {
        ob_start();
        // Check permission
        if (User::$logged_in == false)
            throw new AddonException('You must be logged in to see this.');
        if ($_SESSION['role']['manageaddons'] == false && $this->addon->getUploader() != $_SESSION['userid'])
            throw new AddonException(htmlspecialchars(_('You do not have the necessary privileges to perform this action.')));

        echo '<br /><hr /><br /><h3>'.htmlspecialchars(_('Configuration')).'</h3>';
        echo '<form name="changeProps" action="'.File::rewrite($this->addon->getLink().'&amp;save=props').'" method="POST">';

        // Edit designer
        $designer = ($this->addon->getDesigner() == htmlspecialchars(_('Unknown'))) ? NULL : $this->addon->getDesigner();
        echo '<label for="designer_field">'.htmlspecialchars(_('Designer:')).'</label><br />';
        echo '<input type="text" name="designer" id="designer_field" value="'.$designer.'" /><br />';
        echo '<br />';

        // Edit description
        echo '<label for="desc_field">'.htmlspecialchars(_('Description:')).'</label> ('.sprintf(htmlspecialchars(_('Max %u characters')),'140').')<br />';
        echo '<textarea name="description" id="desc_field" rows="4" cols="60"
            onKeyUp="textLimit(document.getElementById(\'desc_field\'),140);"
            onKeyDown="textLimit(document.getElementById(\'desc_field\'),140);">'.$this->addon->getDescription().'</textarea><br />';

        // Submit
        echo '<input type="submit" value="'.htmlspecialchars(_('Save Properties')).'" />';
        echo '</form><br />';

        // Delete addon
        if ($this->addon->getUploader() == $_SESSION['userid']
                || $_SESSION['role']['manageaddons'])
            echo '<input type="button" value="'.htmlspecialchars(_('Delete Addon')).'"
                onClick="confirm_delete(\''.File::rewrite($this->addon->getLink().'&amp;save=delete').'\')" /><br /><br />';

        // Set status flags
        echo '<strong>'.htmlspecialchars(_('Status Flags:')).'</strong><br />';
        echo '<form method="POST" action="'.File::rewrite($this->addon->getLink().'&amp;save=status').'">';
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
        echo '<th>'.img_label(htmlspecialchars(_('Invalid Textures'))).'</th><th></th>';
        echo '</tr></thead>';
        $fields = array();
        $fields[] = 'latest';
        foreach ($this->addon->getAllRevisions() AS $rev_n => $revision) {
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
	    
	    // Delete revision button
	    echo '<td>';
	    echo '<input type="button" value="'.sprintf(htmlspecialchars(_('Delete revision %d')),$rev_n).'" onClick="confirm_delete(\''.File::rewrite($this->addon->getLink().'&amp;save=del_rev&amp;rev='.$rev_n).'\');" />';
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
            echo '<form method="POST" action="'.File::rewrite($this->addon->getLink().'&amp;save=notes').'">';
        $fields = array();
        foreach ($this->addon->getAllRevisions() AS $rev_n => $revision) {
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
        return ob_get_clean();
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
                return '0.7 - 0.7.3';
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
                return '0.7 - 0.7.3';
            }
            return htmlspecialchars(_('Unknown'));
            break;
    }
    return htmlspecialchars(_('Unknown'));
}
?>
