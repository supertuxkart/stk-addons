<?php
/**
 * Copyright 2015 Daniel Butum <danibutum at gmail dot com>
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
define('CRON_MODE', true);
require_once(dirname(__DIR__) . DIRECTORY_SEPARATOR . "config.php");

echo "Executed at: " . date('d/m/Y H:i:s', time()) . "\n";
try
{
    ClientSession::cron(5*60 /* 5 minutes */, 3600*24*30 /* 1 month */);
    echo "SUCCESS \n";
}
catch (ClientSessionException $e)
{
    echo "ERROR: \n" . $e->getMessage();
}
