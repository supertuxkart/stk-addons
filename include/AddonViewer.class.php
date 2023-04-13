<?php
/**
 * Copyright 2010      Lucas Baudin <xapantu@gmail.com>
 *           2011-2014 Stephen Just <stephenjust@gmail.com>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
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
     * @var array
     */
    private $latestRev;

    /**
     * @var Rating
     */
    private $rating;

    /**
     * @var bool
     */
    private $user_is_logged = false;

    /**
     * @var bool
     */
    private $user_is_owner = false;

    /**
     * Indicates if the user is a privileged user can edit the addon
     * @var bool
     */
    private $user_has_permission = false;


    /**
     * Indicates if the user is the owner of the addon  or has edit permission
     * @var bool
     */
    private $can_edit = false;

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
            $this->can_edit = ($this->user_is_owner || $this->user_has_permission);
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
        $this->rating = Rating::get($id);
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
            'type'           => Addon::typeToString($this->addon->getType()),
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

        if ($this->user_is_logged) // not logged in, no reason to do checking
        {
            $tpl['vote'] = $this->rating->displayUserRating(User::getLoggedId());
        }

        // Get image url
        $image = Cache::getImage($this->addon->getImage(), StkImage::SIZE_LARGE);
        if ($this->addon->getImage() !== Addon::NO_IMAGE && $image['exists'] == true && $image['is_approved'] == true)
        {
            $tpl['image_url'] = $image['url'];
        }

        // build info table
        $submiter = "DELETED user";
        $is_deleted_user = true;
        if ($this->addon->hasUploader())
        {
            $addonUser = User::getFromID($this->addon->getUploaderId());
            $submiter = $addonUser->getUserName();
            $is_deleted_user = false;
        }

        $latestRev = $this->addon->getLatestRevision();
        $info = [
            'upload_date'     => $latestRev['timestamp'],
            'submitter'       => h($submiter),
            'is_deleted_user' => $is_deleted_user,
            'revision'        => $latestRev['revision'],
            'compatibility'   => Util::getVersionFormat((int)$latestRev['format'], $this->addon->getType()),
            'link'            => URL::rewriteFromConfig($this->addon->getLink())
        ];
        $tpl['info'] = $info;

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
            if (!$this->can_edit && !$is_approved)
            {
                continue;
            }

            $rev = [
                'number'       => $rev_n,
                'timestamp'    => $revision['timestamp'],
                'file_path'    => DOWNLOAD_LOCATION . $this->addon->getFile($rev_n),
                'dl_label'     => h(sprintf(_('Download revision %u'), $rev_n)),
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
        foreach ($this->addon->getImages() as $image)
        {
            // do not display
            if (!$this->can_edit && !$image->isApproved())
            {
                continue;
            }

            $image_cache = Cache::getImage($image->getId(), StkImage::SIZE_MEDIUM);
            $image_tpl = [
                "has_icon"    => false,
                "has_image"   => false,
                "thumb_url"   => $image_cache['url'],
                "url"         => DOWNLOAD_LOCATION . $image->getPath(),
                "is_approved" => $image->isApproved(),
                "id"          => $image->getId(),
            ];

            // edit addons and the owner
            if ($this->can_edit)
            {
                // has_icon, current icon is not already the icon
                if ($this->addon->getType() === Addon::KART && $this->addon->getIcon() !== $image->getId())
                {
                    $image_tpl["has_icon"] = true;
                }

                // current image is not already the image
                if ($this->addon->getImage() !== $image->getId())
                {
                    $image_tpl["has_image"] = true;
                }
            }

            $tpl['images'][] = $image_tpl;
        }

        // Search database for source files
        $source_number = 0;
        foreach ($this->addon->getSourceFiles() as $source)
        {
            // do not display
            if (!$this->can_edit && !$source->isApproved())
            {
                continue;
            }

            $tpl['sources'][] = [
                "id"          => $source->getId(),
                "is_approved" => $source->isApproved(),
                "label"       => sprintf(_h('Source File %u'), $source_number + 1),
                "url"         => DOWNLOAD_LOCATION . $source->getPath()
            ];
            $source_number++;
        }

        // configuration
        if ($this->can_edit)
        {
            $config = [
                "status" => [
                    "alpha_img"   => StkImage::getImageLabel(_h('Alpha')),
                    "beta_img"    => StkImage::getImageLabel(_h('Beta')),
                    "rc_img"      => StkImage::getImageLabel(_h('Release-Candidate')),
                    "latest_img"  => StkImage::getImageLabel(_h('Latest'))
//                    "invalid_img" => StkImage::getImageLabel(_h('Invalid Textures'))
                ]
            ];

            if ($this->user_has_permission)
            {
                $config["status"]["approve_img"] = StkImage::getImageLabel(_h('Approved'));
                $config["status"]["invisible_img"] = StkImage::getImageLabel(_h('Invisible'));
                $config["status"]["dfsg_img"] = StkImage::getImageLabel(_h('DFSG Compliant'));
                $config["status"]["featured_img"] = StkImage::getImageLabel(_h('Featured'));
            }

            $tpl['config'] = $config;
        }

        $template->assign('addon', $tpl)
            ->assign("has_permission", $this->user_has_permission)
            ->assign("can_edit", $this->can_edit)
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
        if (!Addon::isApproved($status))
        {
            $string .= '<span class="badge f_pending">' . _h('Pending Approval') . '</span>';
        }
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
