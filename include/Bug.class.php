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
class Bug extends Base
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
    protected $bugData = [];


    /**
     * Hold all the comments for this bug
     * @var array
     */
    protected $commentsData = [];

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
                [":bug_id" => $this->id]
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
    protected function __construct($id, array $bugData = [], $loadComments = true)
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
     * @param string $message
     *
     * @throws BugException
     */
    protected static function throwException($message)
    {
        throw new BugException($message);
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
        return (int)$this->bugData["is_report"] === 1;
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
        return static::existsField("bugs", "id", $id, DBConnection::PARAM_INT);
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
        return static::existsField("bugs_comments", "id", $id, DBConnection::PARAM_INT);
    }

    /**
     * Get all the bugs data without the comments
     *
     * @param int $limit
     * @param int $current_page
     *
     * @return array
     * @throws BugException
     */
    public static function getAll($limit = -1, $current_page = 1)
    {
        return static::getAllFromTable("bugs", "ORDER BY `date_edit` DESC, `id` ASC", "", $limit, $current_page);
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
        $data = static::getFromField("bugs", "id", $bugId, DBConnection::PARAM_INT, sprintf(_h("There is no bug with id %d"), $bugId));

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
                [":id" => $commentId],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to fetch a comments data") . '. ' . _("Please contact a website administrator.")));
        }

        if (empty($comment))
        {
            throw new BugException(_h("The bug comment does not exist"));
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
     * @return array|null
     * @throws BugException
     */
    public static function search($search_term, $status = "all", $search_description = false)
    {
        // validate
        if (empty($search_term))
        {
            throw new BugException(_h("The search term is empty"));
        }

        $query = "SELECT id, addon_id, title, date_edit, date_close, close_id, close_reason FROM `" . DB_PREFIX . "bugs`";

        // search in description
        if ($search_description)
        {
            $query .= " WHERE (`addon_id` LIKE :search_term OR `title` LIKE :search_term OR `description` LIKE :search_term)";
        }
        else
        {
            $query .= " WHERE (`addon_id` LIKE :search_term OR `title` LIKE :search_term)";
        }

        switch ($status)
        {
            case "all";
                break;

            case "open":
                $query .= " AND `close_id` is NULL";
                break;

            case "closed":
                $query .= " AND `close_id` is NOT NULL";
                break;

            default:
                throw new BugException(sprintf("status = %s is invalid", $status));
                break;
        }

        // set default order
        $query .= " ORDER BY `date_edit` DESC";

        try
        {
            $bugs = DBConnection::get()->query(
                $query,
                DBConnection::FETCH_ALL,
                [":search_term" => '%' . $search_term . '%']
            );

        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Error on searching bugs") . '. ' . _("Please contact a website administrator.")));
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
    public static function add($userId, $addonId, $bugTitle, $bugDescription)
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
                [
                    ":user_id"     => $userId,
                    ":addon_id"    => $addonId,
                    ":title"       => $bugTitle,
                    ":description" => $bugDescription,
                    "date_report"  => "NOW()"
                ]
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
    public static function addComment($userId, $bugId, $commentDescription)
    {
        // validate
        if (!static::exists($bugId))
        {
            throw new BugException(_h("The bug does not exist"));
        }
        if (!User::hasPermission(AccessControl::PERM_ADD_BUG_COMMENT))
        {
            throw new BugException(_h("You do not have the necessary permission to add a comment"));
        }

        // clean
        $commentDescription = Util::htmlPurify($commentDescription);

        try
        {
            DBConnection::get()->insert(
                "bugs_comments",
                [
                    ":user_id"     => $userId,
                    ":bug_id"      => $bugId,
                    ":description" => $commentDescription,
                    "date"         => "NOW()"
                ]
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
        // get bug and also verify if it exists
        $bug = static::get($bugId, false);

        // TODO check if we can update bug after it is closed
        $isOwner = (User::getLoggedId() === $bug->getUserId());
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
                [
                    ":id"          => $bugId,
                    ":title"       => $bugTitle,
                    ":description" => $bugDescription
                ],
                [":id" => DBConnection::PARAM_INT]
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
        // validate comment
        if (!static::commentExists($commentId))
        {
            throw new BugException(_h("The bug comment does not exist"));
        }

        //$isOwner = (User::getLoggedId() === $comment["user_id"]);
        $canEdit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$canEdit)
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
                [":id" => $commentId, ":description" => $commentDescription],
                [":id" => DBConnection::PARAM_INT]
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
        // get bug and also verify if it exists
        $bug = static::get($bugId, false);

        // is already closed
        if ($bug->isClosed())
        {
            throw new BugException(_h("The bug is already closed"));
        }

        $isOwner = (User::getLoggedId() === $bug->getUserId());
        $canEdit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$isOwner && !$canEdit)
        {
            throw new BugException(_h("You do not have the necessary permission to close this bug"));
        }

        // clean
        $closeReason = Util::htmlPurify($closeReason);

        try
        {
            DBConnection::get()->update(
                "bugs",
                "`id` = :id",
                [
                    ":id"           => $bugId,
                    ":close_id"     => User::getLoggedId(),
                    ":close_reason" => $closeReason,
                    "date_close"    => "NOW()"
                ],
                [":id" => DBConnection::PARAM_INT, ":close_id" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to close a bug") . '. ' . _("Please contact a website administrator.")));
        }
    }

    /**
     * Delete a bug
     *
     * @param int $bugId the comment to delete
     *
     * @throws BugException
     */
    public static function delete($bugId)
    {
        // validate
        if (!static::exists($bugId))
        {
            throw new BugException(_h("The bug does not exist"));
        }

        $canDelete = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$canDelete)
        {
            throw new BugException(_h("You do not have the necessary permission to delete this bug"));
        }

        try
        {
            DBConnection::get()->delete(
                "bugs",
                "`id` = :id",
                [":id" => $bugId],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to delete a bug") . '. ' . _("Please contact a website administrator.")));
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
        // validate
        if (!static::commentExists($commentId))
        {
            throw new BugException(_h("The bug comment does not exist"));
        }

        //$isOwner = (User::getLoggedId() === $comment["user_id"]);
        $canEdit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$canEdit)
        {
            throw new BugException(_h("You do not have the necessary permission to delete this bug comment"));
        }

        try
        {
            DBConnection::get()->delete(
                "bugs_comments",
                "`id` = :id",
                [":id" => $commentId],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch(DBException $e)
        {
            throw new BugException(h(_("Tried to delete a bug comment") . '. ' . _("Please contact a website administrator.")));
        }
    }

}