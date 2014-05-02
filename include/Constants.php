<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
 *
 * This file is part of stkaddons
 *
 * stkaddons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stkaddons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stkaddons.  If not, see <http://www.gnu.org/licenses/>.
 */

// Add-On Flags
//
// Do not change existing flags! Doing so will cause errors with existing
// add-ons, and possible game incompatibility. To add new flags, create a new
// constant, and set it to the next power of 2. The current database schema
// allows 24 flags.
define('F_APPROVED', 1);
define('F_ALPHA', 2);
define('F_BETA', 4);
define('F_RC', 8);
define('F_INVISIBLE', 16);
define('F_RESERVED2', 32); // Reserved for future use
define('F_DFSG', 64);
define('F_FEATURED', 128);
define('F_LATEST', 256);
define('F_TEX_NOT_POWER_OF_2', 512);
