<?php
/**
 * copyright 2011 Stephen Just <stephenjust@users.sourceforge.net>
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
    $writer->writeDtd('news',NULL,'../docs/news.dtd');

    // Open document tag
    $writer->startElement('news');
    $writer->writeAttribute('version',1);
    // File creation time
    $writer->writeAttribute('mtime',time());
    // Time between updates
    $writer->writeAttribute('frequency', ConfigManager::get_config('xml_frequency'));

    // Reference assets.xml
    $writer->startElement('include');
    $writer->writeAttribute('file',ASSET_XML);
    $writer->writeAttribute('mtime',filemtime(ASSET_XML_LOCAL));
    $writer->endElement();
    
    // Refresh dynamic news entries
    News::refreshDynamicEntries();

    // Fetch news list
    $querySql = 'SELECT `n`.*, `u`.`user`
	FROM '.DB_PREFIX.'news n
	LEFT JOIN '.DB_PREFIX.'users u
	ON (`n`.`author_id`=`u`.`id`)
	WHERE `n`.`active` = \'1\'
        ORDER BY `date` DESC';
    $reqSql = sql_query($querySql);
    while($result = sql_next($reqSql))
    {
	$writer->startElement('message');
        $writer->writeAttribute('id',$result['id']);
	$writer->writeAttribute('date',$result['date']);
	$writer->writeAttribute('author',$result['user']);
	$writer->writeAttribute('content',$result['content']);
        if (strlen($result['condition']) > 0)
            $writer->writeAttribute('condition',$result['condition']);
	$writer->endElement();
    }

    // End document tag
    $writer->fullEndElement();
    $writer->endDocument();

    // Return XML file
    $return = $writer->flush();

    return $return;
}

function writeNewsXML()
{
    return writeFile(generateNewsXML(), NEWS_XML_LOCAL);
}

function generateAssetXML()
{
    // Define addon types
    $addon_types = array('kart','track','arena');
    $writer = new XMLWriter();
    // Output to memory
    $writer->openMemory();
    $writer->startDocument('1.0');
    // Indent is 4 spaces
    $writer->setIndent(true);
    $writer->setIndentString('    ');
    // Use news DTD
    $writer->writeDtd('assets',NULL,'../docs/assets.dtd');

    // Open document tag
    $writer->startElement('assets');
    $writer->writeAttribute('version',1);
    // File creation time
    $writer->writeAttribute('mtime',time());
    // Time between updates
    $writer->writeAttribute('frequency', ConfigManager::get_config('xml_frequency'));

    foreach ($addon_types AS $type)
    {
        // Fetch addon list
        $querySql = 'SELECT `k`.*, `r`.`fileid`,
                `r`.`creation_date` AS `date`,`r`.`revision`,`r`.`format`,
                `r`.`image`,`r`.`status`, `u`.`user`
            FROM '.DB_PREFIX.'addons k
            LEFT JOIN '.DB_PREFIX.$type.'s_revs r
            ON (`k`.`id`=`r`.`addon_id`)
            LEFT JOIN '.DB_PREFIX.'users u
            ON (`k`.`uploader` = `u`.`id`)
            WHERE `k`.`type` = \''.$type.'s\'';
        $reqSql = sql_query($querySql);

        // Loop through each addon record
        while($result = sql_next($reqSql))
        {
            if (ConfigManager::get_config('list_invisible') == 0)
            {
                if($result['status'] & F_INVISIBLE)
                    continue;
            }
            $file_path = File::getPath($result['fileid']);
            if ($file_path === false)
            {
                echo '<span class="warning">An error occurred locating add-on: '.$result['name'].'</span><br />';
                continue;
            }
            if (!file_exists(UP_LOCATION.$file_path))
            {
                echo '<span class="warming">'.htmlspecialchars(_('The following file could not be found:')).' '.$file_path.'</span><br />';
                continue;
            }
            $writer->startElement($type);
            $writer->writeAttribute('id',$result['id']);
            $writer->writeAttribute('name',$result['name']);
            $writer->writeAttribute('file',DOWN_LOCATION.$file_path);
            $writer->writeAttribute('date',strtotime($result['date']));
            $writer->writeAttribute('uploader',$result['user']);
            $writer->writeAttribute('designer',$result['designer']);
            $writer->writeAttribute('description',$result['description']);
            $image_path = File::getPath($result['image']);
            if ($image_path !== false)
            {
                if (file_exists(UP_LOCATION.$image_path))
                {
                    $writer->writeAttribute('image',DOWN_LOCATION.$image_path);
                }
            }
            $writer->writeAttribute('format',$result['format']);
            $writer->writeAttribute('revision',$result['revision']);
            $writer->writeAttribute('status',$result['status']);
            $writer->writeAttribute('size',filesize(UP_LOCATION.$file_path));
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
function writeAssetXML()
{
    return writeFile(generateAssetXML(), ASSET_XML_LOCAL);
}

function writeFile($content,$file)
{
    // If file doesn't exist, create it
    if (!file_exists($file))
    {
	if (!touch($file))
	{
	    return false;
	}
    }
    $fhandle = fopen($file,'w');
    if (!fwrite($fhandle,$content))
    {
	return false;
    }
    fclose($fhandle);
    return true;
}

?>
