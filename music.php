<?php
/**
 * copyright 2013 Stephen Just <stephenjust@users.sourceforge.net>
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

define('ROOT', './');
require(ROOT . 'config.php');
require_once(INCLUDE_DIR . 'Music.class.php');
require_once(INCLUDE_DIR . 'User.class.php');
require_once(INCLUDE_DIR . 'StkTemplate.class.php');

$tpl = new StkTemplate('music-browser.tpl');
$tpl->assign('title', htmlspecialchars(_('STK Add-ons') . ' | ' . _('Browse Music')));

$music_tracks = Music::getAllByTitle();
$music_data = array();
foreach ($music_tracks as $track)
{
    $music_data[] = $track->getTitle();
    $music_data[] = $track->getArtist();
    $music_data[] = $track->getLicense();
    $link = '<a href="' . DOWN_LOCATION . 'music/' . $track->getFile() . '">' . $track->getFile() . '</a>';
    $music_data[] = $link;
}

$tpl->assign(
    'music_browser',
    array(
        'cols'    => array(
            htmlspecialchars(_('Track Title')),
            htmlspecialchars(_('Track Artist')),
            htmlspecialchars(_('License')),
            htmlspecialchars(_('File'))
        ),
        'data'    => $music_data
    )
);

echo $tpl;
