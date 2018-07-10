#!/usr/bin/php -q
<?php
/**
 * Copyright 2017 Daniel Butum <danibutum at gmail dot com>
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
declare(strict_types=1);

// Useful CLI interface for the addons
if (php_sapi_name() !== "cli")
    exit("Not in CLI Mode");

require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

// Prevent against disaster!!!
error_reporting(E_ALL);
function exception_error_handler($severity, $message, $file, $line)
{
    throw new ErrorException($message, 0, $severity, $file, $line);
}
set_error_handler("exception_error_handler");
assert_options(ASSERT_ACTIVE, true);
assert_options(ASSERT_BAIL, true);

function print_string(string $str)
{
    echo $str . PHP_EOL;
}

function exit_string(string $str)
{
    exit($str . PHP_EOL);
}

class Commands
{
    const NOT_FOUND = -1;
    const CACHE_CLEAR = 1;
    const CACHE_CLEAR_FS = 2;
    const CACHE_CLEAR_DB = 3;
    const CHECK_ADDONS = 4;
    const LIST_TEXTURES = 5;

    public static function getCommandFromStr(string $str_command) : int
    {
        $str_command = strtolower(trim($str_command));
        $map_str_commands = [
            'cache-clear' => static::CACHE_CLEAR,
            'cache-clear-fs' => static::CACHE_CLEAR_FS,
            'cache-clear-db' => static::CACHE_CLEAR_DB,
            'check-addons' => static::CHECK_ADDONS,
            'list-textures' => static::LIST_TEXTURES
        ];


        if (array_key_exists($str_command, $map_str_commands))
        {
            return $map_str_commands[$str_command];
        }

        return static::NOT_FOUND;
    }

    public static function isCommand(string $str_command) : bool
    {
        return static::getCommandFromStr($str_command) != Commands::NOT_FOUND;
    }
}

# validate data
if ($argc < 2)
    exit_string(sprintf("Usage: php %s <command>", $argv[0]));

$command = $argv[1];
if (!Commands::isCommand($command))
    exit_string(sprintf("Command '%s' does not exist", $command));

$command = Commands::getCommandFromStr($command);
if ($command === Commands::CACHE_CLEAR_FS)
{
    print_string("Clearing cache FileSystem");
    Cache::clearFS();
}
else if ($command === Commands::CACHE_CLEAR_DB)
{
    print_string("Clearing cache Database");
    Cache::clearDB();
}
else if ($command === Commands::CACHE_CLEAR)
{
    print_string("Clearing cache");
    Cache::clear();
}
else if ($command === Commands::CHECK_ADDONS)
{
    exit("NOT IMPLEMENTED");
}
else if ($command === Commands::LIST_TEXTURES)
{
    if ($argc < 3)
        exit_string("what file?");

    print_string("Printing textures of file");
    $file = $argv[2];
    $textures = [];
    if (preg_match('/\.b3d$/i', $file))
    {
        $b3d_parse = new B3DParser();
//        $this->wtf;
        $b3d_parse->loadFile($file);
        $textures = $b3d_parse->listTextures();
    }
    // Parse any SPM models
    else if (preg_match('/\.spm$/i', $file))
    {
        $spm_parse = new SPMParser();
        $spm_parse->loadFile($file);
        $textures = $spm_parse->listTextures();
    }
    else
    {
        exit_string(sprintf("Invalid file extension for file = %s", $file));
    }

    print_string("Textures:");
    print_r($textures);
}
else
{
    exit_string("Something broke");
}
