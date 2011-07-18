<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
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
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: index.php
Version: 1
Licence: GPLv3
Description: Config page for several permission

***************************************************************************/

// This file is included by include/user.php
switch($result['role'])
{
    case "basicUser":
        $_SESSION['role'] = array(
            "basicPage" => true,
            "addAddon" => true,
            "manageaddons" => false,
            "managebasicUsers" => false,
            "managemoderators" => false,
            "manageadministrators" => false,
            "managesupAdministrators" => false,
            "manageroots" => false,
            "managesettings" => false
        );
        break;
    case "moderator":
        $_SESSION['role'] = array(
            "basicPage" => true,
            "addAddon" => true,
            "manageaddons" => true,
            "managebasicUsers" => true,
            "managemoderators" => false,
            "manageadministrators" => false,
            "managesupAdministrators" => false,
            "manageroots" => false,
            "managesettings" => false
        );
        break;
    case "administrator":
        $_SESSION['role'] = array(
            "basicPage" => true,
            "addAddon" => true,
            "manageaddons" => true,
            "managebasicUsers" => true,
            "managemoderators" => true,
            "manageadministrators" => false,
            "managesupAdministrators" => false,
            "manageroots" => false,
            "managesettings" => true
        );
        break;
    case "supAdministrator":
        $_SESSION['role'] = array(
            "basicPage" => true,
            "addAddon" => true,
            "manageaddons" => true,
            "managebasicUsers" => true,
            "managemoderators" => true,
            "manageadministrators" => true,
            "managesupAdministrators" => false,
            "manageroots" => false,
            "managesettings" => true
        );
        break;
    case "root":
        $_SESSION['role'] = array(
            "basicPage" => true,
            "addAddon" => true,
            "manageaddons" => true,
            "managebasicUsers" => true,
            "managemoderators" => true,
            "manageadministrators" => true,
            "managesupAdministrators" => true,
            "manageroots" => true,
            "managesettings" => true
        );
        break;
}
//support for translations :
htmlspecialchars(_("root"));
htmlspecialchars(_("supAdministrator"));
htmlspecialchars(_("administrator"));
htmlspecialchars(_("moderator"));
htmlspecialchars(_("basicUser"));
?>
