<?php


class ExceptionsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @param $exception BaseException
     * @dataProvider providerAllExceptions
     */
    public function testThrowExceptionWithCode($exception)
    {
        foreach (ErrorType::toArray() as $key => $value)
        {
            try
            {
                throw new $exception('', $value);
            }
            catch (BaseException $e)
            {
                $this->assertEquals($e->getCode(), $value);
            }
        }

    }

    /**
     * @param $exception BaseException
     * @dataProvider providerAllExceptions
     */
    public function testThrowExceptionWithoutCode($exception)
    {
        try
        {
            throw new $exception;
        }
        catch (BaseException $e)
        {
            $this->assertEquals($e->getCode(), ErrorType::UNKNOWN);
        }
    }

    public function providerAllExceptions()
    {
        return [
            ['BaseException'],
            ['DBException'],
            ['BugException'],
            ['UserException'],
            ['VerificationException'],
            ['ValidateException'],
            ['AddonException'],
            ['FileException'],
            ['UploadException'],
            ['NewsException'],
            ['RatingsException'],
            ['TemplateException'],
            ['ServerException'],
            ['LogException'],
            ['CacheException'],
            ['StatisticException'],
            ['AchievementException'],
            ['FriendException'],
            ['AccessControlException'],
            ['SImageException'],
            ['SMailException'],
            ['ParserException'],
            ['XMLParserException'],
            ['ClientSessionException'],
            ['ClientSessionConnectException'],
            ['ClientSessionExpiredException'],
        ];
    }
}