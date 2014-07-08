<?php
/**
 * Copyright        2010 Lucas Baudin <xapantu@gmail.com>
 *           2011 - 2014 Stephen Just <stephenjust@gmail.com>
 *                  2014 Daniel Butum <danibutum at gmail dot com>
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
 * Class AddonViewer
 */
class AddonViewer
{

    /**
     * @var Addon Current addon
     */
    private $addon;

    /**
     * @var string
     */
    private $latestRev;

    /**
     * @var Ratings
     */
    private $rating = false;

    /**
     * Constructor
     *
     * @param string $id Add-On ID
     */
    public function __construct($id)
    {
        $this->addon = new Addon($id);
        $this->latestRev = $this->addon->getLatestRevision();
        $this->rating = new Ratings($id);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $return = '';
        try
        {
            if (User::isLoggedIn())
            {
                // write configuration for the submiter and administrator
                if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS) || $this->addon->getUploaderId() === User::getLoggedId())
                {
                    $return .= $this->displayConfig();
                }
            }
        }
        catch(Exception $e)
        {
            $return .= '<span class="error">' . $e->getMessage() . '</span><br />';
        }

        return $return;
    }

    /**
     * Fill template with addon info
     *
     * @param Template $template
     */
    public function fillTemplate($template)
    {
        $tpl = array();
        $tpl['addon'] = array(
            'name'         => $this->addon->getName(),
            'description'  => $this->addon->getDescription(),
            'type'         => $this->addon->getType(),
            'rating'       => array(
                'label'      => $this->rating->getRatingString(),
                'percent'    => $this->rating->getAvgRatingPercent(),
                'decimal'    => $this->rating->getAvgRating(),
                'count'      => $this->rating->getNumRatings(),
                'min_rating' => 0.5,
                'max_rating' => 3.0
            ),
            'badges'       => AddonViewer::badges($this->addon->getStatus()),
            'image'        => array(
                'display' => false,
                'url'     => null
            ),
            'image_upload' => array(
                'display'      => false,
                'target'       => null,
                'button_label' => null
            )
        );

        // Get image
        $image = Cache::getImage($this->addon->getImage(), array('size' => 'big'));
        if ($this->addon->getImage() != 0 && $image['exists'] == true && $image['approved'] == true)
        {
            $tpl['addon']['image'] = array(
                'display' => true,
                'url'     => $image['url']
            );
        }
        // Add upload button below image (or in place of image)
        if ($this->addon->getUploaderId() === User::getLoggedId() || User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            $tpl['addon']['image_upload'] = array(
                'display'      => true,
                'target'       => SITE_ROOT . 'upload.php?type=' . $this->addon->getType() . '&amp;name=' . $this->addon->getId(
                    ) . '&amp;action=file',
                'button_label' => _h('Upload Image')
            );
        }

        $addonUser = User::getFromID($this->addon->getUploaderId());
        $latestRev = $this->addon->getLatestRevision();
        $info = array(
            'type'          => array(
                'label' => _h('Type:'),
                'value' => _h('Arena') // Not shown except for arenas
            ),
            'designer'      => array(
                'label' => _h('Designer:'),
                'value' => h($this->addon->getDesigner())
            ),
            'upload_date'   => array(
                'label' => _h('Upload date:'),
                'value' => $latestRev['timestamp']
            ),
            'submitter'     => array(
                'label' => _h('Submitted by:'),
                'value' => '<a href="' . SITE_ROOT . 'users.php?user=' . $addonUser->getUserName() . '">' . h(
                        $addonUser->getLoggedUserName()
                    ) . '</a>'
            ),
            'revision'      => array(
                'label' => _h('Revision:'),
                'value' => $latestRev['revision']
            ),
            'compatibility' => array(
                'label' => _h('Compatible with:'),
                'value' => Util::getVersionFormat($latestRev['format'], $this->addon->getType())
            ),
            'license'       => array(
                'label' => _h('License'),
                'value' => h($this->addon->getLicense())
            ),
            'link'          => array(
                'label' => _h('Permalink'),
                'value' => File::rewrite($this->addon->getLink())
            )
        );
        $tpl['addon']['info'] = $info;
        $tpl['addon']['warnings'] = null;
        if ($latestRev['status'] & F_TEX_NOT_POWER_OF_2)
        {
            $tpl['addon']['warnings'] = _h(
                'Warning: This addon may not display correctly on some systems. It uses textures that may not be compatible with all video cards.'
            );
        }

        $tpl['addon']['vote'] = array(
            'display'  => User::isLoggedIn(),
            'label'    => _h('Your Rating:'),
            'controls' => $this->rating->displayUserRating()
        );

        // Download button
        $file_path = $this->addon->getFile((int)$this->latestRev['revision']);
        if ($file_path !== false && File::exists($file_path))
        {
            $button_text = h(sprintf(_('Download %s'), $this->addon->getName()));
            $shrink = (mb_strlen($button_text) > 20) ? 'style="font-size: 1.1em !important;"' : null;
            $tpl['addon']['dl'] = array(
                'display'            => true,
                'label'              => $button_text,
                'url'                => DOWNLOAD_LOCATION . $file_path,
                'shrink'             => $shrink,
                'use_client_message' => _h('Download this add-on in game!')
            );
        }
        else
        {
            $tpl['addon']['dl'] = array('display' => false);
        }

        // Revision list
        $rev_list = array(
            'label'     => _h('Revisions'),
            'upload'    => array(
                'display'      => false,
                'target'       => SITE_ROOT . 'upload.php?type=' . $this->addon->getType() . '&amp;name=' . $this->addon->getId(),
                'button_label' => _h('Upload Revision')
            ),
            'revisions' => array()
        );

        if ($this->addon->getUploaderId() == User::getLoggedId() || User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            $rev_list['upload']['display'] = true;
        }

        $revisions = $this->addon->getAllRevisions();
        foreach ($revisions AS $rev_n => $revision)
        {
            if (!User::isLoggedIn())
            {
                // Users not logged in cannot see unapproved addons
                if (!($revision['status'] & F_APPROVED))
                {
                    continue;
                }
            }
            else
            {
                // User is logged in
                // If the user is not the uploader, or moderators, then they
                // cannot see unapproved addons
                if ($this->addon->getUploaderId() !== User::getLoggedId() && !User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && !($revision['status'] & F_APPROVED))
                {
                    continue;
                }
            }
            $rev = array(
                'number'    => $rev_n,
                'timestamp' => $revision['timestamp'],
                'file'      => array(
                    'path' => DOWNLOAD_LOCATION . $this->addon->getFile($rev_n)
                ),
                'dl_label'  => h(sprintf(_('Download revision %u'), $rev_n))
            );
            if (!File::exists($rev['file']['path']))
            {
                continue;
            }
            $rev_list['revisions'][] = $rev;
        }
        $tpl['addon']['revision_list'] = $rev_list;

        // Image list
        $im_list = array(
            'label'             => _h('Images'),
            'upload'            => array(
                'display'      => false,
                'target'       => SITE_ROOT . 'upload.php?type=' . $this->addon->getType() . '&amp;name=' . $this->addon->getId(
                    ) . '&amp;action=file',
                'button_label' => _h('Upload Image')
            ),
            'images'            => array(),
            'no_images_message' => _h('No images have been uploaded for this addon yet.')
        );
        if ($this->addon->getUploaderId() === User::getLoggedId() || User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            $im_list['upload']['display'] = true;
        }

        // Get images
        $image_files_db = $this->addon->getImages();
        $image_files = array();
        foreach ($image_files_db AS $image)
        {
            $image['url'] = DOWNLOAD_LOCATION . $image['file_path'];
            $imageCache = Cache::getImage($image['id'], array('size' => 'medium'));
            $image['thumb']['url'] = $imageCache['url'];
            $admin_links = null;
            if (User::isLoggedIn())
            {
                if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
                {
                    if ($image['approved'] == 1)
                    {
                        $admin_links .= '<a href="' . File::rewrite(
                                $this->addon->getLink() . '&amp;save=unapprove&amp;id=' . $image['id']
                            ) . '">' . _h('Unapprove') . '</a>';
                    }
                    else
                    {
                        $admin_links .= '<a href="' . File::rewrite(
                                $this->addon->getLink() . '&amp;save=approve&amp;id=' . $image['id']
                            ) . '">' . _h('Approve') . '</a>';
                    }
                    $admin_links .= '<br />';
                }
                if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS) || $this->addon->getUploaderId() === User::getLoggedId())
                {
                    if ($this->addon->getType() == 'karts')
                    {
                        if ($this->addon->getImage(true) != $image['id'])
                        {
                            $admin_links .= '<a href="' . File::rewrite(
                                    $this->addon->getLink() . '&amp;save=seticon&amp;id=' . $image['id']
                                ) . '">' . _h('Set Icon') . '</a><br />';
                        }
                    }
                    if ($this->addon->getImage() != $image['id'])
                    {
                        $admin_links .= '<a href="' . File::rewrite(
                                $this->addon->getLink() . '&amp;save=setimage&amp;id=' . $image['id']
                            ) . '">' . _h('Set Image') . '</a><br />';
                    }
                    $admin_links .= '<a href="' . File::rewrite(
                            $this->addon->getLink() . '&amp;save=deletefile&amp;id=' . $image['id']
                        ) . '">' . _h('Delete File') . '</a><br />';
                }
            }
            $image['admin_links'] = $admin_links;
            if ($this->addon->getUploaderId() === User::getLoggedId() || User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
            {
                $image_files[] = $image;
                continue;
            }
            if ($image['approved'] == 1)
            {
                $image_files[] = $image;
            }
        }
        $im_list['images'] = $image_files;
        $tpl['addon']['image_list'] = $im_list;

        // Source files
        $s_list = array(
            'label'            => _h('Source Files'),
            'upload'           => array(
                'display'      => false,
                'target'       => SITE_ROOT . 'upload.php?type=' . $this->addon->getType() . '&amp;name=' . $this->addon->getId(
                    ) . '&amp;action=file',
                'button_label' => _h('Upload Source File')
            ),
            'files'            => array(),
            'no_files_message' => _h('No source files have been uploaded for this addon yet.')
        );
        if ($this->addon->getUploaderId() == User::getLoggedId() || User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            $s_list['upload']['display'] = true;
        }

        // Search database for source files
        $source_files_db = $this->addon->getSourceFiles();
        $source_files = array();
        foreach ($source_files_db AS $source)
        {
            $source['label'] = sprintf(_h('Source File %u'), count($source_files) + 1);
            $source['details'] = null;
            if ($source['approved'] == 0)
            {
                $source['details'] .= '(' . _h('Not Approved') . ') ';
            }
            $source['details'] .= '<a href="' . DOWNLOAD_LOCATION . $source['file_path'] . '" rel="nofollow">' . _(
                    'Download'
                ) . '</a>';
            if (User::isLoggedIn())
            {
                if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
                {
                    if ($source['approved'] == 1)
                    {
                        $source['details'] .= ' | <a href="' . File::rewrite(
                                $this->addon->getLink() . '&amp;save=unapprove&amp;id=' . $source['id']
                            ) . '">' . _h('Unapprove') . '</a>';
                    }
                    else
                    {
                        $source['details'] .= ' | <a href="' . File::rewrite(
                                $this->addon->getLink() . '&amp;save=approve&amp;id=' . $source['id']
                            ) . '">' . _h('Approve') . '</a>';
                    }
                }
                if ($this->addon->getUploaderId() === User::getLoggedId() || User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
                {
                    $source['details'] .= ' | <a href="' . File::rewrite(
                            $this->addon->getLink() . '&amp;save=deletefile&amp;id=' . $source['id']
                        ) . '">' . _h('Delete File') . '</a><br />';
                }
            }
            if ($this->addon->getUploaderId() === User::getLoggedId() || User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
            {
                $source_files[] = $source;
                continue;
            }
            if ($source['approved'] == 1)
            {
                $source_files[] = $source;
            }
        }
        $s_list['files'] = $source_files;
        $tpl['addon']['source_list'] = $s_list;
        $template->assign('addon', $tpl['addon']);
    }

    /**
     * Output HTML to display flag badges
     *
     * @param int $status The 'status' value to interperet
     *
     * @return string
     */
    private static function badges($status)
    {
        $string = '';
        if ($status & F_FEATURED)
        {
            $string .= '<span class="badge f_featured">' . _h('Featured') . '</span>';
        }
        if ($status & F_ALPHA)
        {
            $string .= '<span class="badge f_alpha">' . _h('Alpha') . '</span>';
        }
        if ($status & F_BETA)
        {
            $string .= '<span class="badge f_beta">' . _h('Beta') . '</span>';
        }
        if ($status & F_RC)
        {
            $string .= '<span class="badge f_rc">' . _h('Release-Candidate') . '</span>';
        }
        if ($status & F_DFSG)
        {
            $string .= '<span class="badge f_dfsg">' . _h('DFSG Compliant') . '</span>';
        }

        return $string;
    }

    /**
     * @return string
     * @throws AddonException
     */
    private function displayConfig()
    {
        ob_start();
        // Check permission
        if (User::isLoggedIn() == false)
        {
            throw new AddonException('You must be logged in to see this.');
        }
        if (!User::hasPermission(AccessControl::PERM_EDIT_ADDONS) && $this->addon->getUploaderId() !== User::getLoggedId()
        )
        {
            throw new AddonException(_h('You do not have the necessary privileges to perform this action.'));
        }

        echo '<br /><hr /><br /><h3>' . _h('Configuration') . '</h3>';
        echo '<form name="changeProps" action="' . File::rewrite(
                $this->addon->getLink() . '&amp;save=props'
            ) . '" method="POST" accept-charset="utf-8">';

        // Edit designer
        $designer = ($this->addon->getDesigner() == _h('Unknown')) ? null : $this->addon->getDesigner();
        echo '<label for="designer_field">' . _h('Designer:') . '</label><br />';
        echo '<input type="text" name="designer" id="designer_field" value="' . $designer . '" accept-charset="utf-8" /><br />';
        echo '<br />';

        // Edit description
        echo '<label for="desc_field">' . _h('Description:') . '</label> (' . sprintf(_h('Max %u characters'), '140') . ')<br />';
        echo '<textarea name="description" id="desc_field" rows="4" cols="60" onKeyUp="textLimit(document.getElementById(\'desc_field\'),140);"
            onKeyDown="textLimit(document.getElementById(\'desc_field\'),140);" accept-charset="utf-8">' . $this->addon->getDescription(
            ) . '</textarea><br />';

        // Submit
        echo '<input type="submit" value="' . _h('Save Properties') . '" />';
        echo '</form><br />';

        // Delete addon
        if ($this->addon->getUploaderId() === User::getLoggedId() || User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            echo '<input type="button" value="' . _h('Delete Addon') . '"onClick="confirm_delete(\'' . File::rewrite(
                    $this->addon->getLink() . '&amp;save=delete'
                ) . '\')" /><br /><br />';
        }

        // Mark whether or not an add-on has ever been included in STK
        if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            echo '<strong>' . _h('Included in Game Versions:') . '</strong><br />';
            echo '<form method="POST" action="' . File::rewrite($this->addon->getLink() . '&amp;save=include') . '">';
            echo _h('Start:') . ' <input type="text" name="incl_start" size="6" value="' . h(
                    $this->addon->getIncludeMin()
                ) . '" /><br />';
            echo _h('End:') . ' <input type="text" name="incl_end" size="6" value="' . h(
                    $this->addon->getIncludeMax()
                ) . '" /><br />';
            echo '<input type="submit" value="' . _h('Save') . '" /><br />';
            echo '</form><br />';
        }

        // Set status flags
        echo '<strong>' . _h('Status Flags:') . '</strong><br />';
        echo '<form method="POST" action="' . File::rewrite($this->addon->getLink() . '&amp;save=status') . '">';
        echo '<table id="addon_flags" class="info"><thead><tr><th></th>';
        if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            echo '<th>' . Util::getImageLabel(_h('Approved')) . '</th><th>' . Util::getImageLabel(
                    _h('Invisible')
                ) . '</th>';
        }
        echo '<th>' . Util::getImageLabel(_h('Alpha')) . '</th><th>' . Util::getImageLabel(_h('Beta')) . '</th>
            <th>' . Util::getImageLabel(_h('Release-Candidate')) . '</th><th>' . Util::getImageLabel(
                _h('Latest')
            ) . '</th>';
        if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            echo '<th>' . Util::getImageLabel(_h('DFSG Compliant')) . '</th>
                <th>' . Util::getImageLabel(_h('Featured')) . '</th>';
        }
        echo '<th>' . Util::getImageLabel(_h('Invalid Textures')) . '</th><th></th>';
        echo '</tr></thead>';

        $fields = array();
        $fields[] = 'latest';
        foreach ($this->addon->getAllRevisions() AS $rev_n => $revision)
        {
            // Row Header
            echo '<tr><td style="text-align: center;">';
            printf(_h('Rev %u:'), $rev_n);
            echo '</td>';

            if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
            {
                // F_APPROVED
                echo '<td>';
                if ($revision['status'] & F_APPROVED)
                {
                    echo '<input type="checkbox" name="approved-' . $rev_n . '" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="approved-' . $rev_n . '" />';
                }
                echo '</td>';
                $fields[] = 'approved-' . $rev_n;

                // F_INVISIBLE
                echo '<td>';
                if ($revision['status'] & F_INVISIBLE)
                {
                    echo '<input type="checkbox" name="invisible-' . $rev_n . '" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="invisible-' . $rev_n . '" />';
                }
                echo '</td>';
                $fields[] = 'invisible-' . $rev_n;
            }

            // F_ALPHA
            echo '<td>';
            if ($revision['status'] & F_ALPHA)
            {
                echo '<input type="checkbox" name="alpha-' . $rev_n . '" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="alpha-' . $rev_n . '" />';
            }
            echo '</td>';
            $fields[] = 'alpha-' . $rev_n;

            // F_BETA
            echo '<td>';
            if ($revision['status'] & F_BETA)
            {
                echo '<input type="checkbox" name="beta-' . $rev_n . '" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="beta-' . $rev_n . '" />';
            }
            echo '</td>';
            $fields[] = 'beta-' . $rev_n;

            // F_RC
            echo '<td>';
            if ($revision['status'] & F_RC)
            {
                echo '<input type="checkbox" name="rc-' . $rev_n . '" checked />';
            }
            else
            {
                echo '<input type="checkbox" name="rc-' . $rev_n . '" />';
            }
            echo '</td>';
            $fields[] = 'rc-' . $rev_n;

            // F_LATEST
            echo '<td>';
            if ($revision['status'] & F_LATEST)
            {
                echo '<input type="radio" name="latest" value="' . $rev_n . '" checked />';
            }
            else
            {
                echo '<input type="radio" name="latest" value="' . $rev_n . '" />';
            }
            echo '</td>';

            if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
            {
                // F_DFSG
                echo '<td>';
                if ($revision['status'] & F_DFSG)
                {
                    echo '<input type="checkbox" name="dfsg-' . $rev_n . '" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="dfsg-' . $rev_n . '" />';
                }
                echo '</td>';
                $fields[] = 'dfsg-' . $rev_n;

                // F_FEATURED
                echo '<td>';
                if ($revision['status'] & F_FEATURED)
                {
                    echo '<input type="checkbox" name="featured-' . $rev_n . '" checked />';
                }
                else
                {
                    echo '<input type="checkbox" name="featured-' . $rev_n . '" />';
                }
                echo '</td>';
                $fields[] = 'featured-' . $rev_n;
            }

            // F_TEX_NOT_POWER_OF_2
            echo '<td>';
            if ($revision['status'] & F_TEX_NOT_POWER_OF_2)
            {
                echo '<input type="checkbox" name="texpower-' . $rev_n . '" checked disabled />';
            }
            else
            {
                echo '<input type="checkbox" name="texpower-' . $rev_n . '" disabled />';
            }
            echo '</td>';

            // Delete revision button
            echo '<td>';
            echo '<input type="button" value="' . sprintf(
                    _h('Delete revision %d')
                ),
                $rev_n
                . '" onClick="confirm_delete(\'' . File::rewrite(
                    $this->addon->getLink() . '&amp;save=del_rev&amp;rev=' . $rev_n
                ) . '\');" />';
            echo '</td>';

            echo '</tr>';
        }
        echo '</table>';
        echo '<input type="hidden" name="fields" value="' . implode(',', $fields) . '" />';
        echo '<input type="submit" value="' . _h('Save Changes') . '" />';
        echo '</form><br />';

        // Moderator notes
        echo '<strong>' . _h('Notes from Moderator to Submitter:') . '</strong><br />';
        if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            echo '<form method="POST" action="' . File::rewrite($this->addon->getLink() . '&amp;save=notes') . '">';
        }

        $fields = array();
        foreach ($this->addon->getAllRevisions() AS $rev_n => $revision)
        {
            printf(_h('Rev %u:') . '<br />', $rev_n);
            echo '<textarea name="notes-' . $rev_n . '"
                id="notes-' . $rev_n . '" rows="4" cols="60"
                onKeyUp="textLimit(document.getElementById(\'notes-' . $rev_n . '\'),4000);"
                onKeyDown="textLimit(document.getElementById(\'notes-' . $rev_n . '\'),4000);">';
            echo $revision['moderator_note'];
            echo '</textarea><br />';
            $fields[] = 'notes-' . $rev_n;
        }

        if (User::hasPermission(AccessControl::PERM_EDIT_ADDONS))
        {
            echo '<input type="hidden" name="fields" value="' . implode(',', $fields) . '" />';
            echo '<input type="submit" value="' . _h('Save Notes') . '" />';
            echo '</form>';
        }

        return ob_get_clean();
    }

}
