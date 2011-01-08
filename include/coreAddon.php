<?php
/* copyright 2010 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
 This file is part of stkaddons.                                 
                                                                              
 stkaddons is free software: you can redistribute it and/or      
 modify it under the terms of the GNU General Public License as published by  
 the Free Software Foundation, either version 3 of the License, or (at your   
 option) any later version.                                                   
                                                                              
 stkaddons is distributed in the hope that it will be useful, but
 WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for    
 more details.                                                                
                                                                              
 You should have received a copy of the GNU General Public License along with 
 stkaddons.  If not, see <http://www.gnu.org/licenses/>.   */

include_once(ROOT."config.php");
include_once(ROOT."include/coreUser.php");
class coreAddon
{
    //e.g. karts or tracks
    var $addonType;
    var $reqSql;
    var $addonCurrent;

    function coreAddon($type)
    {
        $this->addonType = $type;
    }

    function selectById($id)
    {
        $this->reqSql = sql_get_all_where($this->addonType, 'id', $id);
        $this->addonCurrent = sql_next($this->reqSql);
    }

    function selectByUser($id)
    {
        $this->reqSql = sql_get_all_where($this->addonType, "user", $id);
    }

    function loadAll()
    {
        $this->reqSql = sql_get_all($this->addonType);
        return false != $this->reqSql;
    }

    function next()
    {
        $succes = true;
        $this->addonCurrent = sql_next($this->reqSql);
        if(!$this->addonCurrent)
        {
            $succes = false;
        }
        return $succes;
    }

    function setAvailable()
    {
        global $USER_LOGGED;
        if($USER_LOGGED && $_SESSION['range']['manageaddons'] == true)
        {
            /* if the addons is already available, we want to deactivate it :
                $is_available = abs(1 - 1) = 0
               else, it isn't and we want to activate it:
                $is_available = abs(0 - 1) = 1
             */
            $is_available = abs($this->addonCurrent['available'] - 1);
            sql_update($this->addonType, "id",
                       $this->addonCurrent['id'],
                       "available",
                       $is_available);
            return true;
        }
        else
        {
            return false;
        }
    }

    function setFile($filetype = "image")
    {
        if($_SESSION['range']['manageaddons'] == true || $this->addonCurrent['user'] == $_SESSION['id'])
        {
            if (isset($_FILES['fileSend']))
            {
                $file_path = UP_LOCATION.$_POST['fileType'].'/'.$this->addonCurrent[$filetype];
        		if(file_exists($file_path))
                {
                    /* Remove the existing file before copy the new one. */
                    /* FIXME: is it really needed? */
                    unlink($file_path);
                }
                /* Move the file which has been sent to it permanent location. */
                move_uploaded_file($_FILES['fileSend']['tmp_name'], $file_path);
            }
        }
    }

    /** Set an information of the addon.
        \param $info The name of the information (e.g. 'name', 'version')
        \param $value The new value of the information (e.g. 'Tux', 'Adiumy')
    */
    function setInformation($info, $value)
    {
        global $USER_LOGGED;
        if($USER_LOGGED && $_SESSION['range']['manageaddons'] == true || $this->addonCurrent['user'] == $_SESSION['id'])
        {
            if(sql_exist("properties", "name", $info))
            {
                $propertie_sql = sql_get_all_where("properties", "name", $info);
                $propertie = sql_next($propertie_sql);
                if($propertie["lock"] != 1)
                {
                    if($propertie['typefield'] == "file")
                    {
                        $this->setFile(post('fileType'));
                    }
                    else
                    {
                        sql_update($this->addonType, "id", $this->addonCurrent['id'], $propertie['name'], $value);

                    }
                    return true;
                }
            }
            if(!defined("UNIT_TEST"))
                echo "Error, I can't find this property.";
        }
        return false;
    }

    /** Remove the selected addons. */
    function remove()
    {
        if($_SESSION['range']['manageaddons'] == true)
        {
            sql_remove_where($this->addonType, "id", $this->addonCurrent['id']);
            return true;
        }
        else
        {
            return false;
        }
    }

    /** Print the information of the addon, it name, it description, it
      * version...
      */
    function writeInformations()
    {
        global $dirDownload, $dirUpload;
        //div for jqery TODO:add jquery effects
        ?>
        <div id="accordion">
        <div>
        <img class="preview" src="image.php?type=big&amp;pic=<?=UP_LOCATION.'image/'.$this->addonCurrent['image']?>" />
        <table>
            <tr>
                <td>
                    <span class="addon_informations_field" id="addons_informations_name">
                        <?=_("Name:")?>
                    </span>
                </td>
                <td>
                    <?=$this->addonCurrent['name']?>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="addon_informations_field" id="addons_informations_description">
                        <?=_("Description:")?>
                    </span>
                </td>
                <td>
                    <?=bbc($this->addonCurrent['Description'])?>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="addon_informations_field" id="addons_informations_revision">
                        <?=_("Revision:")?>
                    </span>
                </td>
                <td>
                    <?=$this->addonCurrent['version']?>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="addon_informations_field" id="addons_informations_stkversion">
                        <?=_("Version of STK:")?>
                    </span>
                </td>
                <td>
                    <?=$this->addonCurrent['STKVersion']?>
                    <?php
                    //load class user
                    $user = new coreUser('users');
                    
                    //select submiter of addons TODO:add author 
                    $user->selectById($this->addonCurrent['user']);
                    
                    ?>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="addon_informations_field" id="addons_informations_name">
                        <?=_("Author:")?>
                    </span>
                </td>
                    <?php
                    if($this->addonCurrent['Author'] != "")
                    {
                    ?>
                <td>
                    <?=bbc($this->addonCurrent['Author'])?>
                </td>
            </tr>
            <tr>
                        <?php
                        echo _("Submitter:");
                    }
                ?>
                <td>
                    <a href="account.php?title=<?=$user->addonCurrent['login']?>"><?=$user->addonCurrent['login']?></a>
                </td>
            </tr>
        </table>
        </div>

        <a href="<?=DOWN_LOCATION.'file/'.$this->addonCurrent['file']?>"><img src="image/download.png" alt="Download" title="Download" /></a>

        <br /><br /><b>Permalink :</b>
        http://<?=$_SERVER['SERVER_NAME'].str_replace("addon.php", "addon-view.php", $_SERVER['SCRIPT_NAME']).'?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['name']?>
        <?php
    }

    /* FIXME: this function needs a lot of cleanup. */
    function writeConfig()
    {
        global $dirDownload, $dirUpload;
        echo '<hr /><h3>Configuration</h3>';
        ?>
        <div class="help-hidden">
            <span class="help-hidden">Help</span>
            <div>
                BBCode:
                <br />strong : [b]....[/b]
                <br />italic : [i]....[/i]
            </div>
        </div>
        <form action="#" method="GET" >
        <?php
        $propertie_sql = mysql_query("SELECT * FROM properties WHERE `properties`.`type` = '".$this->addonType."' AND `properties`.`lock` != 1;");
        $file_str = "";
        while($propertie = mysql_fetch_array($propertie_sql))
        {
            $cible = 'addonRequest(\'addon.php?type='.$this->addonType.'&amp;action='.str_replace(" ", "", $propertie['name']).'\', '.$this->addonCurrent['id'].',document.getElementById(\''.strtolower(str_replace(" ", "", $propertie['name'])).'\').value)';
            if($propertie['typefield'] == "textarea")
            {
                echo "<br />".$propertie['name']." :<br />";
                echo '<textarea cols="65" rows="8" id="'.strtolower(str_replace(" ", "", $propertie['name'])).'">'.$this->addonCurrent[str_replace(" ", "", $propertie['name'])].'</textarea><br />';
                echo '<input onclick="'.$cible.'" value="Change '.$propertie['name'].'" type="button" />';
            }
            elseif($propertie['typefield'] == "text")
            {
                echo "</form><br />".$propertie['name']." :<br />";
                echo '<form action="javascript:'.$cible.'" method="GET" >';
                echo '<input type="text" id="'.strtolower(str_replace(" ", "", $propertie['name'])).'" value="'.$this->addonCurrent[str_replace(" ", "", $propertie['name'])].'" ><br />';
                echo '<input onclick="'.$cible.'" value="Change '.$propertie['name'].'" type="button" />';
                echo "</form>";
                echo '<form action="#" method="GET" >';
            }
            elseif($propertie['typefield'] == "enum")
            {
                echo "<br />".$propertie['name']." :<br />";
                echo '<select onchange="addonRequest(\'addon.php?type='.$this->addonType.'&amp;action='.$propertie['name'].'\', '.$this->addonCurrent['id'].', this.value)">';
                
                $values = explode("\n", $propertie['default']);
                foreach($values as $value)
                {
                    echo '<option value="'.$value.'"';
                    if($this->addonCurrent[str_replace(" ", "", $propertie['name'])]==$value) echo 'selected="selected" ';
                    echo '>'.$value.'</option>';
                }
                echo '</select>';
            }
            elseif($propertie['typefield'] == "file")
            {
                $file_str .='<option value="'.strtolower(str_replace(" ", "", $propertie['name'])).'">'.$propertie['name'].'</option>';
            }
        }
        echo '</form>';
        echo '<form id="formKart" enctype="multipart/form-data" action="addon.php?action=file&amp;type='.$this->addonType.'&amp;id='.$this->addonCurrent['id'].'" method="POST">
        <select name="fileType">';
        echo $file_str;
        echo '</select>
        <input type="file" name="fileSend"/>
        <input type="submit"/>
        </form>';
        if($_SESSION['range']['manageaddons'])
        {
            echo '<form action="#"><input  onchange="addonRequest(\'addon.php?type='.$this->addonType.'&amp;action=available\', '.$this->addonCurrent['id'].')" type="checkbox" name="available" id="available"';
            if($this->addonCurrent['available'] ==1)
            {
                echo 'checked="checked" ';
            }
            echo '/><label for="available">Available</label><br />';
            echo '<input type="button" onclick="verify(\'addonRequest(\\\'addon.php?type='.$this->addonType.'&amp;action=remove\\\', '.$this->addonCurrent['id'].')\')" value="Remove" /><br /></form>';
        }
    }

    function viewInformations($config=True)
    {
        global $USER_LOGGED;
        $this->writeInformations();
        //write configuration for the submiter and administrator
        if($USER_LOGGED && ($_SESSION['range']['manageaddons'] || $this->addonCurrent['user'] == $_SESSION['id']) and $config)
        {
            $this->writeConfig();
        }
    }

    /* FIXME: please cleanup me! */
    /* FIXME: this function needs a _lot_ of a tests. */
    function addAddon($name, $description)
    {   
        global $USER_LOGGED;

        if(!sql_exist($this->addonType, "name", $name) && $USER_LOGGED)
        {
            if(isset($_FILES['file_addon']) and $_FILES['file_addon']['type'] == "application/zip")
            {
                $zip_path = zip_path($name);
                if(defined("UNIT_TEST"))
                {
                    $zip_path = "./test.zip";
                }
                if(!defined("UNIT_TEST"))
                {
                    move_uploaded_file($_FILES['file_addon']['tmp_name'], $zip_path."-uploaded.zip");
                }
                $info = read_info_from_zip($zip_path."-uploaded.zip");
                repack_zip($zip_path."-uploaded.zip-extract", $zip_path);
                sql_insert($this->addonType, array('user',
                                                   'name',
                                                   'Description',
                                                   'file',
                                                   'image',
                                                   'date',
                                                   'STKVersion',
                                                   'Author',
                                                   'available'),
                                             array($_SESSION["id"],
                                                   $info["name"],
                                                   $info["description"],
                                                   $info["name"].".zip",
                                                   $info["name"].".png",
                                                   date("Y-m-d"),
                                                   $info["version"],
                                                   $info["designer"],
                                                   0));
                $this->reqSql = sql_get_all_where($this->addonType, "name", $info["name"]);
                $this->addonCurrent = sql_next($this->reqSql);
            }
            else
            {
                echo _("Please re-upload your file. It must be a zip.")."<br />\n";
                return false;
            }
            
            return true;
        }
        else
        {
            return false;
        }
    }

    /** To get the permanent link of the current addon */ 
    function permalink()
    {
        return 'addon-view.php?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['name'];
    }
}

/* Utilities to generate paths */
function image_path($name)
{
    return UP_LOCATION."image/".$name.".png";
}

function zip_path($name)
{
    return UP_LOCATION."file/".$name.".zip";
}

function read_info_from_zip($path_zip)
{
    /*$zip = zip_open($path_zip);*/
    $zip = new ZipArchive;
    $addon_information = array();
    $addon_information["description"] = "";
    $attribute = array("name",
                       "version",
                       "groups",
                       "model-file",
                       "icon-file",
                       "minimap-icon-file",
                       "shadow-file",
                       "rgb",
                       "left",
                       "right",
                       "straight",
                       "right",
                       "start-winning",
                       "end-winning",
                       "start-losing",
                       "start-losing-loop",
                       "end-losing",
                       "position",
                       "model",
                       "designer",
                       "music",
                       "screenshot");
    if ($zip->open($path_zip) === TRUE)
    {
        if(!file_exists($path_zip."-extract"))
            mkdir($path_zip."-extract");
        $zip->extractTo($path_zip."-extract");
        $zip->close();
        if(file_exists($path_zip."-extract"."/kart.xml"))
        {

            $reader = new XMLReader();

            $reader->open($path_zip."-extract"."/kart.xml");
            $writer = new XMLWriter();
            $writer->openURI('file://'.realpath($path_zip."-extract"."/kart.xml"));
            $writer->startDocument("1.0");
            $writer->setIndent(true);
            while ($reader->read())
            {
                if ($reader->nodeType == XMLREADER::ELEMENT)
                {
                    $elm = $reader->name;
                    $writer->startElement($elm);
                    foreach($attribute as $attr)
                    {
                        $value = $reader->getAttribute($attr);
                        if($reader->getAttribute($attr) == "groups")
                        {
                            $writer->startAttribute($attr);
                            $writer->text("addons");
                            $writer->endAttribute();
                        }
                        elseif($reader->getAttribute($attr) != "")
                        {
                            $writer->startAttribute($attr);
                            $writer->text($reader->getAttribute($attr));
                            $writer->endAttribute();
                        }
                        if($elm == "kart" or $elm == "track")
                        {
                            $addon_information[$attr] = $value;
                        }
                    }
                    if(!($elm == "kart" or $elm == "track" or $elm == "wheels"))
                    {
                        $writer->endElement();
                    }
                }
                elseif($reader->nodeType == XMLREADER::END_ELEMENT)
                {
                    $writer->endElement();
                }
            }
            $writer->flush();
            return $addon_information;
        }
        else
        {
            return null;
        }
    }
    else
    {
        return null;
    }
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
    foreach(scandir($path_zip) as $file)
    {
        if($file != ".." and $file != ".")
        {
            if(!$zip->addFile($path_zip."/".$file, $file))
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
    $succes = $zip->close();
    if(!$succes)
    {
        echo "Can't close the zip\n";
        return false;
    }   
    return true;
}
?>
