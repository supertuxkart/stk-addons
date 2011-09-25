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

/**
 * Class to hold all file-related operations
 *
 * @author Stephen
 */
class File {
    public static function approve($file_id,$approve = true)
    {
        if ($approve !== true)
            $approve = false;

        if (!$_SESSION['role']['manageaddons'])
            throw new FileException(htmlspecialchars(_('Insufficient permissions.')));

        $approve_query = 'UPDATE `'.DB_PREFIX.'files`
            SET `approved` = '.(int)$approve.'
            WHERE `id` = '.(int)$file_id;
        $approve_handle = sql_query($approve_query);
        if (!$approve_handle)
            throw new FileException('Failed to change file approval status.');
        writeAssetXML();
        writeNewsXML();
    }
    
    public static function delete($file_id) {// Get file path
        $get_file_query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id.'
            LIMIT 1';
        $get_file_handle = sql_query($get_file_query);
        if (!$get_file_handle)
            return false;
        if (mysql_num_rows($get_file_handle) == 1)
        {
            $get_file = mysql_fetch_assoc($get_file_handle);
            if (file_exists(UP_LOCATION.$get_file['file_path']))
                unlink(UP_LOCATION.$get_file['file_path']);
        }

        // Delete file record
        $del_file_query = 'DELETE FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id;
        $del_file_handle = sql_query($del_file_query);
        if(!$del_file_handle)
            return false;
        writeAssetXML();
        writeNewsXML();
        return true;
    }

    /**
     * Recursively delete files. This does not touch the database.
     * @param string $dir
     * @return boolean
     */
    public static function deleteRecursive($dir)
    {
        if (is_dir($dir))
        {
            $dir = rtrim($dir, '/');
            $oDir = dir($dir);
            while (($sFile = $oDir->read()) !== false)
            {
                if ($sFile != '.' && $sFile != '..')
                {
                    (!is_link("$dir/$sFile") && is_dir("$dir/$sFile")) ? File::deleteRecursive("$dir/$sFile") : unlink("$dir/$sFile");
                }
            }
            $oDir->close();
            rmdir($dir);
            return true;
        }
        return false;
    }
    
    public static function getPath($file_id) {
        // Validate input
        if (!is_numeric($file_id))
            return false;
        if ($file_id == 0)
            return false;

        // Look up file path from database
        $query = 'SELECT `file_path` FROM `'.DB_PREFIX.'files`
            WHERE `id` = '.(int)$file_id.'
            LIMIT 1';
        $handle = sql_query($query);
        if (mysql_num_rows($handle) == 0)
            return false;
        $file = mysql_fetch_assoc($handle);
        return $file['file_path'];
    }
}

?>
