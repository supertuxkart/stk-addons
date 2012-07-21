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
    
    private function getImageProps() {
        // Get image
        $query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.$this->addon->getImage().'
            AND `approved` = 1
            LIMIT 1';
        $image_handle = sql_query($query);
	$array = array(
	    'image' => array('display' => false),
	    'image_upload' => array('display' => false));
        $string = '<div id="addon-image">';
        if ($image_handle && mysql_num_rows($image_handle) == 1) {
	    $image_result = mysql_fetch_assoc($image_handle);
	    $array['image'] = array(
		'display' => true,
		'url' => SITE_ROOT.'image.php?type=big&amp;pic='.$image_result['file_path']
	    );
	}

        // Add upload button below image (or in place of image)
        if (User::$logged_in && ($this->addon->getUploader() == $_SESSION['userid'] || $_SESSION['role']['manageaddons']))
	    $array['image_upload'] = array(
		'display' => true,
		'target' => SITE_ROOT.'upload.php?type='.$this->addon->getType().'&amp;name='.$this->addon->getId().'&amp;action=file',
		'button_label' => htmlspecialchars(_('Upload Image'))
	    );
        return $array;
    }
    
    public function fillTemplate() {
	$tpl = array();
	$tpl['addon'] = array(
	    'name' => $this->addon->getName($this->addon->getId()),
	    'description' => $this->addon->getDescription(),
	    'type' => $this->addon->getType(),
	    'rating' => array(
		'label' => $this->rating->getRatingString(),
		'percent' => $this->rating->getAvgRatingPercent()
	    ),
	    'badges' => AddonViewer::badges($this->addon->getStatus())
	);
	
        $addonUser = new coreUser();
        $addonUser->selectById($this->addon->getUploader());
        $latestRev = $this->addon->getLatestRevision();
	$info = array(
	    'type' => array(
		'label' => htmlspecialchars(_('Type:')),
		'value' => htmlspecialchars(_('Arena')) // Not shown except for arenas
	    ),
	    'designer' => array(
		'label' => htmlspecialchars(_('Designer:')),
		'value' => htmlspecialchars($this->addon->getDesigner())
	    ),
	    'upload_date' => array(
		'label' => htmlspecialchars(_('Upload date:')),
		'value' => $latestRev['timestamp']
	    ),
	    'submitter' => array(
		'label' => htmlspecialchars(_('Submitted by:')),
		'value' => '<a href="'.SITE_ROOT.'users.php?user='.$addonUser->userCurrent['user'].'">'.htmlspecialchars($addonUser->userCurrent['name']).'</a>'
	    ),
	    'revision' => array(
		'label' => htmlspecialchars(_('Revision:')),
		'value' => $latestRev['revision']
	    ),
	    'compatibility' => array(
		'label' => htmlspecialchars(_('Compatible with:')),
		'value' => format_compat($latestRev['format'],$this->addon->getType())
	    ),
	    'license' => array(
		'label' => htmlspecialchars(_('License')),
		'value' => htmlspecialchars($this->addon->getLicense())
	    ),
	    'link' => array(
		'label' => htmlspecialchars(_('Permalink')),
		'value' => File::rewrite($this->addon->getLink())
	    )
	);
	$tpl['addon']['info'] = $info;
	$tpl['addon']['warnings'] = NULL;
	if ($latestRev['status'] & F_TEX_NOT_POWER_OF_2)
	    $tpl['addon']['warnings'] = htmlspecialchars(_('Warning: This addon may not display correctly on some systems. It uses textures that may not be compatible with all video cards.'));
	
	$tpl['addon']['vote'] = array(
	    'display' => User::$logged_in,
	    'label' => htmlspecialchars(_('Your Rating:')),
	    'controls' => $this->rating->displayUserRating()
	);
	
	// Download button
	$file_path = $this->addon->getFile((int)$this->latestRev['revision']);
        if ($file_path !== false && File::exists($file_path)) {
	    $button_text = htmlspecialchars(sprintf(_('Download %s'),$this->addon->getName($this->addon->getId())));
	    $shrink = (strlen($button_text) > 20) ? 'style="font-size: 1.1em !important;"' : NULL;
	    $tpl['addon']['dl'] = array(
		'display' => true,
		'label' => $button_text,
		'url' => DOWN_LOCATION.$file_path,
		'shrink' => $shrink
	    );
        } else {
	    $tpl['addon']['dl'] = array('display' => false);
        }
	
	// Revision list
	$rev_list = array(
	    'label' => htmlspecialchars(_('Revisions')),
	    'upload' => array(
		'display' => false,
		'target' => SITE_ROOT.'upload.php?type='.$this->addon->getType().'&amp;name='.$this->addon->getId(),
		'button_label' => htmlspecialchars(_('Upload Revision'))
	    ),
	    'revisions' => array()
	);
	if (User::$logged_in && ($this->addon->getUploader() == User::$user_id || $_SESSION['role']['manageaddons']))
	    $rev_list['upload']['display'] = true;
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
	    $rev = array(
		'number' => $rev_n,
		'timestamp' => $revision['timestamp'],
		'file' => array(
		    'path' => $this->addon->getFile($rev_n)
		),
		'dl_label' => htmlspecialchars(sprintf(_('Download revision %u'),$rev_n))
	    );
	    if (!File::exists($rev['file']['path']))
		continue;
	    $rev_list['revisions'][] = $rev;
	}
	$tpl['addon']['revision_list'] = $rev_list;
	
	// Image list
	$im_list = array(
	    'label' => htmlspecialchars(_('Images')),
	    'upload' => array(
		'display' => false,
		'target' => SITE_ROOT.'upload.php?type='.$this->addon->getType().'&amp;name='.$this->addon->getId().'&amp;action=file',
		'button_label' => htmlspecialchars(_('Upload Image'))
	    ),
	    'images' => array(),
	    'no_images_message' => htmlspecialchars(_('No images have been uploaded for this addon yet.'))
	);
	if (User::$logged_in && ($this->addon->getUploader() == User::$user_id || $_SESSION['role']['manageaddons']))
	    $im_list['upload']['display'] = true;
	// Get images
        $imageFilesQuery = 'SELECT * FROM `'.DB_PREFIX.'files`
            WHERE `addon_id` = \''.$this->addon->getId().'\'
            AND `file_type` = \'image\'';
        $imageFilesHandle = sql_query($imageFilesQuery);
	
        // Create an array of all of the images that the current user can see
        $image_files = array();
        for ($i = 1; $i <= mysql_num_rows($imageFilesHandle); $i++) {
            $imageFilesResult = mysql_fetch_assoc($imageFilesHandle);
	    $imageFilesResult['url'] = DOWN_LOCATION.$imageFilesResult['file_path'];
	    $imageFilesResult['thumb']['url'] = SITE_ROOT.'image.php?type=medium&amp;pic='.$imageFilesResult['file_path'];
	    $admin_links = NULL;
            if (User::$logged_in) {
                if ($_SESSION['role']['manageaddons']) {
                    if ($imageFilesResult['approved'] == 1)
                        $admin_links .= '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=unapprove&amp;id='.$imageFilesResult['id']).'">'.htmlspecialchars(_('Unapprove')).'</a>';
                    else
                        $admin_links .= '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=approve&amp;id='.$imageFilesResult['id']).'">'.htmlspecialchars(_('Approve')).'</a>';
                    $admin_links .= '<br />';
                }
                if ($_SESSION['role']['manageaddons'] || $this->addon->getUploader() == $_SESSION['userid']) {
                    if ($this->addon->getType() == 'karts') {
                        if ($this->addon->getImage(true) != $imageFilesResult['id']) {
                            $admin_links .= '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=seticon&amp;id='.$imageFilesResult['id']).'">'.htmlspecialchars(_('Set Icon')).'</a><br />';
                        }
                    }
                    if ($this->addon->getImage() != $imageFilesResult['id']) {
                        $admin_links .= '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=setimage&amp;id='.$imageFilesResult['id']).'">'.htmlspecialchars(_('Set Image')).'</a><br />';
                    }
                    $admin_links .= '<a href="'.File::rewrite($this->addon->getLink().'&amp;save=deletefile&amp;id='.$imageFilesResult['id']).'">'.htmlspecialchars(_('Delete File')).'</a><br />';
                }
            }
	    $imageFilesResult['admin_links'] = $admin_links;
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
	$im_list['images'] = $image_files;
	$tpl['addon']['image_list'] = $im_list;

	// Source files
	$s_list = array(
	    'label' => htmlspecialchars(_('Source Files')),
	    'upload' => array(
		'display' => false,
		'target' => SITE_ROOT.'upload.php?type='.$this->addon->getType().'&amp;name='.$this->addon->getId().'&amp;action=file',
		'button_label' => htmlspecialchars(_('Upload Source File'))
	    ),
	    'files' => array(),
	    'no_files_message' => htmlspecialchars(_('No source files have been uploaded for this addon yet.'))
	);
	if (User::$logged_in && ($this->addon->getUploader() == User::$user_id || $_SESSION['role']['manageaddons']))
	    $s_list['upload']['display'] = true;
	
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
	    $sourceFilesResult['label'] = sprintf(htmlspecialchars(_('Source File %u')),count($source_files) + 1);
	    $sourceFilesResult['details'] = NULL;
            if ($sourceFilesResult['approved'] == 0) $sourceFilesResult['details'] .= '('.htmlspecialchars(_('Not Approved')).') ';
	    $sourceFilesResult['details'] .= '<a href="'.DOWN_LOCATION.$sourceFilesResult['file_path'].'" rel="nofollow">'.htmlspecialchars(_('Download')).'</a>';
	    if (User::$logged_in) {
                if ($_SESSION['role']['manageaddons'])
                {
                    if ($sourceFilesResult['approved'] == 1)
                        $sourceFilesResult['details'] .= ' | <a href="'.File::rewrite($this->addon->getLink().'&amp;save=unapprove&amp;id='.$sourceFilesResult['id']).'">'.htmlspecialchars(_('Unapprove')).'</a>';
                    else
                        $sourceFilesResult['details'] .= ' | <a href="'.File::rewrite($this->addon->getLink().'&amp;save=approve&amp;id='.$sourceFilesResult['id']).'">'.htmlspecialchars(_('Approve')).'</a>';
                }
                if ($this->addon->getUploader() == $_SESSION['userid'] || $_SESSION['role']['manageaddons'])
                    $sourceFilesResult['details'] .= ' | <a href="'.File::rewrite($this->addon->getLink().'&amp;save=deletefile&amp;id='.$sourceFilesResult['id']).'">'.htmlspecialchars(_('Delete File')).'</a><br />';
            }
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
	$s_list['files'] = $source_files;
	$tpl['addon']['source_list'] = $s_list;
	$tpl['addon'] = array_merge($tpl['addon'], $this->getImageProps());
	
	Template::assignments($tpl);
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

	// Mark whether or not an add-on has ever been included in STK
	if ($_SESSION['role']['manageaddons']) {
	    echo '<strong>'.htmlspecialchars(_('Included in Game Versions:')).'</strong><br />';
	    echo '<form method="POST" action="'.File::rewrite($this->addon->getLink().'&amp;save=include').'">';
	    echo htmlspecialchars(_('Start:')).' <input type="text" name="incl_start" size="6" value="'.htmlspecialchars($this->addon->getIncludeMin()).'" /><br />';
	    echo htmlspecialchars(_('End:')).' <input type="text" name="incl_end" size="6" value="'.htmlspecialchars($this->addon->getIncludeMax()).'" /><br />';
	    echo '<input type="submit" value="'.htmlspecialchars(_('Save')).'" /><br />';
	    echo '</form><br />';
	}

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
