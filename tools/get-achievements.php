#!/usr/bin/php -q
<?php
/**
 * Copyright 2015 - 2016  Daniel Butum <danibutum at gmail dot com>
 *
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
// Script that takes an XML file with all the achievements and generates a SQL insert command
if (php_sapi_name() !== "cli") exit("Not in CLI Mode");

# validate data
if ($argc < 2) exit(sprintf("Usage: php %s <achievements.xml>" . PHP_EOL, $argv[0]));
$filename = $argv[1];
if (!file_exists($filename)) exit(sprintf("File '%s' does not exist" . PHP_EOL, $filename));

$dom = new DOMDocument();
$dom->load($filename);
$achievements = $dom->getElementsByTagName("achievement");

echo "INSERT INTO `v3_achievements` (`id`, `name`) VALUES" . PHP_EOL;
foreach ($achievements as $i => $achievement)
{
    /** @var DOMElement $achievement */
    echo sprintf(
        "    (%s, '%s')%s" . PHP_EOL,
        $achievement->getAttribute("id"),
        str_replace("'", "''", $achievement->getAttribute("name")),
        $i == ($achievements->length - 1) ? ';' : ','
    );
}
