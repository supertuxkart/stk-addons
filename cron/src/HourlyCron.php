<?php

final class HourlyCron
{
	public static function run(): void
	{
		try
		{
			ClientSession::cron(5 * 60 /* 5 minutes */, 3600 * 24 * 30 /* 1 month */);
			echo "SUCCESS \n";
		}
		catch (ClientSessionException $e)
		{
			echo "ERROR: \n" . $e->getMessage();
		}
	}
}
