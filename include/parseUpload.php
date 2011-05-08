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

function parseUpload($file,$revision = false)
{
    if (!is_array($file))
    {
        echo '<span class="error">'._('Failed to upload your file.').'</span><br />';
        return false;
    }

    // Check for file upload errors
    switch ($file['error'])
    {
        default:
            echo '<span class="error">'._('Unknown file upload error.')."</span><br />";
            return false;
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
            echo '<span class="error">'._('Uploaded file is too large.')."</span><br />";
            return false;
        case UPLOAD_ERR_FORM_SIZE:
            echo '<span class="error">'._('Uploaded file is too large.')."</span><br />";
            return false;
        case UPLOAD_ERR_PARTIAL:
            echo '<span class="error">'._('Uploaded file is incomplete.')."</span><br />";
            return false;
        case UPLOAD_ERR_NO_FILE:
            echo '<span class="error">'._('No file was uploaded.')."</span><br />";
            return false;
        case UPLOAD_ERR_NO_TMP_DIR:
            echo '<span class="error">'._('There is no TEMP directory to store the uploaded file in.')."</span><br />";
            return false;
        case UPLOAD_ERR_CANT_WRITE:
            echo '<span class="error">'._('Unable to write uploaded file to disk.')."</span><br />";
            return false;
    }

    // Check file extension
    if (!preg_match('/\.zip$/i',$file['name']))
    {
        echo '<span class="error">'._('The file you uploaded was not the correct type.')."</span><br />";
        return false;
    }
    $fileext = 'zip';

    // Generate a unique file name for the uploaded file
    $fileid = uniqid(true);
    while (file_exists(UP_LOCATION.$fileid.'.'.$fileext))
        $fileid = uniqid();

    // Move the archive to a working directory
    mkdir(UP_LOCATION.'temp/'.$fileid);
    if (!move_uploaded_file($file['tmp_name'],UP_LOCATION.'temp/'.$fileid.'/'.$fileid.'.'.$fileext)) {
        echo '<span class="error">'._('Failed to move uploaded file.');
        return false;
    }

    // Extract archive
    switch ($fileext) {
        case 'zip':
            $archive = new ZipArchive;
            if (!$archive->open(UP_LOCATION.'temp/'.$fileid.'/'.$fileid.'.zip')) {
                echo '<span class="error">'._('Could not open archive file. It may be corrupted.').'</span><br />';
                unlink(UP_LOCATION.'temp/'.$fileid.'/'.$fileid.'.'.$fileext);
                rmdir(UP_LOCATION.'temp/'.$fileid);
                return false;
            }
            $archive->extractTo(UP_LOCATION.'temp/'.$fileid.'/');
            $archive->close();
            unlink(UP_LOCATION.'temp/'.$fileid.'/'.$fileid.'.zip');
            break;
        default:
            echo '<span class="error">'._('Unknown archive type.').'</span><br />';
            unlink(UP_LOCATION.'temp/'.$fileid.'/'.$fileid.'.'.$fileext);
            rmdir(UP_LOCATION.'temp/'.$fileid);
            return false;
            break;
    }

    // Find XML file
    $xml_file = find_xml(UP_LOCATION.'temp/'.$fileid);
    $xml_dir = dirname($xml_file);
    if (!$xml_file) {
        echo '<span class="error>'._('Invalid archive file.').'</span><br />';
        rmdir_recursive(UP_LOCATION.'temp/'.$fileid);
    }

    // Define addon type
    if (preg_match('/kart\.xml$/',$xml_file))
    {
        $addon_type = 'karts';
    }
    else
    {
        $addon_type = 'tracks';
    }

    // Read XML
    $parsed_xml = read_xml($xml_file,$addon_type);
    if (!$parsed_xml)
    {
        echo '<span class="error">'._('Failed to read the add-on\'s XML file. Please make sure you are using the latest version of the kart or track exporter.').'</span><br />';
        rmdir_recursive(UP_LOCATION.'temp/'.$fileid);
        return false;
    }
    // Write new XML file
    $fhandle = fopen($xml_file,'w');
    if (!fwrite($fhandle,$parsed_xml['xml'])) {
        echo '<span class="error">'._('Failed to write new XML file:').'</span><br />';
    }
    fclose($fhandle);

    // Check for valid license file
    if (!find_license(UP_LOCATION.'temp/'.$fileid))
    {
        echo '<span class="error">'._('A valid License.txt file was not found. Please add a License.txt file to your archive and re-submit it.').'</span><br />';
        rmdir_recursive(UP_LOCATION.'temp/'.$fileid);
        return false;
    }

    // Save addon icon or screenshot
    if ($addon_type == 'tracks')
    {
        $image_file = $xml_dir.'/'.$parsed_xml['attributes']['screenshot'];
    }
    else
    {
        $image_file = $xml_dir.'/'.$parsed_xml['attributes']['icon-file'];
    }
    // Check if file exists
    if (!file_exists($image_file))
    {
        $image_file = '';
    }
    // Get image file extension
    preg_match('/\.([a-z]+)$/i',$image_file,$imageext);
    // Save file
    copy($image_file,UP_LOCATION.'images/'.$fileid.'.'.$imageext[1]);
    $parsed_xml['attributes']['image'] = $fileid.'.'.$imageext[1];

    // Initialize the status flag
    $parsed_xml['attributes']['status'] = 0;

    // Check to make sure all image dimensions are powers of 2
    if (!image_check($xml_dir))
    {
        echo '<span class="warning">'._('Some images in this add-on do not have dimensions that are a power of two.')
            .' '._('This may cause display errors on some video cards.').'</span><br />';
        $parsed_xml['attributes']['status'] += F_TEX_NOT_POWER_OF_2;
    }

    // Check for invalid files
    $invalid_files = type_check($xml_dir);
    if (is_array($invalid_files) && count($invalid_files != 0))
    {
        echo '<span class="warning">'._('Some invalid files were found in the uploaded add-on. These files have been removed from the archive:').' '.implode(', ',$invalid_files).'</span><br />';
    }

    // Repack zip file
    if (!repack_zip($xml_dir,UP_LOCATION.$fileid.'.zip'))
    {
        echo '<span class="error">'._('Failed to re-pack archive file.').'</span>';
        rmdir_recursive(UP_LOCATION.'temp/'.$fileid);
        return false;
    }

    // Get addon id
    $addon_id = NULL;
    if (isset($_GET['name']))
        $addon_id = addon_id_clean($_GET['name']);
    if (!preg_match('/^[a-z0-9\-]+_?[0-9]*$/i',$addon_id) || $addon_id == NULL)
        $addon_id = generate_addon_id($addon_type,$parsed_xml['attributes']);

    // Set first revision to be "latest"
    if ($revision == false)
        $parsed_xml['attributes']['status'] += F_LATEST;

    // Create addon
    $addon = new coreAddon($addon_type);

    // Make sure only the original uploader can make a new revision
    if ($revision == true)
    {
        $addon->selectById($addon_id);
        if (!$addon->addonCurrent)
        {
            echo '<span class="error">'._('You are trying to add a new revision of an addon that does not exist.').'</span><br />';
            rmdir_recursive(UP_LOCATION.'temp/'.$fileid);
            return false;
        }
        if ($_SESSION['userid'] != $addon->addonCurrent['uploader']
                && !$_SESSION['role']['manageaddons'])
        {
            echo '<span class="error">'._('You do not have the necessary permissions to perform this action.').'</span><br />';
            rmdir_recursive(UP_LOCATION.'temp/'.$fileid);
            return false;
        }
    }

    if (!$addon->addAddon($fileid,$addon_id,$parsed_xml['attributes']))
    {
        echo '<span class="error">'._('Failed to create add-on.').'</span><br />';
    }
    rmdir_recursive(UP_LOCATION.'temp/'.$fileid);
    echo _('Successfully uploaded add-on.').'<br />';
    echo '<span style="font-size: large"><a href="addons.php?type='.$addon_type.'&amp;name='.$addon_id.'">'._('Continue.').'</a></span><br />';
}

function find_xml($dir)
{
    if(is_dir($dir))
    {
        foreach(scandir($dir) as $file)
        {
            if(is_dir($dir."/".$file) && $file != "." && $file != "..")
            {
                $name = find_xml($dir."/".$file);
                if($name != false)
                {
                    return $name;
                }
            }
            else if(file_exists($dir."/kart.xml"))
            {
                return $dir."/kart.xml";
            }
            else if(file_exists($dir."/track.xml"))
            {
                return $dir."/track.xml";
            }
        }
    }
    return false;
}

function find_license($dir)
{
    if(is_dir($dir))
    {
        foreach(scandir($dir) as $file)
        {
            // Check recursively
            if(is_dir($dir."/".$file) && $file != "." && $file != "..")
            {
                $name = find_license($dir."/".$file);
                // The file was found in a recursive lookup
                if($name != false)
                {
                    return $name;
                }
            }
            else if(file_exists($dir."/License.txt"))
            {
                return $dir."/License.txt";
            }
        }
    }
    return false;
}

function read_xml($file,$type)
{
    // Can't use XMLReader because we don't know the names of all our attributes
    $reader = xml_parser_create();

    // Remove whitespace at beginning and end of file
    $xmlContents = trim(file_get_contents($file));
    // Remove amperstands (&) because they cause problems
    $xmlContents = str_replace('& ','&amp; ',$xmlContents);

    if (!xml_parse_into_struct($reader,$xmlContents,$vals,$index))
    {
        echo 'XML Error: '.xml_error_string(xml_get_error_code($reader)).'<br />';
        return false;
    }

    // Set up the XMLWriter to modify the XML file
    $writer = new XMLWriter();
    $writer->openMemory();
    $writer->startDocument('1.0');
    $writer->setIndent(true);
    $writer->setIndentString('    ');

    $groups_found = false;
    $attributes = array();
    // Cycle through all of the xml file's elements
    foreach ($vals AS $val)
    {
        if ($val['type'] == 'close')
        {
            $writer->endElement();
            continue;
        }
        if ($val['type'] == 'open' || $val['type'] == 'complete')
            $writer->startElement(strtolower($val['tag']));
        if (isset($val['attributes']))
        {
            foreach ($val['attributes'] AS $attribute => $value)
            {
                // XML parser returns tag names in all uppercase
                if (strtolower($val['tag']).'s' == $type)
                {
                    $attribute = strtolower($attribute);
                    if ($attribute != 'groups')
                    {
                        $attributes[$attribute] = $value;
                    }
                    else
                    {
                        $attributes[$attribute] = 'Add-Ons';
                        $value = 'Add-Ons';
                    }
                }
                $writer->writeAttribute(strtolower($attribute),$value);
            }
        }
        if ($val['type'] == 'complete')
            $writer->endElement();
    }
    $writer->endDocument();
    $new_xml = $writer->flush();

    // Make sure certain attributes exist
    if (!array_key_exists('arena',$attributes))
        $attributes['arena'] = 0;
    if (!array_key_exists('designer',$attributes))
        $attributes['designer'] = '';

    return array('xml'=>$new_xml,'attributes'=>$attributes);
}

function rmdir_recursive($dir)
{
    if (is_dir($dir))
    {
        $dir = rtrim($dir, '/');
        $oDir = dir($dir);
        while (($sFile = $oDir->read()) !== false)
        {
            if ($sFile != '.' && $sFile != '..')
            {
                (!is_link("$dir/$sFile") && is_dir("$dir/$sFile")) ? rmdir_recursive("$dir/$sFile") : unlink("$dir/$sFile");
            }
        }
        $oDir->close();
        rmdir($dir);
        return true;
    }
    return false;
}

function generate_addon_id($type,$attb)
{
    if (!is_array($attb))
        return false;
    if (!array_key_exists('name',$attb))
        return false;

    $addon_id = addon_id_clean($attb['name']);
    if (!$addon_id)
        return false;

    // Check database
    while(sql_exist($type, "id", $addon_id))
    {
        if (preg_match('/^.+_([0-9]+)$/i', $addon_id, $matches))
        {
            $next_num = (int)$matches[1];
            $next_num++;
            $addon_id = str_replace($matches[1],$next_num,$addon_id);
        }
        else
        {
            $addon_id .= '_1';
        }
    }
    return $addon_id;
}

function image_check($path)
{
    if (!file_exists($path))
        return false;
    if (!is_dir($path))
        return false;
    // Check supported image types
    $imagetypes = imagetypes();
    $imageFileExts = array();
    if ($imagetypes & IMG_GIF)
        $imageFileExts[] = 'gif';
    if ($imagetypes & IMG_PNG)
        $imageFileExts[] = 'png';
    if ($imagetypes & IMG_JPG)
    {
        $imageFileExts[] = 'jpg';
        $imageFileExts[] = 'jpeg';
    }
    if ($imagetypes & IMG_WBMP)
        $imageFileExts[] = 'wbmp';
    if ($imagetypes & IMG_XPM)
        $imageFileExts[] = 'xpm';


    foreach (scandir($path) AS $file)
    {
        // Don't check current and parent directory
        if ($file == '.' || $file == '..')
            continue;
        // Make sure the whole path is there
        $file = $path.'/'.$file;
        // Dig into deeper directories
        if (is_dir($file)) {
            if (!image_check($file))
                return false;
            continue;
        }
        // Don't check files that aren't images
        if (!preg_match('/\.('.implode('|',$imageFileExts).')$/i',$file))
            continue;

        // If we're still in the loop, there is an image to check
        $image_size = getimagesize($file);
        // Make sure dimensions are powers of 2
        if (($image_size[0] & ($image_size[0]-1)) || ($image_size[0] <= 0))
            return false;
        if (($image_size[1] & ($image_size[1]-1)) || ($image_size[1] <= 0))
            return false;
    }


    return true;
}

function type_check($path)
{
    if (!file_exists($path))
        return false;
    if (!is_dir($path))
        return false;
    // Make a list of approved file types
    // FIXME: Don't hardcode this
    $approved_types = array('txt','b3d','xml','png','jpg','jpeg','music','ogg');
    $removed_files = array();

    foreach (scandir($path) AS $file)
    {
        // Don't check current and parent directory
        if ($file == '.' || $file == '..')
            continue;
        // Make sure the whole path is there
        $file = $path.'/'.$file;
        // Dig into deeper directories
        if (is_dir($file))
        {
            $dir_result = type_check($file);
            if (is_array($dir_result))
            {
                foreach ($dir_result AS $result)
                {
                    $removed_files[] = $result;
                }
            }
            continue;
        }
        // Remove files with unapproved extensions
        if (!preg_match('/\.('.implode('|',$approved_types).')$/i',$file))
        {
            $removed_files[] = basename($file);
            unlink($file);
        }
    }
    if (count($removed_files) == 0)
        return true;
    return $removed_files;
}

function repack_zip($path_zip, $to)
{
    $zip = new ZipArchive();
    $filename = $to;

    if(file_exists($filename))
        unlink($filename);

    if ($zip->open($filename, ZIPARCHIVE::CREATE)!==TRUE)
    {
        echo("Cannot open <$filename>\n");
        return false;
    }
    repack_internal($zip, $path_zip);
    $succes = $zip->close();
    if(!$succes)
    {
        echo "Can't close the zip\n";
        return false;
    }
    return true;
}

function repack_internal($zip, $path_zip)
{
    foreach(scandir($path_zip) as $file)
    {
        if($file == ".." || $file == ".")
            continue;
        if(is_dir($path_zip."/".$file))
        {
            // Skip over .svn directories that may exist
            if (preg_match('/\.svn$/i',$path_zip.'/'.$file))
                continue;
            repack_internal($zip, $path_zip."/".$file);
        }
        else if(!$zip->addFile($path_zip."/".$file, $file))
        {
            echo "Can't add this file: ".$file."\n";
            return false;
        }
        if(!file_exists($path_zip."/".$file))
        {
            echo "Can't add this file (it doesn't exist): ".$file."\n";
            return false;
        }
    }
}
?>
