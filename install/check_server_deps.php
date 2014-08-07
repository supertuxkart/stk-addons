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

// Check for Magic Quotes GPC
if (get_magic_quotes_gpc())
{
    echo '<strong>Magic Quotes GPC Enabled.</strong><br />';
}
else
{
    echo 'Magic Quotes GPC Disabled.<br />';
}

// Check for register_globals
if (ini_get('register_globals'))
{
    echo '<strong>register_globals Enabled.</strong><br />';
}
else
{
    echo 'register_globals Disabled.<br />';
}

// Check for short_open_tag
if (ini_get('short_open_tag'))
{
    echo 'short_open_tag Enabled.<br />';
}
else
{
    echo '<strong>short_open_tag Disabled.</strong><br />';
}

// Check for PEAR
@ require_once('System.php');
if (class_exists('System'))
{
    echo 'PEAR Available.<br />';
}
else
{
    echo '<strong>PEAR Not Available.</strong><br />';
}

if (class_exists('ZipArchive'))
{
    echo 'ZipArchive Available.<br />';
}
else
{
    echo '<strong>ZipArchive Not Available.</strong><br />';
}

// Check for PEAR::Mail
@ include_once('Mail.php');
if (class_exists('Mail'))
{
    echo 'PEAR::Mail Available.<br />';
}
else
{
    echo '<strong>PEAR::Mail Not Available.</strong><br />';
}

// Check for PEAR::Net_SMTP
@ include_once('Net/SMTP.php');
if (class_exists('Net_SMTP'))
{
    echo 'PEAR::Net_SMTP Available.<br />';
}
else
{
    echo '<strong>PEAR::Net_SMTP Not Available.</strong><br />';
}

// Check for Fileinfo ext
if (function_exists('finfo_open'))
{
    echo 'Fileinfo Available.<br />';
}
else
{
    echo '<strong>Fileinfo Not Available.</strong><br />';
}

// Check for Gettext
if (function_exists('_'))
{
    echo 'Gettext Available.<br />';
}
else
{
    echo '<strong>Gettext Not Available.<br />';
}

// Check for XMLReader
if (class_exists('XMLReader'))
{
    echo 'XMLReader Available.<br />';
}
else
{
    echo '<strong>XMLReader Not Available.<br />';
}

// Check for MySQLi
if (function_exists('mysqli_connect'))
{
    echo 'MySQLi Available.<br />';
}
else
{
    echo '<strong>MySQLi Not Available.<br />';
}

// Check for hash
if (function_exists('hash'))
{
    echo 'hash() Available.<br />';
}
else
{
    echo '<strong>hash() Not Available.</strong><br />';
}

// Check for GD
if (function_exists('gd_info'))
{
    echo 'GD Available, and supports image types: ';
    $image_types = imagetypes();
    if ($image_types & IMG_GIF)
    {
        echo 'GIF ';
    }
    if ($image_types & IMG_PNG)
    {
        echo 'PNG ';
    }
    if ($image_types & IMG_JPG)
    {
        echo 'JPG ';
    }
    if ($image_types & IMG_WBMP)
    {
        echo 'WBMP ';
    }
    echo '<br />';
}
else
{
    echo '<strong>GD Not Available.</strong><br />';
}
