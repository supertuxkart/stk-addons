<?php
/**
 * Copyright 2013 Stephen Just <stephenjust@users.sourceforge.net>
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

require_once(INCLUDE_DIR . 'DBConnection.class.php');

class Music {
    /**
     * @var integer
     */
    private $id = NULL;
    /**
     * @var string
     */
    private $title = NULL;
    /**
     * @var string
     */
    private $artist = NULL;
    /**
     * @var string
     */
    private $license = NULL;
    /**
     * @var integer
     */
    private $length = NULL;
    /**
     * @var float
     */
    private $gain = NULL;
    /**
     * @var string
     */
    private $file = NULL;
    /**
     * @var string
     */
    private $file_md5 = NULL;
    /**
     * @var string
     */
    private $xml_file = NULL;
    
    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }
    /**
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }
    /**
     * @return string
     */
    public function getArtist() {
        return $this->artist;
    }
    /**
     * @return string
     */
    public function getLicense() {
        return $this->license;
    }
    /**
     * @return string
     */
    public function getFile() {
        return $this->file;
    }
    /**
     * @return string
     */
    public function getXmlFile() {
        return $this->xml_file;
    }
    /**
     * @return float
     */
    public function getGain() {
        return $this->gain;
    }
    /**
     * @return integer
     */
    public function getLength() {
        return $this->length;
    }
    
    /**
     * Get a Music object by ID
     * @param integer $id
     * @return \self
     */
    public static function get($id) {
        $instance = new self();
        $instance->populateById($id);
        return $instance;
    }
    
    /**
     * Get an array of Music objects containing all tracks, sorted by title
     * @return array
     */
    public static function getAllByTitle() {
        try {
            $music_tracks = array();
            $result = DBConnection::get()->query(
                    'SELECT `id` FROM `'.DB_PREFIX.'music`
                     ORDER BY `title` ASC',
                    DBConnection::FETCH_ALL);
            foreach ($result AS $music_track) {
                $track = Music::get($music_track['id']);
                if ($track->getId() !== NULL)
                    $music_tracks[] = $track;
            }
            return $music_tracks;
        } catch (DBException $e) {
            return array();
        }
    }
    
    /**
     * Populate a music object by looking up the ID in the database
     * @param integer $id
     * @return void
     */
    private function populateById($id) {
        try {
            $track_info = DBConnection::get()->query(
                    'SELECT * FROM `'.DB_PREFIX.'music`
                     WHERE `id` = :id',
                    DBConnection::FETCH_ALL,
                    array(':id' => (int) $id));
            if (count($track_info) === 0) return;
            
            $this->id = $id;
            $this->title = $track_info[0]['title'];
            $this->artist = $track_info[0]['artist'];
            $this->license = $track_info[0]['license'];
            $this->gain = $track_info[0]['gain'];
            $this->length = $track_info[0]['length'];
            $this->file = $track_info[0]['file'];
            $this->file_md5 = $track_info[0]['file_md5'];
            $this->xml_file = $track_info[0]['xml_filename'];
        } catch (DBException $e) {
            return;
        }
    }
}
