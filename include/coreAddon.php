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
include("config.php");
include_once("coreUser.php");
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
        $this->reqSql = mysql_query("SELECT * FROM ".$this->addonType." WHERE `".$this->addonType."`.`id` =".$id." LIMIT 1 ;");
        $this->addonCurrent = mysql_fetch_array($this->reqSql);
    }
    function selectByUser($id)
    {
        $this->reqSql = mysql_query("SELECT * FROM ".$this->addonType." WHERE `".$this->addonType."`.`user` =".$id." ;");
    }
    function loadAll()
    {
        $this->reqSql = mysql_query("SELECT * FROM ".$this->addonType) or die(mysql_error());
    }
    function next()
    {
        $succes = true;
        $this->addonCurrent = mysql_fetch_array($this->reqSql) or $succes = false;
        return $succes;
    }
    
    //TODO : remove all function set* and replace them by a function setInformation($value, $type)
    function setAvailable()
    {
        global $base;
        if($_SESSION['range']['manageaddons'] == true)
        {
            if($this->addonCurrent['available'] == 0)  mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `available` = '1' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
            else mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `available` = '0' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
        }
    }
    function setDescription($newDesc)
    {
        global $base;
        if($_SESSION['range']['manageaddons'] == true|| $this->addonCurrent['user'] == $_SESSION['id'])
        {
            mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `Description` = '".$newDesc."' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
        }
    }
    
    function setFile()
    {
        global $base, $dirUpload;
        echo $type;
        if($_SESSION['range']['manageaddons'] == true|| $this->addonCurrent['user'] == $_SESSION['id'])
        {
            if($_POST['fileType']!="icon" || $this->addonType!="tracks")
            {
            if (isset($_FILES['fileSend'])) {
            echo $_POST['fileType'];
                $chemin_destination = $dirUpload.$_POST['fileType'].'/';
                unlink($chemin_destination.$this->addonCurrent[$_POST['fileType']]);
                move_uploaded_file($_FILES['fileSend']['tmp_name'], $chemin_destination.$this->addonCurrent[$_POST['fileType']]);
            }
            }
        }
    }
    
    function setStkVersion($version)
    {
        global $base;
        if($_SESSION['range']['manageaddons'] == true|| $this->addonCurrent['user'] == $_SESSION['id'])
        {
            mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `STKVersion` = '".$version."' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");
        }
    }
    
    function setInformation($info, $value)
    {
        global $base;
        if($_SESSION['range']['manageaddons'] == true || $this->addonCurrent['user'] == $_SESSION['id'])
        {
            $exist = True;
            $propertie_sql = mysql_query("SELECT * FROM properties WHERE `properties`.`type` = '".$this->addonType."' AND `properties`.`lock` != 1;");
            $propertie = mysql_fetch_array($propertie_sql) or $exist = false;
            if($exist)
            {
                if($propertie['typefield'] == "file")
                {
                    $this->setFile();
                }
                else
                {
                    mysql_query("UPDATE `".$base."`.`".$this->addonType."` SET `".$info."` = '".$value."' WHERE `".$this->addonType."`.`id` =".$this->addonCurrent['id']." LIMIT 1 ;");

                }
                mysql_query("INSERT INTO `stkbase`.`history` (
                `date` ,
                `id` ,
                `user` ,
                `action` ,
                `option`
                )
                VALUES (
                '".date("Y-m-d G:i:s")."', NULL , '".$_SESSION['id']."', 'change ".$info."', '".$this->addonType."\n".$this->addonCurrent['id']."');");
            }
        }
    }
    
    //this function is only available for moderators
    function remove()
    {
        global $base;
        if($_SESSION['range']['manageaddons'] == true)
        {
            mysql_query("DELETE FROM `".$base."`.`".$this->addonType."` WHERE `".$this->addonType."`.`id` = ".$this->addonCurrent['id']." LIMIT 1");
        }
    }
    function writeInformations()
    {
        global $dirDownload, $dirUpload;
        //div for jqery TODO:add jquery effects
        echo '<div id="accordion">';
        echo '<div>';
        
        //write image
        echo '<img class="preview" src="image.php?type=big&amp;pic='.$dirUpload.'image/'.$this->addonCurrent['image'].'" />';
        echo '<table><tr><td><b>'._("Name :").' </b></td><td>';
        
        //write name
        echo $this->addonCurrent['name'];
        echo '</td></tr><tr><td><b>'._("Description :").' </b></td><td>';
        
        // write description
        echo bbc($this->addonCurrent['Description']);
        
        //write revision
        echo '</td></tr><tr><td><b>'._("Revision :").' </b></td><td>';
        echo $this->addonCurrent['version'];
        
        //write version of STK
        echo '</td></tr><tr><td><b>'._("Version of STK :").' </b></td><td>';
        echo $this->addonCurrent['STKVersion'];
        
        //load class user
        $user = new coreUser('users');
        
        //select submiter of addons TODO:add author 
        $user->selectById($this->addonCurrent['user']);
        
        //write author
        echo '</td></tr><tr><td><b>';
        if($this->addonCurrent['Author'] == "")
        {
            echo _("Author :");
        }
        else
        {
            echo _("Submiter :");
        }
        echo ' </b></td><td><a href="account.php?title='.$user->addonCurrent['login'].'">'.$user->addonCurrent['login'].'</a></td></tr>';
        if($this->addonCurrent['Author'] != "")
        {
            echo '<tr><td><b>';
            echo _("Author :");
            echo ' </b></td><td>'.bbc($this->addonCurrent['Author']).'</td></tr>';
        }
        echo '</table></div>';
        
        //write download link
        echo '<a href="'.$dirDownload.'file/'.$this->addonCurrent['file'].'"><img src="image/download.png" alt="Download" title="Download" /></a>';
        
        //write permalink
        echo '<br /><br /><b>Permalink :</b> ';
        echo 'http://'.$_SERVER['SERVER_NAME'].str_replace("addon.php", "addon-view.php", $_SERVER['SCRIPT_NAME']).'?addons='.$this->addonType.'&amp;title='.$this->addonCurrent['name'];
    }
    function writeConfig()
    {
        global $dirDownload, $dirUpload;
        echo '<hr /><h3>Configuration</h3>';
            ?>
            <div class="help-hidden"><span class="help-hidden">Help</span><div>BBCode : 
            <br />strong : [b]....[/b]
            <br />italic : [i]....[/i]</div></div>
            <?php
            echo '<form action="#" method="GET" >';
            $propertie_sql = mysql_query("SELECT * FROM properties WHERE `properties`.`type` = '".$this->addonType."' AND `properties`.`lock` != 1;");
            $file_str = "";
            while($propertie = mysql_fetch_array($propertie_sql))
            {
                $cible = 'addonRequest(\'addon.php?type='.$this->addonType.'&amp;action='.str_replace(" ", "", $propertie['name']).'\', '.$this->addonCurrent['id'].',document.getElementById(\''.str_replace(" ", "", $propertie['name']).'\').value)';
                if($propertie['typefield'] == "textarea")
                {
                    echo "<br />".$propertie['name']." :<br />";
                    echo '<textarea cols="75" rows="8" id="'.str_replace(" ", "", $propertie['name']).'">'.$this->addonCurrent[str_replace(" ", "", $propertie['name'])].'</textarea><br />';
                    echo '<input onclick="'.$cible.'" value="Change '.$propertie['name'].'" type="button" />';
                }
                elseif($propertie['typefield'] == "text")
                {
                    echo "</form><br />".$propertie['name']." :<br />";
                    echo '<form action="javascript:'.$cible.'" method="GET" >';
                    echo '<input type="text" id="'.str_replace(" ", "", $propertie['name']).'" value="'.$this->addonCurrent[str_replace(" ", "", $propertie['name'])].'" ><br />';
                    echo '<input onclick="'.$cible.'" value="Change '.$propertie['name'].'" type="button" />';
                    echo "</form>";
                    echo '<form action="#" method="GET" >';
                }
                elseif($propertie['typefield'] == "enum")
                {
                    echo "<br />".$propertie['name']." :<br />";
                    echo '<select onchange="addonRequest(\'addon.php?type='.$this->addonType.'&amp;action='.str_replace(" ", "", $propertie['name']).'\', '.$this->addonCurrent['id'].', this.value)">';
                    
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
        global $dirDownload, $dirUpload;
        $this->writeInformations();
        //write configuration for the submiter and administrator
        if(($_SESSION['range']['manageaddons']|| $this->addonCurrent['user'] == $_SESSION['id']) and $config)
        {
            $this->writeConfig();
        }
    }
    
    
    
    function addAddon($kartName, $kartDescription)
    {   
        global $base, $dirUpload;
        echo '<div id="content">';
        $existSql= mysql_query("SELECT * FROM `".$this->addonType."` WHERE `".$this->addonType."`.`name` = '".$kartName."'");
        $exist =true;
        $sql =  mysql_fetch_array($existSql) or $exist = false;
        if($exist == false && $kartName != null)
        {
            mysql_query("INSERT INTO `".$base."`.`".$this->addonType."` (`user` ,`name` ,`Description` ,`file` ,`image` ,`icon` ,`date` ,`available` ,`version`, `STKVersion`) 
            VALUES ('".$_SESSION["id"]."', '".$kartName."', '".$kartDescription."', '".$kartName.".zip"."', '".$kartName.".png"."', '".$kartName.".png"."', '".date("Y-m-d")."', '0', '1', '0.7');") or die(mysql_error());
            if (isset($_FILES['icon']) && $_FILES['icon']['type'] == "image/png") {
                $chemin_destination = $dirUpload.'icon/';
                move_uploaded_file($_FILES['icon']['tmp_name'], $chemin_destination.$kartName.".png");

            }
            elseif($this->addonType=="karts")
            {
                echo _("Please re-upload your icon. It must be a png.")."<br />";
            }
            if (isset($_FILES['image']) && $_FILES['image']['type'] == "image/png") {
                $chemin_destination = $dirUpload.'image/';
                move_uploaded_file($_FILES['image']['tmp_name'], $chemin_destination.$kartName.".png");
            }
            else
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
        mysql_query("INSERT INTO `stkbase`.`history` (
        `date` ,
        `id` ,
        `user` ,
        `action` ,
        `option`
        )
        VALUES (
        '".date("Y-m-d G:i:s")."', NULL , '".$_SESSION['id']."', 'add', '".$this->addonType."\n".$this->addonCurrent['id']."');");
    }
}

?>
