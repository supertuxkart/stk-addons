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
        throw new UploadException(htmlspecialchars(_('Failed to upload your file.')));

    // This won't be set when uploading addons/revisions
    if (!isset($_POST['upload-type'])) $_POST['upload-type'] = NULL;

    // Check for file upload errors and check file extension
    File::checkUploadError($file['error']);
    $fileext = File::checkUploadExtension($file['name'], $_POST['upload-type']);

    // Set upload directory
    $file_dir = ($_POST['upload-type'] == 'image') ? 'images/' : NULL;

    // Generate a unique file name for the uploaded file
    $fileid = uniqid();
    while (file_exists(UP_LOCATION.$file_dir.$fileid.'.'.$fileext)) {
        $fileid = uniqid();
    }
    
    // Handle image uploads
    if ($_POST['upload-type'] == 'image') {
        try {
            $addon_id = Addon::cleanId($_GET['name']);
            $addon_type = mysql_real_escape_string($_GET['type']);
            File::newImage($file, $fileid.'.'.$fileext, $addon_id, $addon_type);
            echo htmlspecialchars(_('Successfully uploaded image.')).'<br />';
            echo '<span style="font-size: large"><a href="addons.php?type='.$_GET['type'].'&amp;name='.$_GET['name'].'">'.htmlspecialchars(_('Continue.')).'</a></span><br />';
            return true;
        }
        catch (FileException $e) {
            throw new UploadException($e->getMessage());
        }
    }
    
    // Move the archive to a working directory
    mkdir(CACHE_DIR.$fileid);
    if (!move_uploaded_file($file['tmp_name'],CACHE_DIR.$fileid.'/'.$fileid.'.'.$fileext))
        throw new UploadException(htmlspecialchars(_('Failed to move uploaded file.')));

    // Extract archive
    try {
        File::extractArchive(CACHE_DIR.$fileid.'/'.$fileid.'.'.$fileext,
            CACHE_DIR.$fileid.'/',
            $fileext);
    }
    catch (FileException $e) {
        File::deleteRecursive(CACHE_DIR.$fileid);
        throw new UploadException($e->getMessage());
    }

    // Find XML file
    if ($_POST['upload-type'] != 'source')
    {
        $xml_file = find_xml(UP_LOCATION.'temp/'.$fileid);
        $xml_dir = dirname($xml_file);
        if (!$xml_file) {
            File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
            throw new UploadException(htmlspecialchars(_('Invalid archive file. The archive must contain the addon\'s xml file.')));
        }
    }

    // Check for invalid files
    if ($_POST['upload-type'] != 'source')
        $invalid_files = type_check($xml_dir);
    else
    {
        $xml_dir = UP_LOCATION.'temp/'.$fileid;
        $invalid_files = type_check($xml_dir, true);
    }
    if (is_array($invalid_files) && count($invalid_files != 0))
    {
        echo '<span class="warning">'.htmlspecialchars(_('Some invalid files were found in the uploaded add-on. These files have been removed from the archive:')).' '.implode(', ',$invalid_files).'</span><br />';
    }

    if ($_POST['upload-type'] != 'source')
    {
        // Define addon type
        if (preg_match('/kart\.xml$/',$xml_file))
        {
            $addon_type = 'karts';
            echo htmlspecialchars(_('Upload was recognized as a kart.')).'<br />';
        }
        else
        {
            $addon_type = 'tracks';
            echo htmlspecialchars(_('Upload was recognized as a track.')).'<br />';
        }

        // Read XML
        $parsed_xml = read_xml($xml_file,$addon_type);
        if (!$parsed_xml)
        {
            File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
            throw new UploadException(htmlspecialchars(_('Failed to read the add-on\'s XML file. Please make sure you are using the latest version of the kart or track exporter.')));
        }
        // Write new XML file
        $fhandle = fopen($xml_file,'w');
        if (!fwrite($fhandle,$parsed_xml['xml'])) {
            echo '<span class="error">'.htmlspecialchars(_('Failed to write new XML file:')).'</span><br />';
        }
        fclose($fhandle);
        
        // Handle arenas
        if ($parsed_xml['attributes']['arena'] == 'Y') {
            echo htmlspecialchars(_('This track is an arena.')).'<br />';
            $addon_type = 'arenas';
        }

        // Check for valid license file
        $license_file = find_license(UP_LOCATION.'temp/'.$fileid);
        if ($license_file === false)
        {
            File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
            throw new UploadException(htmlspecialchars(_('A valid License.txt file was not found. Please add a License.txt file to your archive and re-submit it.')));
        }
        $parsed_xml['attributes']['license'] = $license_file;

        // Get addon id
        $addon_id = NULL;
        if (isset($_GET['name'])) {
            $addon_id = Addon::cleanId($_GET['name']);
            if (!Addon::exists($addon_id))
                $addon_id = NULL;
        }
        if (!preg_match('/^[a-z0-9\-]+_?[0-9]*$/i',$addon_id) || $addon_id == NULL)
            $addon_id = Addon::generateId($addon_type,$parsed_xml['attributes']['name']);

        // Save addon icon or screenshot
        if ($addon_type == 'karts')
        {
            $image_file = $xml_dir.'/'.$parsed_xml['attributes']['icon-file'];
        }
        else
        {
            $image_file = $xml_dir.'/'.$parsed_xml['attributes']['screenshot'];
        }
        // Check if file exists
        if (!file_exists($image_file))
        {
            $image_file = false;
        }
        if ($image_file !== false) {
            // Get image file extension
            preg_match('/\.([a-z]+)$/i',$image_file,$imageext);
            // Save file
            copy($image_file,UP_LOCATION.'images/'.$fileid.'.'.$imageext[1]);
            $parsed_xml['attributes']['image'] = $fileid.'.'.$imageext[1];

            // Record image file in database
            $newImageQuery = 'CALL `'.DB_PREFIX.'create_file_record` '.
                "('$addon_id','$addon_type','image','images/$fileid.{$imageext[1]}',@a)";
            $newImageHandle = sql_query($newImageQuery);
            if (!$newImageHandle)
            {
                echo '<span class="error">'.htmlspecialchars(_('Failed to associate image file with addon.')).mysql_error().'</span><br />';
                unlink(UP_LOCATION.'images/'.$fileid.'.'.$imageext[1]);
                $parsed_xml['attributes']['image'] = 0;
            }
            else
            {        
                $getInsertIdQuery = 'SELECT @a';
                $getInsertIdHandle = sql_query($getInsertIdQuery);
                if (!$getInsertIdHandle) $parsed_xml['attributes']['fileid'] = 0;
                $iid_result = mysql_fetch_array($getInsertIdHandle);
                // Get ID of previously inserted image
                $parsed_xml['attributes']['image'] = $iid_result[0];
            }
        }

        // Initialize the status flag
        $parsed_xml['attributes']['status'] = 0;

        // Check to make sure all image dimensions are powers of 2
        if (!image_check($xml_dir))
        {
            echo '<span class="warning">'.htmlspecialchars(_('Some images in this add-on do not have dimensions that are a power of two.'))
                .' '.htmlspecialchars(_('This may cause display errors on some video cards.')).'</span><br />';
            $parsed_xml['attributes']['status'] += F_TEX_NOT_POWER_OF_2;
        }
        
        $filetype = 'addon';
    }
    else
    {
        $addon_id = Addon::cleanId($_GET['name']);
        $addon_type = mysql_real_escape_string($_GET['type']);
        $filetype = 'source';
    }

    // Validate addon type field
    if (!Addon::isAllowedType($addon_type))
    {
        File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
        throw new UploadException(htmlspecialchars(_('Invalid add-on type.')));
    }

    // Repack zip file
    if (!repack_zip($xml_dir,UP_LOCATION.$fileid.'.zip'))
    {
        File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
        throw new UploadException(htmlspecialchars(_('Failed to re-pack archive file.')));
    }
    
    // Record addon's file in database
    $newAddonFileQuery = 'CALL `'.DB_PREFIX.'create_file_record` '.
        "('$addon_id','$addon_type','$filetype','$fileid.zip',@a)";
    $newAddonFileHandle = sql_query($newAddonFileQuery);
    if (!$newAddonFileHandle)
    {
        echo '<span class="error">'.htmlspecialchars(_('Failed to associate archive file with addon.')).'</span><br />';
        unlink(UP_LOCATION.$fileid.'.zip');
        if ($_POST['upload-type'] != 'source')
            $parsed_xml['attributes']['fileid'] = 0;
    }
    else
    {
        $getInsertIdQuery = 'SELECT @a';
        $getInsertIdHandle = sql_query($getInsertIdQuery);
        if (!$getInsertIdHandle) $parsed_xml['attributes']['fileid'] = 0;
        $iid_result = mysql_fetch_array($getInsertIdHandle);
        // Get ID of previously inserted file
        if ($_POST['upload-type'] != 'source')
            $parsed_xml['attributes']['fileid'] = $iid_result[0];
    }
    
    if ($_POST['upload-type'] == 'source')
    {
        File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
        echo htmlspecialchars(_('Successfully uploaded source archive.')).'<br />';
        echo '<span style="font-size: large"><a href="addons.php?type='.$addon_type.'&amp;name='.$addon_id.'">'.htmlspecialchars(_('Continue.')).'</a></span><br />';
        return true;
    }

    // Set first revision to be "latest"
    if ($revision == false)
        $parsed_xml['attributes']['status'] += F_LATEST;

    try {
        if (!Addon::exists($addon_id)) {
            // Check if we were trying to add a new revision
            if ($revision == true) {
                File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
                throw new UploadException(htmlspecialchars(_('You are trying to add a new revision of an add-on that does not exist.')));
            }
            $addon = Addon::create($addon_type, $parsed_xml['attributes'], $fileid);
        } else {
            $addon = new Addon($addon_id);
            // Check if we are the original uploader, or a moderator
            if (User::$user_id != $addon->getUploader() && !$_SESSION['role']['manageaddons']) {
                File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
                throw new UploadException(htmlspecialchars(_('You do not have the necessary permissions to perform this action.')));
            }
            $addon->createRevision($parsed_xml['attributes'], $fileid);
        }
        
        try {
            // If the add-on is a track, add an image of the driveline
            if ($addon_type == 'tracks') {
                if (file_exists($xml_dir.'/quads.xml')) {
                    File::newImageFromQuads($xml_dir.'/quads.xml', $addon_id, $addon_type);
                }
            }
        }
        catch (FileException $e) {
            echo '<span class="warning">Error interpreting quads: '.$e->getMessage().'</span><br />';
        }
    }
    catch (AddonException $e) {
        echo '<span class="error">'.$e->getMessage().'</span><br />';
    }
    
    File::deleteRecursive(UP_LOCATION.'temp/'.$fileid);
    echo htmlspecialchars(_('Your add-on was uploaded successfully. It will be reviewed by our moderators before becoming publicly available.')).'<br /><br />';
    echo '<a href="upload.php?type='.$addon_type.'&amp;name='.$addon_id.'&amp;action=file">'.htmlspecialchars(_('Click here to upload the sources to your add-on now.')).'</a><br />';
    echo htmlspecialchars(_('(Uploading the sources to your add-on enables others to improve your work and also ensure your add-on will not be lost in the future if new SuperTuxKart versions are not compatible with the current format.)')).'<br /><br />';
    echo '<a href="addons.php?type='.$addon_type.'&amp;name='.$addon_id.'">'.htmlspecialchars(_('Click here to view your add-on.')).'</a><br />';
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
            else if(file_exists($dir.'/License.txt'))
            {
                return file_get_contents($dir.'/License.txt');
            }
            else if (file_exists($dir.'/license.txt'))
            {
                return file_get_contents($dir.'/license.txt');
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
        $attributes['arena'] = '0';
    if (!array_key_exists('designer',$attributes))
        $attributes['designer'] = '';

    return array('xml'=>$new_xml,'attributes'=>$attributes);
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

function type_check($path, $source = false)
{
    if (!file_exists($path))
        return false;
    if (!is_dir($path))
        return false;
    // Make a list of approved file types
    if ($source === false)
        $approved_types = ConfigManager::get_config('allowed_addon_exts');
    else
        $approved_types = ConfigManager::get_config('allowed_source_exts');
    $approved_types = explode(',',$approved_types);
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
            $dir_result = type_check($file, $source);
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
