<?php
/**
 * Copyright 2009      Lucas Baudin <xapantu@gmail.com>
 *           2011-2014 Stephen Just <stephenjust@gmail.com>
 *           2014      Daniel Butum <danibutum at gmail dot com>
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
require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");

$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : null;
$has_permission = User::hasPermission(AccessControl::PERM_EDIT_ADDONS);
$template_addons = [];
foreach (Addon::getAll($_GET['type'], true) as $addon)
{
    try
    {
        // Get link icon
        if ($addon->getType() === Addon::KART)
        {
            // Make sure an icon file is set for kart
            if ($addon->getImage(true) != 0)
            {
                $im = Cache::getImage($addon->getImage(true), ['size' => 'small']);
                if ($im['exists'] && $im['approved'])
                {
                    $icon = $im['url'];
                }
                else
                {
                    $icon = IMG_LOCATION . 'kart-icon.png';
                }
            }
            else
            {
                $icon = IMG_LOCATION . 'kart-icon.png';
            }
        }
        else
        {
            $icon = IMG_LOCATION . 'track-icon.png';
        }

        // Approved?
        if ($addon->hasApprovedRevision())
        {
            $class = '';
        }
        elseif ($has_permission || User::getLoggedId() == $addon->getUploaderId())
        {
            // not approved, see of we are logged in and we have permission
            $class = ' unavailable';
        }
        else
        {
            // do not show
            continue;
        }

        $real_url = sprintf("addons.php?type=%s&amp;name=%s", $_GET['type'], $addon->getId());
        $template_addons[] = [
            "class"       => $class,
            "is_featured" => Addon::isFeatured($addon->getStatus()),
            "name"        => $addon->getName(),
            "real_url"    => $real_url,
            "image_src"   => $icon,
            "disp"        => File::rewrite($real_url)
        ];
    }
    catch(AddonException $e)
    {
        exit($e->getMessage());
    }
}

$tpl = StkTemplate::get("addons-menu.tpl")->assign("addons", $template_addons);

echo $tpl;
