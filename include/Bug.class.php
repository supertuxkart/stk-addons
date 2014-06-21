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

/**
 * Class Bug
 */
class Bug
{

    /**
     * Hold the bug id
     * @var int
     */
    protected $id;

    /**
     * Hold all the bug fields
     * @var array
     */
    protected $bugData = array();


    /**
     * Hold all the comments for this bug
     * @var array
     */
    protected $commentsData = array();

    /**
     * Load the comments from the database into the current bug instance
     *
     * @throws BugException
     */
    protected function loadComments()
    {
        try
        {
            $comments = DBConnection::get()->query(
                "SELECt * FROM " . DB_PREFIX . "bugs_comments
                WHERE `bug_id` = :bug_id
                ORDER BY date DESC",
                DBConnection::FETCH_ALL,
                array(":bug_id" => $this->id)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to fetch comments.") . ' ' . _("Please contact a website administrator.")));
        }

        $this->commentsData = $comments;
    }

    /**
     * @param int   $id
     * @param array $bugData
     * @param bool  $loadComments flag that indicates to load the comments
     *
     * @throws BugException on database error
     */
    public function __construct($id, array $bugData = array(), $loadComments = true)
    {
        $this->id = $id;
        $this->bugData = $bugData;

        // load comments
        if ($loadComments)
        {
            $this->loadComments();
        }
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
     * Check if the bug is closed
     *
     * @return bool
     */
    public function isClosed()
    {
        return $this->getCloseId();
    }

    /**
     * Get the instance addon data
     *
     * @return array
     */
    public function getData()
    {
        return $this->bugData;
    }


    /**
     * The comments data of the bug
     * @return array
     */
    public function getCommentsData()
    {
        return $this->commentsData;
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
            $count = DBConnection::get()->count(
                "bugs",
                "`id` = :id",
                array(":id" => $id),
                array(":id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to see if a bug exists.") . '. ' . _("Please contact a website administrator.")));
        }

        return $count !== 0;
    }

    /**
     * See if a bug comment exists
     *
     * @param int $id
     *
     * @return bool
     * @throws BugException
     */
    public static function commentExists($id)
    {
        try
        {
            $count = DBConnection::get()->count(
                "bugs_comments",
                "`id` = :id",
                array(":id" => $id),
                array(":id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to see if a bug comment exists.") . '. ' . _("Please contact a website administrator.")));
        }

        return $count !== 0;
    }

    /**
     * Get all the bugs data without the comments
     *
     * @return array
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
            throw new BugException(h(_("Tried to fetch all bugs") . '. ' . _("Please contact a website administrator.")));
        }

        return $bugs;
    }

    /**
     * Factory method to build a Bug by id
     *
     * @param int  $bugId
     * @param bool $loadComments flag that indicates to load the comments
     *
     * @return Bug
     * @throws BugException
     */
    public static function get($bugId, $loadComments = true)
    {
        try
        {
            $data = DBConnection::get()->query(
                "SELECT * FROM " . DB_PREFIX . "bugs
                WHERE `id` = :id LIMIT 1",
                DBConnection::FETCH_FIRST,
                array(":id" => $bugId),
                array(":id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to see if a bug exists.") . '. ' . _("Please contact a website administrator.")));
        }

        if (empty($data))
        {
            throw new BugException(sprintf(_h("There is no bug with id %d"), $bugId));
        }

        return new Bug($data['id'], $data, $loadComments);
    }

    /**
     * Get the data of a comment by id
     *
     * @param $commentId
     *
     * @return array
     * @throws BugException
     */
    public static function getCommentData($commentId)
    {
        try
        {
            $comment = DBConnection::get()->query(
                "SELECt * FROM " . DB_PREFIX . "bugs_comments
                WHERE `id` = :id LIMIT 1",
                DBConnection::FETCH_FIRST,
                array(":id" => $commentId),
                array(":id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to fetch a comments data") . '. ' . _("Please contact a website administrator.")));
        }

        return $comment;
    }

    /**
     * Search a bug by different criteria
     *
     * @param string $search_term        the search item
     * @param string $status             the status of the bug. possible: all, open, closed
     * @param bool   $search_description search also in description
     *
     * @return array|int|null
     * @throws InvalidArgumentException
     * @throws BugException
     */
    public static function search($search_term, $status = "all", $search_description = false)
    {
        if (empty($search_term))
        {
            return array();
        }

        try
        {
            if ($search_description)
            {
                $query = "SELECT * FROM `" . DB_PREFIX . "bugs` WHERE (`title` LIKE :search_term OR `description` LIKE :search_term)";
            }
            else
            {
                $query = "SELECT * FROM `" . DB_PREFIX . "bugs` WHERE (`title` LIKE :search_term)";
            }

            switch ($status)
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
                    throw new InvalidArgumentException(sprintf("status = %s is invalid", $status));
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
            throw new BugException(h(_("Error on selecting all search bugs") . '. ' . _("Please contact a website administrator.")));
        }

        return $bugs;
    }

    /**
     * Add a bug into the database
     *
     * @param int    $userId
     * @param string $addonId
     * @param string $bugTitle
     * @param string $bugDescription
     *
     * @return int bug id
     * @throws BugException the error
     */
    public static function insert($userId, $addonId, $bugTitle, $bugDescription)
    {
        // validate
        if (!Addon::exists($addonId))
        {
            throw new BugException(_h("The addon name does not exist"));
        }
        if (!User::hasPermission(AccessControl::PERM_ADD_BUG))
        {
            throw new BugException(_h("You do not have the necessary permission to add a bug"));
        }

        // clean
        $bugTitle = h($bugTitle);
        $bugDescription = Util::htmlPurify($bugDescription);

        try
        {
            DBConnection::get()->insert(
                "bugs",
                array(
                    ":user_id"     => $userId,
                    ":addon_id"    => $addonId,
                    ":title"       => $bugTitle,
                    ":description" => $bugDescription,
                    "date_report"  => "NOW()"
                )
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to insert a bug") . '. ' . _("Please contact a website administrator.")));
        }

        return DBConnection::get()->lastInsertId();
    }

    /**
     * Add a bug comment to the database
     *
     * @param int    $userId
     * @param int    $bugId
     * @param string $commentDescription
     *
     * @return int comment id
     * @throws BugException
     */
    public static function insertComment($userId, $bugId, $commentDescription)
    {
        // validate
        if (!static::exists($bugId))
        {
            throw new BugException(_h("The bug does not exist"));
        }
        if (!User::isLoggedIn())
        {
            throw new BugException(_h("You do not have the necessary permission to add a comment"));
        }

        // clean
        $commentDescription = Util::htmlPurify($commentDescription);

        try
        {
            DBConnection::get()->insert(
                "bugs_comments",
                array(
                    ":user_id"     => $userId,
                    ":bug_id"      => $bugId,
                    ":description" => $commentDescription,
                    "date"         => "NOW()"
                )
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to insert a bug comment") . '. ' . _("Please contact a website administrator.")));
        }

        return DBConnection::get()->lastInsertId();
    }

    /**
     * Update a bug title and description
     *
     * @param int    $bugId
     * @param string $bugTitle
     * @param string $bugDescription
     *
     * @throws BugException
     */
    public static function update($bugId, $bugTitle, $bugDescription)
    {
        // get bug
        try
        {
            $bug = static::get($bugId, false);
        }
        catch(BugException $e)
        {
            throw new BugException(_h("The bug does not exist"));
        }

        // TODO check if we can update bug after it is closed
        $isOwner = (User::getId() === $bug->getUserId());
        $canEdit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$isOwner && !$canEdit)
        {
            throw new BugException(_h("You do not have the necessary permission to update this bug"));
        }

        // clean
        $bugTitle = h($bugTitle);
        $bugDescription = Util::htmlPurify($bugDescription);

        try
        {
            DBConnection::get()->update(
                "bugs",
                "`id` = :id",
                array(
                    ":id"          => $bugId,
                    ":title"       => $bugTitle,
                    ":description" => $bugDescription
                ),
                array(":id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to update a bug") . '. ' . _("Please contact a website administrator.")));
        }
    }

    /**
     * Update a bug comment description
     *
     * @param int    $commentId
     * @param string $commentDescription
     *
     * @throws BugException
     */
    public static function updateComment($commentId, $commentDescription)
    {
        // get comment
        $comment = static::getCommentData($commentId);

        // validate
        if (empty($comment))
        {
            throw new BugException(_h("The bug comment does not exist"));
        }

        $isOwner = (User::getId() === $comment["user_id"]);
        $canEdit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$isOwner && !$canEdit)
        {
            throw new BugException(_h("You do not have the necessary permission to update this bug comment"));
        }

        // clean
        $commentDescription = Util::htmlPurify($commentDescription);

        try
        {
            DBConnection::get()->update(
                "bugs_comments",
                "`id` = :id",
                array(":id" => $commentId, ":description" => $commentDescription),
                array(":id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to update a bug comment") . '. ' . _("Please contact a website administrator.")));
        }
    }

    /**
     * Close a bug
     *
     * @param int    $bugId       the bug id to close
     * @param string $closeReason the closing reason
     *
     * @throws BugException
     */
    public static function close($bugId, $closeReason)
    {
        // get bug
        try
        {
            $bug = static::get($bugId, false);
        }
        catch(BugException $e)
        {
            throw new BugException(_h("The bug does not exist"));
        }

        // is already closed
        if ($bug->isClosed())
        {
            throw new BugException(_h("The bug is already closed"));
        }

        $isOwner = (User::getId() === $bug->getUserId());
        $canEdit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$isOwner && !$canEdit)
        {
            throw new BugException(_h("You do not have the necessary permission to close this bug"));
        }

        try
        {
            DBConnection::get()->update(
                "bugs",
                "`id` = :id",
                array(
                    ":id"           => $bugId,
                    ":close_id"     => User::getId(),
                    ":close_reason" => h($closeReason),
                    "date_close"    => "NOW()"
                ),
                array(":id" => DBConnection::PARAM_INT, ":close_id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to close a bug") . '. ' . _("Please contact a website administrator.")));
        }
    }

    /**
     * Delete a bug comment
     *
     * @param int $commentId the comment to delete
     *
     * @throws BugException
     */
    public static function deleteComment($commentId)
    {
        // get comment
        $comment = static::getCommentData($commentId);

        // validate
        if (empty($comment))
        {
            throw new BugException(_h("The bug comment does not exist"));
        }

        $isOwner = (User::getId() === $comment["user_id"]);
        $canEdit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$isOwner && !$canEdit)
        {
            throw new BugException(_h("You do not have the necessary permission to delete this bug comment"));
        }

        try
        {
            DBConnection::get()->delete(
                "bugs_comments",
                "`id` = :id",
                array(":id" => $commentId),
                array(":id" => DBConnection::PARAM_INT)
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to delete a bug comment") . '. ' . _("Please contact a website administrator.")));
        }
    }

}