<?php
/**
 * Copyright 2011-2012 Stephen Just <stephenjust@users.sourceforge.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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

function generateNewsXML()
{
    $writer = new XMLWriter();

    // Output to memory
    $writer->openMemory();
    $writer->startDocument('1.0');

    // Indent is 4 spaces
    $writer->setIndent(true);
    $writer->setIndentString('    ');

    // Use news DTD
    $writer->writeDtd('news', null, '../assets/dtd/news.dtd');

    // Open document tag
    $writer->startElement('news');
    $writer->writeAttribute('version', 1);

    // File creation time
    $writer->writeAttribute('mtime', time());

    // Time between updates
    $writer->writeAttribute('frequency', (int)Config::get(Config::XML_UPDATE_TIME));

    // Reference assets.xml
    $writer->startElement('include');
    $writer->writeAttribute('file', ASSETS_XML_LOCATION);
    $writer->writeAttribute('mtime', filemtime(ASSETS_XML_PATH));
    $writer->endElement();

    // Refresh dynamic news entries
    News::refreshDynamicEntries();
    $news_entries = News::getActive();
    foreach ($news_entries as $result)
    {
        $writer->startElement('message');
        $writer->writeAttribute('id', $result['id']);
        $writer->writeAttribute('date', $result['date']);
        $writer->writeAttribute('author', $result['author']);
        $writer->writeAttribute('content', $result['content']);
        if (mb_strlen($result['condition']) > 0)
        {
            $writer->writeAttribute('condition', $result['condition']);
        }
        if ($result['important'])
        {
            $writer->writeAttribute('important', 'true');
        }
        $writer->endElement();
    }

    // End document tag
    $writer->fullEndElement();
    $writer->endDocument();

    // Return XML file
    $return = $writer->flush();

    return $return;
}

function generateAssetXML()
{
    // Define addon types
    $addon_types = ['kart', 'track', 'arena'];
    $image_list_path_format = Config::get(Config::PATH_IMAGE_JSON);
    $license_path_format = Config::get(Config::PATH_LICENSE_JSON);
    $show_invisible = (int)Config::get(Config::SHOW_INVISIBLE_ADDONS);
    $writer = new XMLWriter();

    // Output to memory
    $writer->openMemory();
    $writer->startDocument('1.0');

    // Indent is 4 spaces
    $writer->setIndent(true);
    $writer->setIndentString('    ');

    // Use news DTD
    $writer->writeDtd('assets', null, '../assets/dtd/assets.dtd');

    // Open document tag
    $writer->startElement('assets');
    $writer->writeAttribute('version', 1);

    // File creation time
    $writer->writeAttribute('mtime', time());

    // Time between updates
    $writer->writeAttribute('frequency', (int)Config::get(Config::XML_UPDATE_TIME));

    foreach ($addon_types as $type)
    {
        // Fetch addon list
        try
        {
            $iconQuery = ($type === "kart") ? '`r`.`icon`,' : '';

            // TODO find a cleaner solution to writing these queries
            // we do not need to escape the $type variable because it is defined above
            $addons = DBConnection::get()->query(
                'SELECT `k`.*, `r`.`fileid`, `r`.`creation_date` AS `date`,
                `r`.`revision`, `r`.`format`, `r`.`image`,
                ' . $iconQuery . ' `r`.`status`, `u`.`user`
                FROM ' . DB_PREFIX . 'addons k
                    LEFT JOIN ' . DB_PREFIX . $type . 's_revs r
                        ON (`k`.`id` = `r`.`addon_id`)
                    LEFT JOIN ' . DB_PREFIX . 'users u
                        ON (`k`.`uploader` = `u`.`id`)
                WHERE `k`.`type` = \'' . $type . 's\'',
                DBConnection::FETCH_ALL
            );

            // Loop through each addon record
            foreach ($addons as $addon)
            {
                if (!$show_invisible && Addon::isInvisible($addon['status']))
                {
                    continue;
                }

                $file_path = File::getPath($addon['fileid']);
                if (!$file_path)
                {
                    trigger_error('Error finding addon file for ' . $addon['name'], E_USER_WARNING);
                    echo '<span class="warning">An error occurred locating add-on: ' . $addon['name'] . '</span><br />';
                    continue;
                }

                if (!file_exists(UP_PATH . $file_path))
                {
                    trigger_error('File not found for ' . $addon['name'], E_USER_WARNING);
                    echo '<span class="warning">' . _h('The following file could not be found:') . ' ' . $file_path . '</span><br />';
                    continue;
                }

                $writer->startElement($type);
                $writer->writeAttribute('id', $addon['id']);
                $writer->writeAttribute('name', $addon['name']);
                $writer->writeAttribute('file', DOWNLOAD_LOCATION . $file_path);
                $writer->writeAttribute('date', strtotime($addon['date']));
                $writer->writeAttribute('uploader', $addon['user']);
                $writer->writeAttribute('designer', $addon['designer']);
                $writer->writeAttribute('description', $addon['description']);
                $image_path = File::getPath($addon['image']);
                if ($image_path)
                {
                    if (file_exists(UP_PATH . $image_path))
                    {
                        $writer->writeAttribute('image', DOWNLOAD_LOCATION . $image_path);
                    }
                }

                if ($type == "kart")
                {
                    echo 'FOUND KART';
                    $icon_path = File::getPath($addon['icon']);
                    if ($icon_path)
                    {
                        if (file_exists(UP_PATH . $icon_path))
                        {
                            $writer->writeAttribute('icon', DOWNLOAD_LOCATION . $icon_path);
                        }
                    }
                }

                $writer->writeAttribute('format', $addon['format']);
                $writer->writeAttribute('revision', $addon['revision']);
                $writer->writeAttribute('status', $addon['status']);
                $writer->writeAttribute('size', filesize(UP_PATH . $file_path));
                $writer->writeAttribute('min-include-version', $addon['min_include_ver']);
                $writer->writeAttribute('max-include-version', $addon['max_include_ver']);

                // Write license path
                $license_path = str_replace(
                    ['$aid', '$atype'],
                    [$addon['id'], $addon['type']],
                    $license_path_format
                );
                $writer->writeAttribute('license', $license_path);

                // Write image list path
                $image_list_path = str_replace(
                    ['$aid', '$atype'],
                    [$addon['id'], $addon['type']],
                    $image_list_path_format
                );
                $writer->writeAttribute('image-list', $image_list_path);

                // Get add-on rating
                $writer->writeAttribute('rating', sprintf('%.3F', Rating::get($addon['id'])->getAvgRating()));
                $writer->endElement(); // close <$type>
            }
        }
        catch(DBException $e)
        {
            throw new AddonException('Failed to load addon records for writing XML!');
        }
    }

    // End document tag
    $writer->fullEndElement();
    $writer->endDocument();

    // Return XML file
    $return = $writer->flush();

    return $return;
}

function generateAssetXML2()
{
    // Define addon types
    $addon_types = ['kart', 'track', 'arena'];
    $image_list_path_format = Config::get(Config::PATH_IMAGE_JSON);
    $license_path_format = Config::get(Config::PATH_LICENSE_JSON);
    $show_invisible = (int)Config::get(Config::SHOW_INVISIBLE_ADDONS);

    $writer = new XMLWriter();

    // Output to memory
    $writer->openMemory();
    $writer->startDocument('1.0');

    // Indent is 4 spaces
    $writer->setIndent(true);
    $writer->setIndentString('    ');

    // Use news DTD
    $writer->writeDtd('assets', null, '../assets/dtd/assets2.dtd');

    // Open document tag
    $writer->startElement('assets');
    $writer->writeAttribute('version', 2);

    // File creation time
    $writer->writeAttribute('mtime', time());

    // Time between updates
    $writer->writeAttribute('frequency', (int)Config::get(Config::XML_UPDATE_TIME));

    foreach ($addon_types as $type)
    {
        // Get list of addons
        try
        {
            $writer->startElement($type);

            // we do not need to escape the $type variable because it is defined above
            $addons = DBConnection::get()->query(
                'SELECT `a`.*, `u`.`user`
                FROM `' . DB_PREFIX . 'addons` `a`
                LEFT JOIN `' . DB_PREFIX . 'users` `u`
                ON (`a`.`uploader` = `u`.`id`)
                WHERE `a`.`type` = \'' . $type . 's\'',
                DBConnection::FETCH_ALL
            );

            // Loop through each addon
            foreach ($addons as $addon)
            {
                $writer->startElement('addon');
                $writer->writeAttribute('id', $addon['id']);
                $writer->writeAttribute('name', $addon['name']);
                $writer->writeAttribute('designer', $addon['designer']);
                $writer->writeAttribute('description', $addon['description']);
                $writer->writeAttribute('uploader', $addon['user']);
                $writer->writeAttribute('min-include-version', $addon['min_include_ver']);
                $writer->writeAttribute('max-include-version', $addon['max_include_ver']);

                // Write image list path
                $image_list_path = str_replace(
                    ['$aid', '$atype'],
                    [$addon['id'], $addon['type']],
                    $image_list_path_format
                );
                $writer->writeAttribute('image-list', $image_list_path);

                // Write license path
                $license_path = str_replace(
                    ['$aid', '$atype'],
                    [$addon['id'], $addon['type']],
                    $license_path_format
                );
                $writer->writeAttribute('license', $license_path);

                // Get add-on rating
                $writer->writeAttribute('rating', sprintf('%.3F', Rating::get($addon['id'])->getAvgRating()));

                // Search for revisions
                try
                {
                    $addon_revs = DBConnection::get()->query(
                        'SELECT * FROM `' . DB_PREFIX . $type . 's_revs` WHERE `addon_id` = :id',
                        DBConnection::FETCH_ALL,
                        [":id" => $addon['id']]
                    );

                    foreach ($addon_revs as $addon_rev)
                    {
                        // Skip invisible entries
                        if (!$show_invisible && Addon::isInvisible($addon_rev['status']))
                        {
                            continue;
                        }

                        $file_path = File::getPath($addon_rev['fileid']);
                        if (!$file_path || !file_exists(UP_PATH . $file_path))
                        {
                            continue;
                        }

                        $writer->startElement('revision');

                        $writer->writeAttribute('file', DOWNLOAD_LOCATION . $file_path);
                        $writer->writeAttribute('date', strtotime($addon_rev['creation_date']));
                        $writer->writeAttribute('format', $addon_rev['format']);
                        $writer->writeAttribute('revision', $addon_rev['revision']);
                        $writer->writeAttribute('status', $addon_rev['status']);
                        $writer->writeAttribute('size', filesize(UP_PATH . $file_path));

                        // Add image and icon to record
                        $image_path = File::getPath($addon_rev['image']);
                        if ($image_path)
                        {
                            if (file_exists(UP_PATH . $image_path))
                            {
                                $writer->writeAttribute('image', DOWNLOAD_LOCATION . $image_path);
                            }
                        }
                        if ($type === "kart")
                        {
                            $icon_path = File::getPath($addon_rev['icon']);
                            if ($icon_path)
                            {
                                if (file_exists(UP_PATH . $icon_path))
                                {
                                    $writer->writeAttribute('icon', DOWNLOAD_LOCATION . $icon_path);
                                }
                            }
                        }

                        $writer->fullEndElement(); // close <revision>
                    }
                }
                catch(DBException $e)
                {
                    $writer->fullEndElement(); // close <addon>
                    continue;
                }

                $writer->fullEndElement(); // close <addon>
            }

            $writer->fullEndElement(); // close <$type>, <kart>, etc
        }
        catch(DBException $e)
        {
            throw new AddonException('Failed to load addon records for writing XML!');
        }
    }

    // Write music section
    $writer->startElement('music');

    $music_items = Music::getAllByTitle();
    foreach ($music_items as $music)
    {
        if (!file_exists(UP_PATH . 'music' . DS . $music->getFile()))
        {
            trigger_error('File ' . UP_PATH . 'music' . DS . $music->getFile() . ' not found!', E_USER_WARNING);
            continue;
        }

        $writer->startElement('addon');
        $writer->writeAttribute('id', $music->getId());
        $writer->writeAttribute('title', $music->getTitle());
        $writer->writeAttribute('artist', $music->getArtist());
        $writer->writeAttribute('license', $music->getLicense());
        $writer->writeAttribute('gain', sprintf('%.3F', $music->getGain()));
        $writer->writeAttribute('length', $music->getLength());
        $writer->writeAttribute('file', DOWNLOAD_LOCATION . 'music/' . $music->getFile());
        $writer->writeAttribute('size', filesize(UP_PATH . 'music' . DS . $music->getFile()));
        $writer->writeAttribute('xml-filename', $music->getXmlFile());
        $writer->endElement();
    }

    $writer->fullEndElement(); // close <music>

    // End document tag
    $writer->fullEndElement();
    $writer->endDocument();

    // Return XML file
    $return = $writer->flush();

    return $return;
}

function writeNewsXML()
{
    return File::write(NEWS_XML_PATH, generateNewsXML());
}

function writeAssetXML()
{
    $count = File::write(ASSETS2_XML_PATH, generateAssetXML2());
    $count += File::write(ASSETS_XML_PATH, generateAssetXML());

    return $count;
}

function writeXML()
{
    writeAssetXML();
    writeNewsXML();
}