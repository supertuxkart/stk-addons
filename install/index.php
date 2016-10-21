<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sf.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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

function text_success($text)
{
    return sprintf('<p style="color: #1d8a00">%s</p>', $text);
}

function text_error($text)
{
    return sprintf('<p style="color: #D8000C">%s</p>', $text);
}

if (get_magic_quotes_gpc())
{
    echo text_error('Magic Quotes GPC Enabled.');
}
else
{
    echo text_success('Magic Quotes GPC Disabled.');
}

if (ini_get('register_globals'))
{
    echo text_error('register_globals Enabled.');
}
else
{
    echo text_success('register_globals Disabled.');
}

if (extension_loaded("zip"))
{
    echo text_success('Zip Available.');
}
else
{
    echo text_error('Zip Not Available');
}

if (extension_loaded("gettext"))
{
    echo text_success('Gettext Available.');
}
else
{
    echo text_error('Gettext Not Available.');
}

if (extension_loaded("xml"))
{
    echo text_success('XML is Available.');
}
else
{
    echo text_error('XML Not Available.');
}

if (extension_loaded("pdo"))
{
    echo text_success("PDO is Available");
}
else
{
    echo text_error("PDO Not Available");
}

if (extension_loaded("mbstring"))
{
    echo text_success("mbstring is Available");
}
else
{
    echo text_error("mbstring Not Available");
}

if (extension_loaded("mcrypt"))
{
    echo text_success("mcrypt is Available");
}
else
{
    echo text_error("mcrypt Not Available");
}

// Check for GD
if (extension_loaded('gd'))
{
    $image_types = imagetypes();
    $supported = [];
    if ($image_types & IMG_GIF)
    {
        $supported[] = 'GIF';
    }
    if ($image_types & IMG_PNG)
    {
        $supported[] = 'PNG';
    }
    if ($image_types & IMG_JPG)
    {
        $supported[] = 'JPG';
    }
    if ($image_types & IMG_WBMP)
    {
        $supported[] = 'WBMP';
    }
    if ($image_types & IMG_XPM)
    {
        $supported[] = "XPM";
    }

    echo text_success('GD Available, and supports the following image types: ' . implode(", ", $supported));
}
else
{
    echo text_error('GD Not Available.');
}
