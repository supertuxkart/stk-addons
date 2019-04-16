<?php
/**
 * copyright 2011         Stephen Just <stephenjust@users.sf.net>
 *           2015 - 2016  Daniel Butum <danibutum at gmail dot com>
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
declare(strict_types=1);

// Useful CLI interface for the addons
if (php_sapi_name() !== "cli")
    exit("Not in CLI Mode");

require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

// Prevent against disaster!!!
error_reporting(E_ALL);
ini_set('display_errors', "On");
ini_set('html_errors', "Off");


$xml = writeNewsXML();
echo 'News xml written: ' . $xml . ' bytes' . PHP_EOL;

$xml = writeAssetXML();
echo 'Asset xml written: ' . $xml . ' bytes' . PHP_EOL;
