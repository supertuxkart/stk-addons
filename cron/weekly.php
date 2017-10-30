<?php
/**
 * Copyright 2012-2013 Stephen Just <stephenjust@users.sf.net>
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

log_email();

echo "Executed at: " . date('d/m/Y H:i:s', time()) . "\n";
function log_email()
{
    $events = StkLog::getUnemailedEvents();
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

    try
    {
        StkMail::get()->moderatorNotification('Weekly log update', $content);
    }
    catch (SMailException $e)
    {
        StkLog::newEvent($e->getMessage(), LogLevel::ERROR);
        exit;
    }

    StkLog::setAllEventsMailed();

    print "Sent log message email.\n";
}
