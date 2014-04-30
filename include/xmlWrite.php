<?php

/**
 * Copyright 2011-2012 Stephen Just <stephenjust@users.sourceforge.net>
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

require_once(INCLUDE_DIR . 'Constants.php');
require_once(INCLUDE_DIR . 'News.class.php');
require_once(INCLUDE_DIR . 'Music.class.php');

function generateNewsXML() {
    $writer = new XMLWriter();
    // Output to memory
    $writer->openMemory();
    $writer->startDocument('1.0');
    // Indent is 4 spaces
    $writer->setIndent(true);
    $writer->setIndentString('    ');
    // Use news DTD
    $writer->writeDtd('news', NULL, '../docs/news.dtd');

    // Open document tag
    $writer->startElement('news');
    $writer->writeAttribute('version', 1);
    // File creation time
    $writer->writeAttribute('mtime', time());
    // Time between updates
    $writer->writeAttribute('frequency', ConfigManager::get_config('xml_frequency'));

    // Reference assets.xml
    $writer->startElement('include');
    $writer->writeAttribute('file', ASSET_XML);
    $writer->writeAttribute('mtime', filemtime(ASSET_XML_LOCAL));
    $writer->endElement();

    // Refresh dynamic news entries
    News::refreshDynamicEntries();
    $news_entries = News::getActive();
    foreach ($news_entries AS $result) {
        $writer->startElement('message');
        $writer->writeAttribute('id', $result['id']);
        $writer->writeAttribute('date', $result['date']);
        $writer->writeAttribute('author', $result['author']);
        $writer->writeAttribute('content', $result['content']);
        if (strlen($result['condition']) > 0)
            $writer->writeAttribute('condition', $result['condition']);
        if ($result['important']) {
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

function writeNewsXML() {
    return writeFile(generateNewsXML(), NEWS_XML_LOCAL);
}

function generateAssetXML() {
    // Define addon types
    $addon_types = array('kart', 'track', 'arena');
    $writer = new XMLWriter();
    // Output to memory
    $writer->openMemory();
    $writer->startDocument('1.0');
    // Indent is 4 spaces
    $writer->setIndent(true);
    $writer->setIndentString('    ');
    // Use news DTD
    $writer->writeDtd('assets', NULL, '../docs/assets.dtd');

    // Open document tag
    $writer->startElement('assets');
    $writer->writeAttribute('version', 1);
    // File creation time
    $writer->writeAttribute('mtime', time());
    // Time between updates
    $writer->writeAttribute('frequency', ConfigManager::get_config('xml_frequency'));

    foreach ($addon_types AS $type) {
        // Fetch addon list
        $iconQuery = ($type == "kart") ? '`r`.`icon`,' : NULL;
        $querySql = 'SELECT `k`.*, `r`.`fileid`,
                `r`.`creation_date` AS `date`,`r`.`revision`,`r`.`format`,
                `r`.`image`,' . $iconQuery . '`r`.`status`, `u`.`user`
            FROM ' . DB_PREFIX . 'addons k
            LEFT JOIN ' . DB_PREFIX . $type . 's_revs r
            ON (`k`.`id`=`r`.`addon_id`)
            LEFT JOIN ' . DB_PREFIX . 'users u
            ON (`k`.`uploader` = `u`.`id`)
            WHERE `k`.`type` = \'' . $type . 's\'';
        $reqSql = sql_query($querySql);

        // Loop through each addon record
        while ($result = sql_next($reqSql)) {
            if (ConfigManager::get_config('list_invisible') == 0) {
                if ($result['status'] & F_INVISIBLE) {
                    trigger_error('Hiding invisible addon ' . $result['name'], E_USER_WARNING);
                    continue;
                }
            }
            $file_path = File::getPath($result['fileid']);
            if ($file_path === false) {
                trigger_error('Error finding addon file for ' . $result['name'], E_USER_WARNING);
                echo '<span class="warning">An error occurred locating add-on: ' . $result['name'] . '</span><br />';
                continue;
            }

            if (!file_exists(UP_LOCATION . $file_path)) {
                trigger_error('File not found for ' . $result['name'], E_USER_WARNING);
                echo '<span class="warning">' . htmlspecialchars(_('The following file could not be found:')) . ' ' . $file_path . '</span><br />';
                continue;
            }

            $writer->startElement($type);
            $writer->writeAttribute('id', $result['id']);
            $writer->writeAttribute('name', $result['name']);
            $writer->writeAttribute('file', DOWN_LOCATION . $file_path);
            $writer->writeAttribute('date', strtotime($result['date']));
            $writer->writeAttribute('uploader', $result['user']);
            $writer->writeAttribute('designer', $result['designer']);
            $writer->writeAttribute('description', $result['description']);
            $image_path = File::getPath($result['image']);
            if ($image_path !== false) {
                if (file_exists(UP_LOCATION . $image_path)) {
                    $writer->writeAttribute('image', DOWN_LOCATION . $image_path);
                }
            }
            if ($type == "kart") {
                $icon_path = File::getPath($result['icon']);
                if ($icon_path !== false) {
                    if (file_exists(UP_LOCATION . $icon_path)) {
                        $writer->writeAttribute('icon', DOWN_LOCATION . $icon_path);
                    }
                }
            }
            $writer->writeAttribute('format', $result['format']);
            $writer->writeAttribute('revision', $result['revision']);
            $writer->writeAttribute('status', $result['status']);
            $writer->writeAttribute('size', filesize(UP_LOCATION . $file_path));
            $writer->writeAttribute('min-include-version', $result['min_include_ver']);
            $writer->writeAttribute('max-include-version', $result['max_include_ver']);
            // Write license path
            $license_path_format = ConfigManager::get_config('license_json_path');
            $license_path = str_replace(array('$aid', '$atype'), array($result['id'], $result['type']), $license_path_format);
            $writer->writeAttribute('license', $license_path);
            $image_list_path = ConfigManager::get_config('image_json_path');
            $image_list_path = str_replace('$aid', $result['id'], $image_list_path);
            $image_list_path = str_replace('$atype', $result['type'], $image_list_path);
            $writer->writeAttribute('image-list', $image_list_path);
            // Get add-on rating
            $rating = new Ratings($result['id']);
            $writer->writeAttribute('rating', sprintf('%.3F', $rating->getAvgRating()));
            $writer->endElement();
        }
    }

    // End document tag
    $writer->fullEndElement();
    $writer->endDocument();

    // Return XML file
    $return = $writer->flush();

    return $return;
}

function generateAssetXML2() {
    // Define addon types
    $addon_types = array('kart', 'track', 'arena');
    $image_list_path_format = ConfigManager::get_config('image_json_path');
    $license_path_format = ConfigManager::get_config('license_json_path');

    $writer = new XMLWriter();
    // Output to memory
    $writer->openMemory();
    $writer->startDocument('1.0');
    // Indent is 4 spaces
    $writer->setIndent(true);
    $writer->setIndentString('    ');
    // Use news DTD
    $writer->writeDtd('assets', NULL, '../docs/assets2.dtd');

    // Open document tag
    $writer->startElement('assets');
    $writer->writeAttribute('version', 2);
    // File creation time
    $writer->writeAttribute('mtime', time());
    // Time between updates
    $writer->writeAttribute('frequency', ConfigManager::get_config('xml_frequency'));

    foreach ($addon_types AS $type) {
        $writer->startElement($type);

        // Get list of addons
        $addon_query = 'SELECT `a`.*, `u`.`user`
	    FROM `' . DB_PREFIX . 'addons` `a`
            LEFT JOIN `' . DB_PREFIX . 'users` `u`
            ON (`a`.`uploader` = `u`.`id`)
	    WHERE `a`.`type` = \'' . $type . 's\'';
        $addon_q_handle = sql_query($addon_query);
        if (!$addon_q_handle) {
            throw new AddonException('Failed to load addon records for writing XML!');
        }
        $num_addons = mysql_num_rows($addon_q_handle);

        // Loop through each addon
        for ($i = 0; $i < $num_addons; $i++) {
            $addon_result = mysql_fetch_assoc($addon_q_handle);
            $writer->startElement('addon');
            $writer->writeAttribute('id', $addon_result['id']);
            $writer->writeAttribute('name', $addon_result['name']);
            $writer->writeAttribute('designer', $addon_result['designer']);
            $writer->writeAttribute('description', $addon_result['description']);
            $writer->writeAttribute('uploader', $addon_result['user']);
            $writer->writeAttribute('min-include-version', $addon_result['min_include_ver']);
            $writer->writeAttribute('max-include-version', $addon_result['max_include_ver']);
            // Write image list path
            $image_list_path = str_replace(array('$aid', '$atype'), array($addon_result['id'], $addon_result['type']), $image_list_path_format);
            $writer->writeAttribute('image-list', $image_list_path);
            // Write license path
            $license_path = str_replace(array('$aid', '$atype'), array($addon_result['id'], $addon_result['type']), $license_path_format);
            $writer->writeAttribute('license', $license_path);
            // Get add-on rating
            $rating = new Ratings($addon_result['id']);
            $writer->writeAttribute('rating', sprintf('%.3F', $rating->getAvgRating()));

            // Search for revisions
            $rev_query = 'SELECT * FROM `' . DB_PREFIX . $type . 's_revs`
		WHERE `addon_id` = \'' . $addon_result['id'] . '\'';
            $rev_handle = sql_query($rev_query);
            if (!$rev_handle) {
                $writer->fullEndElement();
                continue;
            }
            $num_revs = mysql_num_rows($rev_handle);

            // Loop through revisions
            for ($j = 0; $j < $num_revs; $j++) {
                $revision = mysql_fetch_assoc($rev_handle);
                // Skip invisible entries
                if (ConfigManager::get_config('list_invisible') == 0 &&
                        $revision['status'] & F_INVISIBLE) {
                    continue;
                }
                $file_path = File::getPath($revision['fileid']);
                if ($file_path === false || !file_exists(UP_LOCATION . $file_path)) {
                    continue;
                }
                $writer->startElement('revision');

                $writer->writeAttribute('file', DOWN_LOCATION . $file_path);
                $writer->writeAttribute('date', strtotime($revision['creation_date']));
                $writer->writeAttribute('format', $revision['format']);
                $writer->writeAttribute('revision', $revision['revision']);
                $writer->writeAttribute('status', $revision['status']);
                $writer->writeAttribute('size', filesize(UP_LOCATION . $file_path));

                // Add image and icon to record
                $image_path = File::getPath($revision['image']);
                if ($image_path !== false) {
                    if (file_exists(UP_LOCATION . $image_path)) {
                        $writer->writeAttribute('image', DOWN_LOCATION . $image_path);
                    }
                }
                if ($type == "kart") {
                    $icon_path = File::getPath($revision['icon']);
                    if ($icon_path !== false) {
                        if (file_exists(UP_LOCATION . $icon_path)) {
                            $writer->writeAttribute('icon', DOWN_LOCATION . $icon_path);
                        }
                    }
                }


                $writer->fullEndElement();
            }

            $writer->fullEndElement();
        }
        $writer->fullEndElement();
    }

    // Write music section
    $writer->startElement('music');

    $music_items = Music::getAllByTitle();
    foreach ($music_items AS $music) {
        if (!file_exists(UP_LOCATION . 'music/' . $music->getFile())) {
            trigger_error('File ' . UP_LOCATION . 'music/' . $music->getFile() . ' not found!', E_USER_WARNING);
            continue;
        }
        $writer->startElement('addon');
        $writer->writeAttribute('id', $music->getId());
        $writer->writeAttribute('title', $music->getTitle());
        $writer->writeAttribute('artist', $music->getArtist());
        $writer->writeAttribute('license', $music->getLicense());
        $writer->writeAttribute('gain', sprintf('%.3F', $music->getGain()));
        $writer->writeAttribute('length', $music->getLength());
        $writer->writeAttribute('file', DOWN_LOCATION . 'music/' . $music->getFile());
        $writer->writeAttribute('size', filesize(UP_LOCATION . 'music/' . $music->getFile()));
        $writer->writeAttribute('xml-filename', $music->getXmlFile());
        $writer->endElement();
    }

    $writer->fullEndElement();

    // End document tag
    $writer->fullEndElement();
    $writer->endDocument();

    // Return XML file
    $return = $writer->flush();

    return $return;
}

function writeAssetXML() {
    writeFile(generateAssetXML2(), ASSET_XML2_LOCAL);
    return writeFile(generateAssetXML(), ASSET_XML_LOCAL);
}

function writeFile($content, $file) {
    // If file doesn't exist, create it
    if (!file_exists($file)) {
        if (!touch($file)) {
            return false;
        }
    }
    $fhandle = fopen($file, 'w');
    if (!$fhandle) {
        trigger_error('Could not open xml file for writing!', E_USER_WARNING);
        return false;
    }
    if (!fwrite($fhandle, $content)) {
        return false;
    }
    fclose($fhandle);
    return true;
}
