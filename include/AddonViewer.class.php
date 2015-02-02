<?php
/**
 * Copyright      2010 Lucas Baudin <xapantu@gmail.com>
 *           2011-2014 Stephen Just <stephenjust@gmail.com>
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
     * @var Rating
     */
    private $rating = false;

    /**
     * @var bool
     */
    private $user_is_logged = false;

    /**
     * @var bool
     */
    private $user_is_owner = false;

    /**
     * @var bool
     */
    private $user_has_permission = false;

    /**
     * Constructor
     *
     * @param string $id Add-On ID
     *
     * @throws AddonException if the addon has no approved revision
     */
    public function __construct($id)
    {
        $this->addon = Addon::get($id);

        $this->user_is_logged = User::isLoggedIn();
        if ($this->user_is_logged)
        {
            $this->user_is_owner = ($this->addon->getUploaderId() === User::getLoggedId());
            $this->user_has_permission = User::hasPermission(AccessControl::PERM_EDIT_ADDONS);
        }

        // can view addon
        if (!$this->addon->hasApprovedRevision())
        {
            if (!$this->user_is_owner && !$this->user_has_permission)
            {
                throw new AddonException(_h("Addon is not approved"));
            }
        }

        $this->latestRev = $this->addon->getLatestRevision();
        $this->rating = new Rating($id);
    }

    /**
     * Fill template with addon info
     *
     * @param Template $template
     */
    public function fillTemplate($template)
    {
        // build template
        $tpl = [
            'id'             => $this->addon->getId(),
            'name'           => h($this->addon->getName()),
            'description'    => h($this->addon->getDescription()),
            'type'           => $this->addon->getType(),
            'is_arena'       => $this->addon->getType() === Addon::ARENA,
            'designer'       => h($this->addon->getDesigner()),
            'license'        => h($this->addon->getLicense()),
            'min'            => h($this->addon->getIncludeMin()),
            'max'            => h($this->addon->getIncludeMax()),
            'revisions'      => $this->addon->getAllRevisions(),
            'rating'         => [
                'label'      => $this->rating->getRatingString(),
                'percent'    => $this->rating->getAvgRatingPercent(),
                'decimal'    => $this->rating->getAvgRating(),
                'count'      => $this->rating->getNumRatings(),
                'min_rating' => Rating::MIN_RATING,
                'max_rating' => Rating::MAX_RATING
            ],
            'badges'         => AddonViewer::badges($this->addon->getStatus()),
            'image_url'      => false,
            'dl'             => [],
            'vote'           => false, // only logged users see this
            'view_revisions' => [],
            'images'         => [],
            'sources'        => []
        ];

        // build permission variables
        $can_edit = false;
        if ($this->user_is_logged) // not logged in, no reason to do checking
        {
            $can_edit = ($this->user_is_owner || $this->user_has_permission);

            $tpl['vote'] = $this->rating->displayUserRating();
        }

        // Get image url
        $image = Cache::getImage($this->addon->getImage(), SImage::SIZE_BIG);
        if ($this->addon->getImage() != 0 && $image['exists'] == true && $image['approved'] == true)
        {
            $tpl['image_url'] = $image['url'];
        }

        // build info table
        $addonUser = User::getFromID($this->addon->getUploaderId());
        $latestRev = $this->addon->getLatestRevision();
        $info = [
            'upload_date'   => $latestRev['timestamp'],
            'submitter'     => h($addonUser->getUserName()),
            'revision'      => $latestRev['revision'],
            'compatibility' => Util::getVersionFormat($latestRev['format'], $this->addon->getType()),
            'link'          => File::rewrite($this->addon->getLink())
        ];
        $tpl['info'] = $info;
        if (Addon::isTextureInvalid($latestRev['status']))
        {
            $template->assign(
                "warnings",
                'Warning: This addon may not display correctly on some systems. It uses textures that may not be compatible with all video cards.'
            );
        }

        // Download button, TODO use this in some way
        $file_path = $this->addon->getFile((int)$this->latestRev['revision']);
        if ($file_path !== false && File::existsDB($file_path))
        {
            $button_text = h(sprintf(_('Download %s'), $this->addon->getName()));
            $shrink = (mb_strlen($button_text) > 20) ? 'style="font-size: 1.1em !important;"' : null;
            $tpl['dl'] = [
                'label'  => $button_text,
                'url'    => DOWNLOAD_LOCATION . $file_path,
                'shrink' => $shrink,
            ];
        }

        // Revision list
        foreach ($tpl['revisions'] as $rev_n => $revision)
        {
            $status = $revision['status'];
            $is_approved = Addon::isApproved($status);

            // If the user is not the uploader, or moderators, then they cannot see unapproved addons
            if (!$can_edit && !$is_approved)
            {
                continue;
            }

            $rev = [
                'number'       => $rev_n,
                'timestamp'    => $revision['timestamp'],
                'file_path'    => DOWNLOAD_LOCATION . $this->addon->getFile($rev_n),
                'dl_label'     => h(sprintf(_('Download revision %u'), $rev_n)),
                'delete_link'  => File::rewrite($this->addon->getLink() . '&amp;save=del_rev&amp;rev=' . $rev_n),
                // status vars
                'is_approved'  => $is_approved,
                'is_invisible' => Addon::isInvisible($status),
                'is_dfsg'      => Addon::isDFSGCompliant($status),
                'is_featured'  => Addon::isFeatured($status),
                'is_alpha'     => Addon::isAlpha($status),
                'is_beta'      => Addon::isBeta($status),
                'is_rc'        => Addon::isReleaseCandidate($status),
                'is_latest'    => Addon::isLatest($status),
                'is_invalid'   => Addon::isTextureInvalid($status)
            ];

            // TODO see if file exists
            //            if (!File::exists($rev['file_path']))
            //            {
            //                continue;
            //            }

            $tpl['view_revisions'][] = $rev;
        }

        // Images
        $image_files_db = $this->addon->getImages();
        foreach ($image_files_db as $image)
        {
            $imageCache = Cache::getImage($image['id'], SImage::SIZE_MEDIUM);
            $image['thumb']['url'] = $imageCache['url'];
            $image['url'] = DOWNLOAD_LOCATION . $image['file_path'];
            $image['approved'] = (bool)$image['approved'];

            // do not compute anything
            if (!$can_edit && !$image['approved'])
            {
                continue;
            }

            $admin_links = null;
            if ($this->user_is_logged)
            {
                // only users that can edit addons
                if ($this->user_has_permission)
                {
                    if ($image['approved'])
                    {
                        $image["unapprove_link"] = File::rewrite($this->addon->getLink() . '&amp;save=unapprove&amp;id=' . $image['id']);
                    }
                    else
                    {
                        $image["approve_link"] = File::rewrite($this->addon->getLink() . '&amp;save=approve&amp;id=' . $image['id']);
                    }
                }

                // edit addons and the owner
                if ($can_edit)
                {
                    if ($this->addon->getType() == Addon::KART)
                    {
                        if ($this->addon->getImage(true) != $image['id'])
                        {
                            $image["icon_link"] = File::rewrite($this->addon->getLink() . '&amp;save=seticon&amp;id=' . $image['id']);
                        }
                    }
                    if ($this->addon->getImage() != $image['id'])
                    {
                        $image["image_link"] = File::rewrite($this->addon->getLink() . '&amp;save=setimage&amp;id=' . $image['id']);
                    }
                    $image["delete_link"] = File::rewrite($this->addon->getLink() . '&amp;save=deletefile&amp;id=' . $image['id']);
                }
            }

            $tpl['images'][] = $image;
        }

        // Search database for source files
        $source_files_db = $this->addon->getSourceFiles();
        $source_number = 0;
        foreach ($source_files_db as $source)
        {
            $source['label'] = sprintf(_h('Source File %u'), $source_number + 1);
            $source['approved'] = (bool)$source['approved'];

            // do not compute anything
            if (!$can_edit && !$source['approved'])
            {
                continue;
            }

            $source['download_link'] = DOWNLOAD_LOCATION . $source['file_path'];
            if ($this->user_is_logged)
            {
                if ($this->user_has_permission)
                {
                    if ($source['approved'])
                    {
                        $source['unapprove_link'] = File::rewrite($this->addon->getLink() . '&amp;save=unapprove&amp;id=' . $source['id']);
                    }
                    else
                    {
                        $source['approve_link'] = File::rewrite($this->addon->getLink() . '&amp;save=approve&amp;id=' . $source['id']);
                    }
                }

                if ($can_edit)
                {
                    $source['delete_link'] = File::rewrite($this->addon->getLink() . '&amp;save=deletefile&amp;id=' . $source['id']);
                }
            }

            $tpl['sources'][] = $source;
            $source_number++;
        }

        // configuration
        if ($can_edit)
        {
            $config = [
                "change_props_action" => File::rewrite($this->addon->getLink() . '&amp;save=props'),
                "delete_link"         => File::rewrite($this->addon->getLink() . '&amp;save=delete'),
                "include_action"      => File::rewrite($this->addon->getLink() . '&amp;save=include'),
                "moderator_action"    => File::rewrite($this->addon->getLink() . '&amp;save=notes'),
                "status"              => [
                    "action"      => File::rewrite($this->addon->getLink() . '&amp;save=status'),
                    "alpha_img"   => Util::getImageLabel(_h('Alpha')),
                    "beta_img"    => Util::getImageLabel(_h('Beta')),
                    "rc_img"      => Util::getImageLabel(_h('Release-Candidate')),
                    "latest_img"  => Util::getImageLabel(_h('Latest')),
                    "invalid_img" => Util::getImageLabel(_h('Invalid Textures'))
                ]
            ];

            if ($this->user_has_permission)
            {
                $config["status"]["approve_img"] = Util::getImageLabel(_h('Approved'));
                $config["status"]["invisible_img"] = Util::getImageLabel(_h('Invisible'));
                $config["status"]["dfsg_img"] = Util::getImageLabel(_h('DFSG Compliant'));
                $config["status"]["featured_img"] = Util::getImageLabel(_h('Featured'));
            }

            $tpl['config'] = $config;
        }

        $template->assign('addon', $tpl)
            ->assign("has_permission", $this->user_has_permission)
            ->assign("can_edit", $can_edit)
            ->assign("is_logged", $this->user_is_logged);
    }

    /**
     * Output HTML to display flag badges
     *
     * @param int $status The 'status' value to interpreted
     *
     * @return string
     */
    private static function badges($status)
    {
        $string = '';
        if (Addon::isFeatured($status))
        {
            $string .= '<span class="badge f_featured">' . _h('Featured') . '</span>';
        }
        if (Addon::isAlpha($status))
        {
            $string .= '<span class="badge f_alpha">' . _h('Alpha') . '</span>';
        }
        if (Addon::isBeta($status))
        {
            $string .= '<span class="badge f_beta">' . _h('Beta') . '</span>';
        }
        if (Addon::isReleaseCandidate($status))
        {
            $string .= '<span class="badge f_rc">' . _h('Release-Candidate') . '</span>';
        }
        if (Addon::isDFSGCompliant($status))
        {
            $string .= '<span class="badge f_dfsg">' . _h('DFSG Compliant') . '</span>';
        }

        return $string;
    }
}
