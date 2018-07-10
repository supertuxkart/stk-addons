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

class UtilTest extends \PHPUnit\Framework\TestCase
{
    public function providerTestAreFloatsNearlyEqual()
    {
        return [
            [0.0, 0.0, true],
            [999.65455, 999.65455, true],
            [0.1234567, 0.123459, false]
        ];
    }

    /**
     * @param float $a
     * @param float $b
     * @param bool $are_equal
     *
     * @dataProvider providerTestAreFloatsNearlyEqual
     */
    public function testAreFloatsNearlyEqual($a, $b, $are_equal)
    {
        $this->assertEquals(Util::areFloatsNearlyEqual($a, $b), $are_equal);
    }

    public function testIsFloatNearlyEqual()
    {
        $this->assertTrue(Util::isFloatNearlyZero(0.0000001));
        $this->assertFalse(Util::isFloatNearlyZero(0.00001));
        $this->assertTrue(Util::isFloatNearlyZero(0.0));
        $this->assertTrue(Util::isFloatNearlyZero(-0.0));
        $this->assertTrue(Util::isFloatNearlyZero(-0.0000001));
    }


    public function providerTestIsCoordinates()
    {
        return [
            [-90.0, -180.0, true],
            [-90.0, 180.0, true],
            [90.0, -180.0, true],
            [90.0, 180.0, true],
            [0.0, 0.0, true],
            [45.5457, 45.5785, true],
            [-91.0, -180.0, false],
            [-90.0, -181.0, false],
            [-91.0, 180.0, false],
            [-90.0, 181.0, false],
            [91.0, -181.0, false],
        ];
    }

    /**
     * @param float $lat
     * @param float $lon
     * @param bool $are
     *
     * @dataProvider providerTestIsCoordinates
     */
    public function testIsCoordinates($lat, $lon, $are)
    {
        $this->assertEquals(Util::isCoordinates($lat, $lon), $are);
    }

    public function providerTestIsNullIslandCoordinates()
    {
        return [
            [0.0000001, 0.0000001, true],
            [0.0, 0.0, true],
            [-0.0, -0.0, true],
            [-91.0, -180.0, false],
            [-90.0, -181.0, false],
            [-91.0, 180.0, false],
            [-90.0, 181.0, false],
            [91.0, -181.0, false],
        ];
    }

    /**
     * @param float $lat
     * @param float $lon
     * @param bool $is_island
     *
     * @dataProvider providerTestIsNullIslandCoordinates
     */
    public function testIsNullIslandCoordinates($lat, $lon, $is_island)
    {
        $this->assertEquals(Util::isNullIslandCoordinates($lat, $lon), $is_island);
    }

    public function providerTestGetDistance()
    {
        return [
            [[0.0, 0.0], [45.0, 45.0], -1],
            [[45.0, 45.0], [0.0, 0.0], -1],
            [[-91.0, 45.0], [0.0, 0.0], -1],
            [[45.0, 45.0], [null, null], -1],
            [[45.0, 45.0], [-45.0, -45.0], 13343],
            [[-90, -180.0], [90.0, 180.0], 20015],
        ];
    }

    /**
     * @param array $from
     * @param array $to
     * @param int  $expected_distance_km
     *
     * @dataProvider providerTestGetDistance
     */
    public function testGetDistance($from, $to, $expected_distance_km)
    {
        $this->assertEquals((int)Util::getDistance($from[0], $from[1], $to[0], $to[1]), $expected_distance_km);
    }
}