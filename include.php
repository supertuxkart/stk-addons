<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
 *           2013 Glenn De Jonghe
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

require_once(ROOT.'config.php');
require_once(ROOT.'include/AccessControl.class.php');
require_once(ROOT.'include/sql.php'); //needs to be removed eventually
require_once(ROOT.'include/DBConnection.class.php');
require_once(ROOT.'include/Template.class.php');
require_once(ROOT.'include/Constants.php');
require_once(ROOT.'include/exceptions.php');
require_once(ROOT.'include/Log.class.php');
require_once(ROOT.'include/Cache.class.php');
require_once(ROOT.'include/ConfigManager.php');
require_once(ROOT.'include/Validate.class.php');
require_once(ROOT.'include/Verification.class.php');
require_once(ROOT.'include/File.class.php');
require_once(ROOT.'include/SImage.class.php');
require_once(ROOT.'include/SMail.class.php');
require_once(ROOT.'include/News.class.php');
require_once(ROOT.'include/PanelInterface.class.php');
require_once(ROOT.'include/Addon.class.php');
require_once(ROOT.'include/AddonViewer.class.php');
require_once(ROOT.'include/locale.php');
require_once(ROOT.'include/User.class.php');
require_once(ROOT.'include/Ratings.class.php');
require_once(ROOT.'include/coreUser.php');
require_once(ROOT.'include/image.php');
require_once(ROOT.'include/statistics.php');
require_once(ROOT.'include/xmlWrite.php');
?>