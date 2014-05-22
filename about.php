<?php
/**
 * Copyright        2009 Lucas Baudin <xapantu@gmail.com>
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

require_once(__DIR__ . DIRECTORY_SEPARATOR . "config.php");

$tpl = new StkTemplate('about.tpl');
$tpl->assign('title', htmlspecialchars(_('STK Add-ons') . ' | ' . _('About')));
$tpl->assign('about', array('credits' => array('content' => file_get_contents("CREDITS"))));

echo $tpl;
