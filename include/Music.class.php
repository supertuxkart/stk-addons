<?php
/**
 * Copyright 2013 Stephen Just <stephenjust@users.sourceforge.net>
 *           2014 Daniel Butum <danibutum at gmail dot com>
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
 * Class Music
 */
class Music
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $artist;

    /**
     * @var string
     */
    private $license;

    /**
     * @var integer
     */
    private $length;

    /**
     * @var float
     */
    private $gain;

    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $file_md5;

    /**
     * @var string
     */
    private $xml_file;

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * @return string
     */
    public function getLicense()
    {
        return $this->license;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @return string
     */
    public function getXmlFile()
    {
        return $this->xml_file;
    }

    /**
     * @return float
     */
    public function getGain()
    {
        return $this->gain;
    }

    /**
     * @return integer
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * Get a Music object by ID
     *
     * @param integer $id
     *
     * @return Music
     */
    public static function get($id)
    {
        $instance = new self();
        $instance->populateById($id);

        return $instance;
    }

    /**
     * Get an array of Music objects containing all tracks, sorted by title
     *
     * @return Music[] array of music instances
     */
    public static function getAllByTitle()
    {
        try
        {
            $tracks = DBConnection::get()->query(
                'SELECT `id` FROM `' . DB_PREFIX . 'music`
                ORDER BY `title` ASC',
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            return [];
        }

        $music_tracks = [];
        foreach ($tracks as $track)
        {
            $track_instance = Music::get($track['id']);
            if ($track_instance->getId() !== null)
            {
                $music_tracks[] = $track_instance;
            }
        }

        return $music_tracks;
    }

    /**
     * Populate a music object by looking up the ID in the database
     *
     * @param integer $id
     *
     * @return null
     */
    private function populateById($id)
    {
        try
        {
            $track_info = DBConnection::get()->query(
                'SELECT * FROM `' . DB_PREFIX . 'music`
                WHERE `id` = :id',
                DBConnection::FETCH_FIRST,
                [':id' => $id],
                [':id' => DBConnection::PARAM_INT]
            );
            if (empty($track_info))
            {
                return null;
            }

            $this->id = $id;
            $this->title = $track_info['title'];
            $this->artist = $track_info['artist'];
            $this->license = $track_info['license'];
            $this->gain = $track_info['gain'];
            $this->length = $track_info['length'];
            $this->file = $track_info['file'];
            $this->file_md5 = $track_info['file_md5'];
            $this->xml_file = $track_info['xml_filename'];
        }
        catch(DBException $e)
        {
            return;
        }
    }
}
