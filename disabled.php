<?php
/**
 * copyright 2011 Stephen Just <stephenjust@gmail.com>
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

File: security.php
Version: 1
Licence: GPLv3
Description: security

***************************************************************************/

include(ROOT.'include/top.php');
?>
        <meta http-equiv="refresh" content="3;URL=index.php" />
    </head>
    <body>
        <?php include('include/menu.php'); ?>
        <div id="content">
            <span class="error">
                <?php echo _('This page is currently disabled.'); ?><br />
                <?php echo _('You will be redirected to the home page.'); ?>
            </span>
        <?php include("include/footer.php"); exit; ?>