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

class CacheTest extends PHPUnit_Framework_TestCase
{
    public function providerTestGetCachePrefix()
    {
        return [
            ['100--', Cache::getCachePrefix(999)],
            ['100--', Cache::getCachePrefix(-999)],
            ['300--', Cache::getCachePrefix(SImage::SIZE_LARGE)],
            ['75--', Cache::getCachePrefix(SImage::SIZE_MEDIUM)],
            ['25--', Cache::getCachePrefix(SImage::SIZE_SMALL)]
        ];
    }

    /**
     * @param string $expected
     * @param string $actual
     *
     * @dataProvider providerTestGetCachePrefix
     */
    public function testGetCachePrefix($expected, $actual)
    {
        $this->assertEquals($expected, $actual);
    }
}
