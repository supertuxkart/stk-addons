<?php
/**
 * copyright 2011-2013 Stephen Just <stephenjust@users.sf.net>
 *           2013      Glenn De Jonghe
 *           2014      Daniel Butum <danibutum at gmail dot com>
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

require_once(ROOT . 'config.php');
require_once(INCLUDE_PATH . 'AccessControl.class.php');
require_once(INCLUDE_PATH . 'DBConnection.class.php');
require_once(INCLUDE_PATH . 'Constants.php');
require_once(INCLUDE_PATH . 'Exceptions.class.php');
require_once(INCLUDE_PATH . 'Log.class.php');
require_once(INCLUDE_PATH . 'Cache.class.php');
require_once(INCLUDE_PATH . 'ConfigManager.php');
require_once(INCLUDE_PATH . 'Validate.class.php');
require_once(INCLUDE_PATH . 'Verification.class.php');
require_once(INCLUDE_PATH . 'File.class.php');
require_once(INCLUDE_PATH . 'SImage.class.php');
require_once(INCLUDE_PATH . 'SMail.class.php');
require_once(INCLUDE_PATH . 'PanelInterface.class.php');
require_once(INCLUDE_PATH . 'AddonViewer.class.php');
require_once(INCLUDE_PATH . 'User.class.php');
require_once(INCLUDE_PATH . 'locale.php');
require_once(INCLUDE_PATH . 'Ratings.class.php');
require_once(INCLUDE_PATH . 'xmlWrite.php');
