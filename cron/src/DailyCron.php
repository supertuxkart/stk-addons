<?php

final class DailyCron
{
    public static function run(): void
    {
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
            Verification::cron(CRON_DAILY_VERIFICATION_DAYS);
            echo "SUCCESS: Verification::cron \n";
        }
        catch (VerificationException $e)
        {
            echo "ERROR: Verification::cron \n" . $e->getMessage();
        }

        try
        {
            Ranking::cron();
            echo "SUCCESS: Ranking::cron \n";
        }
        catch (RankingException $e)
        {
            echo "ERROR: RankingException::cron \n" . $e->getMessage();
        }

    }
}
