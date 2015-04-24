<?php
/**
 * copyright 2009      Lucas Baudin <xapantu@gmail.com>
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
require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");

$description = _h('This is the official SuperTuxKart add-on repository. It contains extra karts and tracks for the SuperTuxKart game.');
$tpl = StkTemplate::get('index.tpl')
    ->assign('title', _h('SuperTuxKart Add-ons'))
    ->assign("show_stk_image", true)
    ->setMetaDesc($description)
    ->addScriptInclude("jquery.newsticker.js")
    ->addScriptInline('$("#news-messages").newsTicker();', StkTemplate::ORDER_AFTER);

// Display index menu
$type_kart = Addon::typeToString(Addon::KART);
$type_track = Addon::typeToString(Addon::TRACK);
$type_arena = Addon::typeToString(Addon::ARENA);
$tpl->assign(
    'index_menu',
    [
        [
            'href'  => File::rewrite('addons.php?type=' . $type_kart),
            'label' => _h('Karts'),
            'type'  => $type_kart
        ],
        [
            'href'  => File::rewrite('addons.php?type=' . $type_track),
            'label' => _h('Tracks'),
            'type'  => $type_track
        ],
        [
            'href'  => File::rewrite('addons.php?type=' . $type_arena),
            'label' => _h('Arenas'),
            'type'  => $type_arena
        ],
        [
            'href'  => 'https://github.com/supertuxkart/stk-addons/wiki',
            'label' => 'Help',
            'type'  => 'help'
        ]
    ]
);

// Display news messages
$news_messages = News::getWebVisible();

// Note most downloaded track and kart
$pop_kart = Statistic::mostDownloadedAddon(Addon::KART);
$pop_track = Statistic::mostDownloadedAddon(Addon::TRACK);
array_unshift(
    $news_messages,
    sprintf(_h('The most downloaded kart is %s.'), $pop_kart),
    sprintf(_h('The most downloaded track is %s.'), $pop_track)
);

$tpl->assign('news_messages', $news_messages);
echo $tpl;
