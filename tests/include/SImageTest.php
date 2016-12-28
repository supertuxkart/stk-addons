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

class SImageTest extends PHPUnit_Framework_TestCase
{
    public function providerTestSizeToInt()
    {
        return [
            [100, SImage::sizeToInt(SImage::SIZE_DEFAULT)],
            [100, SImage::sizeToInt('9999')],
            [100, SImage::sizeToInt(514949489)],
            [25, SImage::sizeToInt(SImage::SIZE_SMALL)],
            [75, SImage::sizeToInt(SImage::SIZE_MEDIUM)],
            [300, SImage::sizeToInt(SImage::SIZE_LARGE)],

        ];
    }

    /**
     * @param int $expected
     * @param int $actual
     *
     * @dataProvider providerTestSizeToInt
     */
    public function testSizeToInt($expected, $actual)
    {
        $this->assertEquals($expected, $actual);
    }

    public function providerTestIntToSize()
    {
        return [
            [SImage::SIZE_DEFAULT, SImage::intToSize('999999')],
            [SImage::SIZE_DEFAULT, SImage::intToSize(5454488)],
            [SImage::SIZE_SMALL, SImage::intToSize(25)],
            [SImage::SIZE_MEDIUM, SImage::intToSize(75)],
            [SImage::SIZE_LARGE, SImage::intToSize(300)]
        ];
    }

    /**
     * @param int $expected
     * @param int $actual
     *
     * @dataProvider providerTestIntToSize
     */
    public function testIntToSize($expected, $actual)
    {
        $this->assertEquals($expected, $actual);
    }
}
