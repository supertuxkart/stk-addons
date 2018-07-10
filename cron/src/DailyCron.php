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
			Verification::cron(7);
			echo "SUCCESS: Verification::cron \n";
		}
		catch (VerificationException $e)
		{
			echo "ERROR: Verification::cron \n" . $e->getMessage();
		}
	}
}
