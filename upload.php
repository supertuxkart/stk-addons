<?php
/**
 * copyright 2009 Lucas Baudin <xapantu@gmail.com>
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

define('ROOT','./');
$security = 'addAddon';
include('include.php');
include('include/top.php');

// define possibly undefined variables
$_GET['action'] = (isset($_GET['action'])) ? $_GET['action'] : null;

echo '</head><body>';
include(ROOT.'include/menu.php');

if($_GET['action'] == "submit")
{
    echo '<div id="content">';
    parseUpload($_FILES['file_addon']);
    echo '</div>';
    include('include/footer.php');
    exit;
}
?>
<div id="content">
    <form id="formKart" enctype="multipart/form-data" action="upload.php?action=submit" method="POST">
        <?php echo _('Please upload a kart or track.'); ?><br />
        <?php echo _('Do not use this form if you are updating an existing add-on.'); ?><br />
        <label><?php echo _("File:"); ?><br /><input type="file" name="file_addon" /><br /></label>
        <?php echo _('Supported file types are:'); ?> .zip<br />
        <input type="submit" value="<?php echo _('Upload file'); ?>" />
    </form>
</div>
<?php
include("include/footer.php");
?>
