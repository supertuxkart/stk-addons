<?php
/**
 * Copyright 2012-2013 Stephen Just <stephenjust@users.sf.net>
 *           2015      Daniel Butum <danibutum at gmail dot com>
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
define('CRON_MODE', true);
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

echo "Executed at: " . date('d/m/Y H:i:s', time()) . "\n";
try
{
    echo File::deleteQueuedFiles() . "\n";
    writeXML();
    echo "SUCCESS: File::deleteQueuedFiles \n";
}
catch (FileException $e)
{
    echo "ERROR: File::deleteQueuedFiles \n" . $e->getMessage();
}

try
{
    Verification::cron(7);
    echo "SUCCESS: Verification::cron \n";
}
catch (VerificationException $e)
{
    echo "ERROR: Verification::cron \n" . $e->getMessage();
}
