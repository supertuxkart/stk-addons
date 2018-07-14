<?php

/**
 * copyright 2015 - 2016 Daniel Butum <danibutum at gmail dot com>
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
class ExceptionsTest extends \PHPUnit\Framework\TestCase
{
    public function testUniqueConstantValues()
    {
        $array = ErrorType::toArray();
        $map = [];
        foreach ($array as $key => $value)
        {
            // already exists, NOT unique!
            if (isset($map[$value]))
            {
                $this->fail(
                    sprintf("Enum ErrorType has a duplicate key value, %s is the same as %s", $key, $map[$value])
                );
            }
            else
            {
                $map[$value] = $key;
            }
        }
    }

    /**
     * @param BaseException $exception
     *
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
     * @param BaseException $exception
     *
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
            ['StkImageException'],
            ['StkMailException'],
            ['ParserException'],
            ['XMLParserException'],
            ['ClientSessionException'],
            ['ClientSessionConnectException'],
            ['ClientSessionExpiredException'],
        ];
    }
}
