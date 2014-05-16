<?php
/**
 * Copyright 2012-2013 Stephen Just <stephenjust@users.sf.net>
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

define('ROOT', '/home/stkaddons/stkaddons-scripts/web/');
define('CRON', 1);
require(ROOT . 'config.php');
require_once(INCLUDE_DIR . 'Log.class.php');

log_email();

function log_email()
{
    $events = Log::getUnemailedEvents();
    if (count($events) === 0)
    {
        print "No new log messages to email.\n";

        return;
    }

    $table = '<table><thead><tr><th>Date</th><th>User</th><th>Description</th></tr></thead><tbody>';
    foreach ($events AS $event)
    {
        $table .= '<tr><td>' . $event['date'] . '</td><td>' . strip_tags($event['name']) . '</td><td>' . strip_tags(
                $event['message']
            ) . '</td></tr>';
    }
    $table .= '</tbody></table>';

    $content = 'The following events have occurred in the last 7 days:<br />' . $table;

    moderator_email('Weekly log update', $content);

    Log::setAllEventsMailed();

    print "Sent log message email.\n";
}
