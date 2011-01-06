<?php
/* copyright 2009 Lucas Baudin <xapantu@gmail.com>                 
                                                                              
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
?>
<?php
/***************************************************************************
Project: STK Addon Manager

File: coreAddon.php
Version: 1
Licence: GPLv3
Description: file where all fonctions are

***************************************************************************/
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
        }
    }

    function setFile()
    {
        if($_SESSION['range']['manageaddons'] == true || $this->addonCurrent['user'] == $_SESSION['id'])
        {
            if (isset($_FILES['fileSend']))
            {
                echo $_POST['fileType'];
                $file_path = UP_LOCATION.$_POST['fileType'].'/'.$this->addonCurrent[post('fileType')];
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
        if($_SESSION['range']['manageaddons'] == true || $this->addonCurrent['user'] == $_SESSION['id'])
        {
            $propertie_sql = mysql_query("SELECT *
                                          FROM properties
                                          WHERE `properties`.`type` = '".$this->addonType."'
                                          AND `properties`.`lock` != 1
                                          AND `properties`.`name` = '".$info."';");
            if($propertie = mysql_fetch_array($propertie_sql))
            {
                if($propertie['typefield'] == "file")
                {
                    $this->setFile();
                }
                else
                {
                    sql_update($this->addonType, "id", $this->addonCurrent['id'], $propertie['name'], $value);

                }
            }
            else
            {
                echo "Error, I can't find this property.<br />";
            }
        }
    }

    /** Remove the selected addons. */
    function remove()
    {
        global $base;
        if($_SESSION['range']['manageaddons'] == true)
        {
            sql_remove_where($this->addonType, "id", $this->addonCurrent['id']);
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
                    <span id="addons_informations_name">
                        <?=_("Name :")?>
                    </span>
                </td>
                <td>
                    <?=$this->addonCurrent['name']?>
                </td>
            </tr>
            <tr>
                <td>
                    <span id="addons_informations_description">
                        <?=_("Description :")?>
                    </span>
                </td>
                <td>
                    <?=bbc($this->addonCurrent['Description'])?>
                </td>
            </tr>
            <tr>
                <td>
                    <span id="addons_informations_revision">
                        <?=_("Revision :")?>
                    </span>
                </td>
                <td>
                    <?=$this->addonCurrent['version']?>
                </td>
            </tr>
            <tr>
                <td>
                    <span id="addons_informations_stkversion">
                        <?=_("Version of STK :")?>
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
                    <span id="addons_informations_name">
                        <?php
                        if($this->addonCurrent['Author'] == "")
                        {
                            echo _("Author :");
                        }
                        else
                        {
                            echo _("Submitter :");
                        }
                        ?>
                    </span>
                </td>
                <td>
                    <a href="account.php?title=<?=$user->addonCurrent['login']?>"><?=$user->addonCurrent['login']?></a>
                </td>
            </tr>
            <?php
            if($this->addonCurrent['Author'] != "")
            {
                echo '<tr><td><b>';
                echo _("Author :");
                echo ' </b></td><td>'.bbc($this->addonCurrent['Author']).'</td></tr>';
            }
            ?>
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
                    
                    $values =explode("\n", $propertie['default']);
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
        if($USER_LOGGED && ($_SESSION['range']['manageaddons']|| $this->addonCurrent['user'] == $_SESSION['id']) and $config)
        {
            $this->writeConfig();
        }
    }

    /* FIXME: please cleanup me! */
    function addAddon($kartName, $kartDescription)
    {   
        global $base, $dirUpload;
        echo '<div id="content">';
        $existSql= mysql_query("SELECT * FROM `".$this->addonType."` WHERE `".$this->addonType."`.`name` = '".$kartName."'");
        $exist =true;
        $sql =  mysql_fetch_array($existSql) or $exist = false;
        if($exist == false && $kartName != null)
        {
            mysql_query("INSERT INTO `".DB_NAME."`.`".$this->addonType."` (`user` ,`name` ,`Description` ,`file`, `icon`, `image`, `date` ,`available`) 
                         VALUES ('".$_SESSION["id"]."', '".$kartName."', '".$kartDescription."', '".$kartName.".zip"."', '".$kartName.".png"."', '".$kartName.".png"."', '".date("Y-m-d")."', '1');") or die(mysql_error());
            if (isset($_FILES['icon']) && $_FILES['icon']['type'] == "image/png")
            {
                $chemin_destination = $dirUpload.'icon/';
                move_uploaded_file($_FILES['icon']['tmp_name'], $chemin_destination.$kartName.".png");

            }
            elseif($this->addonType=="karts")
            {
                echo _("Please re-upload your icon. It must be a png.")."<br />";
            }
            if (isset($_FILES['image']) && $_FILES['image']['type'] == "image/png")
            {
                $chemin_destination = $dirUpload.'image/';
                move_uploaded_file($_FILES['image']['tmp_name'], $chemin_destination.$kartName.".png");
            }
            elseif($this->addonType!="blender")
            {
                echo _("Please re-upload your image. It must be a png.")."<br />";
            }
            if (isset($_FILES['file_addon']) and $_FILES['file_addon']['type'] == "application/zip") {
                $chemin_destination = $dirUpload.'file/';
                move_uploaded_file($_FILES['file_addon']['tmp_name'], $chemin_destination.$kartName.".zip");
            }
            else
            {
                echo _("Please re-upload your file. It must be a zip.")."<br />";
            }
            echo _("Successful, your kart will appear when a moderator will valid it.")."<br />";
            
            $this->reqSql = mysql_query("SELECT * FROM ".$this->addonType." WHERE `".$this->addonType."`.`name` ='".$kartName."' LIMIT 1 ;");
        }
        echo '</div>';
        $this->addonCurrent = mysql_fetch_array($this->reqSql);
    }

    /** To get the permanent link of the current addon */ 
    function permalink()
    {
        return 'addon-view.php?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['name'];
    }
}

?>
