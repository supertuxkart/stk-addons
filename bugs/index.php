<?php
/**
 * Copyright 2014 Daniel Butum <danibutum at gmail dot com>
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

define('ROOT', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR);
var_dump(ROOT);
require_once(ROOT . 'config.php');

$tpl = new StkTemplate('bugs.tpl', ROOT . "tpl" . DS . "");
//$tpl->assign('title', htmlspecialchars(_('STK Add-ons') . ' | ' . _('About')));

echo $tpl;
