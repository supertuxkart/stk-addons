<?php
/**
 * Copyright 2011-2012 Stephen Just <stephenjust@users.sourceforge.net>
 *           2014-2015 Daniel Butum <danibutum at gmail dot com>
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
    $writer->writeAttribute('mtime', FileSystem::fileModificationTime(ASSETS_XML_PATH, false));
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
        if ($result['is_important'])
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

    // TODO get rid of addon types here
    foreach ($addon_types as $type)
    {
        // Fetch addon list
        try
        {
            $type_int = Addon::stringToType($type);

            // TODO find a cleaner solution to writing these queries
            // TODO optimize
            // we do not need to escape the $type variable because it is defined above
            $addons = DBConnection::get()->query(
                "SELECT A.*, R.`file_id`, R.`creation_date` AS `date`,
                        R.`revision`, R.`format`, R.`image_id`,
                        R.`icon_id`, R.`status`, U.`username`
                FROM `{DB_VERSION}_addons` A
                    LEFT JOIN `{DB_VERSION}_addon_revisions` R
                        ON A.`id` = R.`addon_id`
                    LEFT JOIN `{DB_VERSION}_users` U
                        ON A.`uploader` = U.`id`
                WHERE A.`type` = " . $type_int,
                DBConnection::FETCH_ALL
            );

            // Loop through each addon record
            foreach ($addons as $addon)
            {
                if (!$show_invisible && Addon::isInvisible($addon['status']))
                    continue;

                // TODO handle addons that do not have files :(
                if (!isset($addon['file_id']))
                {
                    Debug::addMessage(sprintf("Addon with id = %s does not have a valid file_id", $addon['id']));
                    continue;
                }

                $file_id = $addon['file_id'];
                try
                {
                    $relative_path = File::getFromID($file_id)->getPath();
                }
                catch (FileException $e)
                {
                    error_log('Error finding addon file in the database for addon = ' . $addon['name']);
                    continue;
                }

                $absolute_path = UP_PATH . $relative_path;
                if (!FileSystem::exists($absolute_path))
                {
                    error_log('File not found on the local filesystem for addon = ' . $addon['name']);
                    continue;
                }

                $writer->startElement($type);
                $writer->writeAttribute('id', $addon['id']);
                $writer->writeAttribute('name', $addon['name']);
                $writer->writeAttribute('file', DOWNLOAD_LOCATION . $relative_path);
                $writer->writeAttribute('date', strtotime($addon['date']));
                $writer->writeAttribute('uploader', $addon['username']);
                $writer->writeAttribute('designer', $addon['designer']);
                $writer->writeAttribute('description', $addon['description']);

                // TODO handle addons that do not have an image :(
                if (isset($addon['image_id']))
                {
                    try
                    {
                        $image_path = File::getFromID($addon['image_id'])->getPath();
                        if (FileSystem::exists(UP_PATH . $image_path))
                        {
                            $writer->writeAttribute('image', DOWNLOAD_LOCATION . $image_path);
                        }
                    }
                    catch (FileException $e)
                    {
                        error_log($e);
                    }
                }

                if ($type_int === Addon::KART)
                {
                    // TODO validate this
                    $icon_id = $addon['icon_id'];
                    try
                    {
                        $icon_path = File::getFromID($icon_id)->getPath();
                        if (FileSystem::exists(UP_PATH . $icon_path))
                        {
                            $writer->writeAttribute('icon', DOWNLOAD_LOCATION . $icon_path);
                        }
                    }
                    catch (FileException $e)
                    {
                        error_log($e);
                    }
                }

                $writer->writeAttribute('format', $addon['format']);
                $writer->writeAttribute('revision', $addon['revision']);
                $writer->writeAttribute('status', $addon['status']);
                $writer->writeAttribute('size', FileSystem::fileSize($absolute_path, false));
                $writer->writeAttribute('min-include-version', $addon['min_include_ver']);
                $writer->writeAttribute('max-include-version', $addon['max_include_ver']);

                // TODO fix paths
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
        catch (DBException $e)
        {
            throw new AddonException('Failed to load addon records for writing XML!');
        }
        catch (Exception $e)
        {
            throw new AddonException('Unknown error occured while loading addons');
        }
    }

    // End document tag
    $writer->fullEndElement();
    $writer->endDocument();

    // Return XML file
    $return = $writer->flush();

    return $return;
}

//function generateAssetXML2()
//{
//    // Define addon types
//    $addon_types = ['kart', 'track', 'arena'];
//    $image_list_path_format = Config::get(Config::PATH_IMAGE_JSON);
//    $license_path_format = Config::get(Config::PATH_LICENSE_JSON);
//    $show_invisible = (int)Config::get(Config::SHOW_INVISIBLE_ADDONS);
//
//    $writer = new XMLWriter();
//
//    // Output to memory
//    $writer->openMemory();
//    $writer->startDocument('1.0');
//
//    // Indent is 4 spaces
//    $writer->setIndent(true);
//    $writer->setIndentString('    ');
//
//    // Use news DTD
//    $writer->writeDtd('assets', null, '../assets/dtd/assets2.dtd');
//
//    // Open document tag
//    $writer->startElement('assets');
//    $writer->writeAttribute('version', 2);
//
//    // File creation time
//    $writer->writeAttribute('mtime', time());
//
//    // Time between updates
//    $writer->writeAttribute('frequency', (int)Config::get(Config::XML_UPDATE_TIME));
//
//    foreach ($addon_types as $type)
//    {
//        // Get list of addons
//        try
//        {
//            $writer->startElement($type);
//            $type_int = Addon::stringToType($type);
//
//            // we do not need to escape the $type variable because it is defined above
//            $addons = DBConnection::get()->query(
//                "SELECT `A`.*, `U`.`username`
//                FROM `{DB_VERSION}_addons` A
//                LEFT JOIN `{DB_VERSION}_users` U
//                    ON A.`uploader` = U.`id`
//                WHERE A.`type` = " . $type_int,
//                DBConnection::FETCH_ALL
//            );
//
//            // TODO validate data
//            // Loop through each addon
//            foreach ($addons as $addon)
//            {
//                $writer->startElement('addon');
//                $writer->writeAttribute('id', $addon['id']);
//                $writer->writeAttribute('name', $addon['name']);
//                $writer->writeAttribute('designer', $addon['designer']);
//                $writer->writeAttribute('description', $addon['description']);
//                $writer->writeAttribute('uploader', $addon['username']);
//                $writer->writeAttribute('min-include-version', $addon['min_include_ver']);
//                $writer->writeAttribute('max-include-version', $addon['max_include_ver']);
//
//                // TODO fix paths
//                // Write image list path
//                $image_list_path = str_replace(
//                    ['$aid', '$atype'],
//                    [$addon['id'], $addon['type']],
//                    $image_list_path_format
//                );
//                $writer->writeAttribute('image-list', $image_list_path);
//
//                // Write license path
//                $license_path = str_replace(
//                    ['$aid', '$atype'],
//                    [$addon['id'], $addon['type']],
//                    $license_path_format
//                );
//                $writer->writeAttribute('license', $license_path);
//
//                // Get add-on rating
//                $writer->writeAttribute('rating', sprintf('%.3F', Rating::get($addon['id'])->getAvgRating()));
//
//                // Search for revisions
//                try
//                {
//                    $addon_revs = DBConnection::get()->query(
//                        'SELECT * FROM `{DB_VERSION}_addon_revisions` WHERE `addon_id` = :id',
//                        DBConnection::FETCH_ALL,
//                        [":id" => $addon['id']]
//                    );
//
//                    foreach ($addon_revs as $addon_rev)
//                    {
//                        // Skip invisible entries
//                        if (!$show_invisible && Addon::isInvisible($addon_rev['status']))
//                        {
//                            continue;
//                        }
//
//                        try
//                        {
//                            $relative_path = File::getFromID($addon_rev['file_id'])->getPath();
//                        }
//                        catch (FileException $e)
//                        {
//                            user_error('Error finding addon file for ' . $addon['name'], E_USER_WARNING);
//                            echo '<span class="warning">An error occurred locating add-on: ' . $addon['name'] .
//                                 '</span><br />';
//                            continue;
//                        }
//
//                        $absolute_path = UP_PATH . $relative_path;
//                        if (!FileSystem::exists(UP_PATH . $relative_path))
//                        {
//                            user_error('File not found for ' . $addon['name'], E_USER_WARNING);
//                            echo '<span class="warning">' . _h(
//                                    'The following file could not be found:'
//                                ) . ' ' . $relative_path . '</span><br />';
//                            continue;
//                        }
//
//                        $writer->startElement('revision');
//
//                        $writer->writeAttribute('file', DOWNLOAD_LOCATION . $relative_path);
//                        $writer->writeAttribute('date', strtotime($addon_rev['creation_date']));
//                        $writer->writeAttribute('format', $addon_rev['format']);
//                        $writer->writeAttribute('revision', $addon_rev['revision']);
//                        $writer->writeAttribute('status', $addon_rev['status']);
//                        $writer->writeAttribute('size', FileSystem::fileSize($absolute_path, false));
//
//                        // Add image and icon to record
//                        try
//                        {
//                            $image_path = File::getFromID($addon_rev['image_id'])->getPath();
//                            if (FileSystem::exists(UP_PATH . $image_path))
//                            {
//                                $writer->writeAttribute('image', DOWNLOAD_LOCATION . $image_path);
//                            }
//                        }
//                        catch (FileException $e)
//                        {
//                            StkLog::newEvent($e->getMessage());
//                        }
//
//                        if ($type_int === Addon::KART)
//                        {
//                            try
//                            {
//                                $icon_path = File::getFromID($addon_rev['icon'])->getPath();
//                                if (FileSystem::exists(UP_PATH . $icon_path))
//                                {
//                                    $writer->writeAttribute('icon', DOWNLOAD_LOCATION . $icon_path);
//                                }
//                            }
//                            catch (FileException $e)
//                            {
//                                StkLog::newEvent($e->getMessage());
//                            }
//                        }
//
//                        $writer->fullEndElement(); // close <revision>
//                    }
//                }
//                catch (DBException $e)
//                {
//                    $writer->fullEndElement(); // close <addon>
//                    continue;
//                }
//
//                $writer->fullEndElement(); // close <addon>
//            }
//
//            $writer->fullEndElement(); // close <$type>, <kart>, etc
//        }
//        catch (DBException $e)
//        {
//            throw new AddonException('Failed to load addon records for writing XML!');
//        }
//    }
//
//    // Write music section
//    $writer->startElement('music');
//
//    $music_items = Music::getAllByTitle();
//    foreach ($music_items as $music)
//    {
//        if (!FileSystem::exists(UP_PATH . 'music' . DS . $music->getFile()))
//        {
//            user_error('File ' . UP_PATH . 'music' . DS . $music->getFile() . ' not found!', E_USER_WARNING);
//            continue;
//        }
//
//        $writer->startElement('addon');
//        $writer->writeAttribute('id', $music->getId());
//        $writer->writeAttribute('title', $music->getTitle());
//        $writer->writeAttribute('artist', $music->getArtist());
//        $writer->writeAttribute('license', $music->getLicense());
//        $writer->writeAttribute('gain', sprintf('%.3F', $music->getGain()));
//        $writer->writeAttribute('length', $music->getLength());
//        $writer->writeAttribute('file', DOWNLOAD_LOCATION . 'music/' . $music->getFile());
//        $writer->writeAttribute('size', FileSystem::fileSize(UP_PATH . 'music' . DS . $music->getFile(), false));
//        $writer->writeAttribute('xml-filename', $music->getXmlFile());
//        $writer->endElement();
//    }
//
//    $writer->fullEndElement(); // close <music>
//
//    // End document tag
//    $writer->fullEndElement();
//    $writer->endDocument();
//
//    // Return XML file
//    $return = $writer->flush();
//
//    return $return;
//}

function writeNewsXML()
{
    return FileSystem::filePutContents(NEWS_XML_PATH, generateNewsXML());
}

function writeAssetXML()
{
    $count = FileSystem::filePutContents(ASSETS_XML_PATH, generateAssetXML());
    //$count += File::write(ASSETS2_XML_PATH, generateAssetXML2());
    return $count;
}

function writeXML()
{
    writeAssetXML();
    writeNewsXML();
}
