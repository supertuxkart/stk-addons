<?php
/**
 * copyright 2013 Stephen Just <stephenjust@users.sourceforge.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

if (!isset($_POST["action"]) || empty($_POST["action"]))
{
    exit_json_error("action param is not defined or is empty");
}

switch ($_POST["action"])
{

    default:
        exit_json_error(printf("action = %s is not recognized", h($_POST["action"])));
        break;
}
