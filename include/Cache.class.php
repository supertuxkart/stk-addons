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
 * Handles management of temporary files
 *
 * @author Stephen
 */
class Cache {
    /**
     * Empty the cache folder, leave certain files in place
     */
    public static function clear() {
        $exclude_regex = '/^(cache_graph_.*\.png)$/i';
        File::deleteRecursive(CACHE_DIR,$exclude_regex);
        @mkdir(CACHE_DIR);
	
	// Clear cache file DB record
	// Get list of cache files
	$select_query = 'SELECT `file`
		FROM `'.DB_PREFIX.'cache`';
	$select_handle = sql_query($select_query);
	if (!$select_handle) return false;
	$num_files = mysql_num_rows($select_handle);
	for ($i = 1; $i <= $num_files; $i++) {
	    $result = mysql_fetch_assoc($select_handle);
	    if (preg_match($exclude_regex,$result['file'])) continue;
	    
	    $del_query = 'DELETE FROM `'.DB_PREFIX.'cache`
		WHERE `file` = \''.$result['file'].'\'';
	    $del_handle = sql_query($del_query);
	    if (!$del_handle) return false;
	}
	return true;
    }
    
    /**
     * Add a database record for a cache file
     * @param string $path Relative to cache root
     * @param string $addon Associated add-on's id or NULL
     * @param string $props File properties (e.g. w=1,h=3)
     * @return boolean 
     */
    public static function createFile($path, $addon = NULL, $props = NULL) {
	$path = mysql_real_escape_string($path);
	$addon = Addon::cleanId($addon);
	$addon = (Addon::exists($addon)) ? $addon : NULL;
	$props = mysql_real_escape_string($props);
	
	// Create record
	$query = 'INSERT INTO `'.DB_PREFIX.'cache`
	    (`file`,`addon`,`props`)
	    VALUES
	    (\''.$path.'\',\''.$addon.'\',\''.$props.'\')';
	$handle = sql_query($query);
	if (!$handle) return false;
	return true;
    }
    
    /**
     * Check if a cache file exist (based on database record of its existence)
     * @param string $path Relative to upload root
     * @return array Empty array on failure, array with 'path', 'addon' otherwise
     */
    public static function fileExists($path) {
	$path = mysql_real_escape_string($path);
	$query = 'SELECT `addon`,`props`
		FROM `'.DB_PREFIX.'cache`
		WHERE `file` = \''.$path.'\'';
	$handle = sql_query($query);
	if (!$handle)
	    return array();
	if (mysql_num_rows($handle) === 0)
	    return array();
	$result = mysql_fetch_assoc($handle);
	return $result;
    }
}

?>
