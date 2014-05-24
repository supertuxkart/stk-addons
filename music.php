<?php
/**
 * copyright 2013 Stephen Just <stephenjust@users.sourceforge.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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

$tpl = new StkTemplate('music-browser.tpl');
$tpl->assign('title', htmlspecialchars(_('STK Add-ons') . ' | ' . _('Browse Music')));

$music_tracks = Music::getAllByTitle();
$music_data = array();
foreach ($music_tracks as $track)
{
    $music_data[] = $track->getTitle();
    $music_data[] = $track->getArtist();
    $music_data[] = $track->getLicense();
    $music_data[] = '<a href="' . DOWN_LOCATION . 'music/' . $track->getFile() . '">' . $track->getFile() . '</a>';
}

$tpl->assign(
    'music_browser',
    array(
        'cols' => array(
            _h('Track Title'),
            _h('Track Artist'),
            _h('License'),
            _h('File')
        ),
        'data' => $music_data
    )
);

echo $tpl;
