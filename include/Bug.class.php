<?php

/**
 * copyright 2014 Daniel Butum <danibutum at gmail dot com>
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
class Bug
{

    /**
     * @var int
     */
    protected $id;

    /**
     * @var array
     */
    protected $bugData = array();

    /**
     * @param int   $id
     * @param array $bugData
     */
    public function __construct($id, array $bugData = array())
    {
        $this->id = $id;
        $this->bugData = $bugData;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->bugData["user_id"];
    }

    /**
     * @return string
     */
    public function getAddonId()
    {
        return $this->bugData["addon_id"];
    }

    /**
     * @return int
     */
    public function getCloseId()
    {
        return $this->bugData["close_id"];
    }

    /**
     * @return string
     */
    public function getCloseReason()
    {
        return $this->bugData["close_reason"];
    }

    /**
     * @return string
     */
    public function getDateReport()
    {
        return $this->bugData["date_report"];
    }

    /**
     * @return string
     */
    public function getDateEdit()
    {
        return $this->bugData["date_edit"];
    }

    /**
     * @return string
     */
    public function getDateClose()
    {
        return $this->bugData["date_close"];
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->bugData["title"];
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->bugData["description"];
    }

    /**
     * @return bool
     */
    public function isReport()
    {
        return $this->bugData["is_report"] == 1;
    }

    /**
     * See if a bug exists
     *
     * @param int $id
     *
     * @return bool
     * @throws BugException
     */
    public static function exists($id)
    {
        try
        {
            $count = DBConnection::get()->count("bugs", "id = :id", array(":id" => $id));
        }
        catch(DBException $e)
        {
            throw new BugException(htmlspecialchars(
                _("Tried to see if a bug exists.") . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        return $count !== 0;
    }

    /**
     * Get all the bug data
     *
     * @return array|int|null
     * @throws BugException
     */
    public static function getAllData()
    {
        try
        {
            $bugs = DBConnection::get()->query(
                'SELECT * FROM ' . DB_PREFIX . 'bugs
                ORDER BY `date_edit` DESC, `id` ASC',
                DBConnection::FETCH_ALL
            );
        }
        catch(DBException $e)
        {
            if (DEBUG_MODE)
            {
                throw new BugException("Error on selecting all bugs");
            }

            return array();
        }

        return $bugs;
    }

    public static function search($search_term, $status = "all", $search_description = false)
    {
        if(empty($search_term))
        {
            return array();
        }

        try
        {
            if($search_description)
            {
                $query = "SELECT * FROM `" . DB_PREFIX . "bugs` WHERE (`title` LIKE :search_term OR `description` LIKE :search_term)";
            }
            else
            {
                $query = "SELECT * FROM `" . DB_PREFIX . "bugs` WHERE (`title` LIKE :search_term)";
            }

            switch($status)
            {
                case "all";
                    break;
                case "open":
                    $query .= " AND `close_id` IS NULL";
                    break;
                case "closed":
                    $query .= " AND `close_id` is NOT NULL";
                    break;
                default:
                    if(DEBUG_MODE)
                    {
                        throw new InvalidArgumentException(sprintf("status = %s is invalid", $status));
                    }
                    break;
            }

            $bugs = DBConnection::get()->query(
                $query,
                DBConnection::FETCH_ALL,
                array(":search_term" => '%' . $search_term . '%')
            );

        }
        catch(DBException $e)
        {
            if (DEBUG_MODE)
            {
                throw new BugException("Error on selecting all search bugs");
            }

            return array();
        }

        return $bugs;

    }

    /**
     * Factory method to build a Bug by id
     *
     * @param int $id
     *
     * @return Bug
     * @throws BugException
     */
    public static function get($id)
    {
        try
        {
            $data = DBConnection::get()->query(
                "SELECT *
                FROM " . DB_PREFIX . "bugs
                WHERE `id` = :id
                LIMIT 1",
                DBConnection::FETCH_FIRST,
                array(":id" => $id),
                array(":id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(htmlspecialchars(
                _("Tried to see if a bug exists.") . ' ' .
                _('Please contact a website administrator.')
            ));
        }

        if (empty($data))
        {
            throw new BugException(sprintf(_h("There is no bug with id %d"), $id));
        }

        return new Bug($data['id'], $data);
    }

}