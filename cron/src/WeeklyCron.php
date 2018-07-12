<?php

final class WeeklyCron
{
    public static function run(): void
    {
        $events = StkLog::getUnemailedEvents();

        if (count($events) === 0)
        {
            echo "No new log messages to email.\n";

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
        catch (StkMailException $e)
        {
            StkLog::newEvent($e->getMessage(), LogLevel::ERROR);
            exit;
        }

        StkLog::setAllEventsMailed();

        echo "Sent log message email.\n";
    }
}
