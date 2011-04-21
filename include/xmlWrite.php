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
    $writer->writeAttribute('frequency', get_config('xml_frequency'));

    // Reference assets.xml
    $writer->startElement('include');
    $writer->writeAttribute('file',ASSET_XML);
    $writer->writeAttribute('mtime',filemtime(ASSET_XML_LOCAL));
    $writer->endElement();

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
    $writer->writeAttribute('frequency', get_config('xml_frequency'));

    // Fetch kart list
    $querySql = 'SELECT `k`.*, `r`.`id` AS `fileid`,
            `r`.`creation_date` AS `date`,`r`.`revision`,`r`.`format`,
            `r`.`image`,`r`.`status`, `u`.`user`
	FROM '.DB_PREFIX.'karts k
	LEFT JOIN '.DB_PREFIX.'karts_revs r
	ON (`k`.`id`=`r`.`addon_id`)
	LEFT JOIN '.DB_PREFIX.'users u
        ON (`k`.`uploader` = `u`.`id`)';
    $reqSql = sql_query($querySql);
    while($result = sql_next($reqSql))
    {
        if (!file_exists(UP_LOCATION.$result['fileid'].'.zip'))
        {
            echo '<span class="warming">'._('The following file could not be found:').' '.$result['fileid'].'.zip</span><br />';
            continue;
        }
	$writer->startElement('kart');
        $writer->writeAttribute('name',$result['name']);
        $writer->writeAttribute('file',DOWN_LOCATION.'assets/'.$result['fileid'].'.zip');
	$writer->writeAttribute('date',$result['date']);
	$writer->writeAttribute('uploader',$result['user']);
        $writer->writeAttribute('designer',$result['designer']);
	$writer->writeAttribute('image',DOWN_LOCATION.'images/'.$result['image']);
        $writer->writeAttribute('format',$result['format']);
        $writer->writeAttribute('revision',$result['revision']);
        $writer->writeAttribute('status',$result['status']);
        $writer->writeAttribute('size',filesize(UP_LOCATION.$result['fileid'].'.zip'));
	$writer->endElement();
    }

    // Fetch track list
    $querySql = 'SELECT `k`.*, `r`.`id` AS `fileid`,
            `r`.`creation_date` AS `date`,`r`.`revision`,`r`.`format`,
            `r`.`image`,`r`.`status`, `u`.`user`
	FROM '.DB_PREFIX.'tracks k
	LEFT JOIN '.DB_PREFIX.'tracks_revs r
	ON (`k`.`id`=`r`.`addon_id`)
	LEFT JOIN '.DB_PREFIX.'users u
        ON (`k`.`uploader` = `u`.`id`)';
    $reqSql = sql_query($querySql);
    while($result = sql_next($reqSql))
    {
        if (!file_exists(UP_LOCATION.$result['fileid'].'.zip'))
        {
            echo '<span class="warming">'._('The following file could not be found:').' '.$result['fileid'].'.zip</span><br />';
            continue;
        }
	$writer->startElement('track');
        $writer->writeAttribute('name',$result['name']);
        $writer->writeAttribute('file',DOWN_LOCATION.'assets/'.$result['fileid'].'.zip');
        $writer->writeAttribute('arena',$result['arena']);
	$writer->writeAttribute('date',$result['date']);
	$writer->writeAttribute('uploader',$result['user']);
        $writer->writeAttribute('designer',$result['designer']);
	$writer->writeAttribute('image',DOWN_LOCATION.'images/'.$result['image']);
        $writer->writeAttribute('format',$result['format']);
        $writer->writeAttribute('revision',$result['revision']);
        $writer->writeAttribute('status',$result['status']);
        $writer->writeAttribute('size',filesize(UP_LOCATION.$result['fileid'].'.zip'));
	$writer->endElement();
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
