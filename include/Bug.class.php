<?php
/**
 * copyright 2014-2015 Daniel Butum <danibutum at gmail dot com>
 *
 * This file is part of stk-addons.
 *
 * stk-addons is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * stk-addons is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with stk-addons. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class Bug
 */
class Bug extends Base
{
    const MIN_TITLE = 5;

    const MAX_TITLE = 64;

    const MIN_DESCRIPTION = 5;

    const MAX_DESCRIPTION = 1024;

    const MIN_COMMENT_DESCRIPTION = 10;

    const MAX_COMMENT_DESCRIPTION = 512;

    const MIN_CLOSE_REASON = 5;

    const MAX_CLOSE_REASON = 512;

    /**
     * Hold the bug id
     * @var int
     */
    private $id;

    /**
     * The user who reported the bug
     * @var int
     */
    private $user_id;

    /**
     * The username of the user who reported the bug
     * @var string
     */
    private $user_username;

    /**
     * The addon that has this bug
     * @var int
     */
    private $addon_id;

    /**
     * The user who closed the bug report
     * @var int
     */
    private $close_id;

    /**
     * The username who closed the bug
     * @var string
     */
    private $close_username;

    /**
     * The reason for it's closure
     * @var string
     */
    private $close_reason;

    /**
     * The date it was reported
     * @var string
     */
    private $date_report;

    /**
     * The date it was last edited
     * @var string
     */
    private $date_edit;

    /**
     * The date the bug was closed
     * @var string
     */
    private $date_close;

    /**
     * The bug title
     * @var string
     */
    private $title;

    /**
     * The bug description
     * @var string
     */
    private $description;

    /**
     * Flag that indicate if the bug is a feedback
     * @var bool
     */
    private $is_report;

    /**
     * Hold all the comments for this bug
     * @var array
     */
    private $comments_data = [];

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
                "SELECT c.*, u.username AS user_name
                FROM `{DB_VERSION}_bugs_comments` c
                    LEFT JOIN `{DB_VERSION}_users` u ON c.user_id = u.id
                WHERE c.bug_id = :bug_id
                ORDER BY c.date ASC",
                DBConnection::FETCH_ALL,
                [":bug_id" => $this->id]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("fetch bug comments")));
        }

        $this->comments_data = $comments;
    }

    /**
     * @param array $bug_data
     * @param bool  $load_comments flag that indicates to load the comments
     *
     * @throws BugException on database error
     */
    protected function __construct(array $bug_data, $load_comments = true)
    {
        $this->id = $bug_data["id"];
        $this->user_id = $bug_data["user_id"];
        $this->user_username = $bug_data["user_username"];
        $this->addon_id = $bug_data["addon_id"];
        $this->close_id = $bug_data["close_id"];
        $this->close_username = $bug_data["close_username"];
        $this->close_reason = $bug_data["close_reason"];
        $this->date_report = $bug_data["date_report"];
        $this->date_edit = $bug_data["date_edit"];
        $this->date_close = $bug_data["date_close"];
        $this->title = $bug_data["title"];
        $this->description = $bug_data["description"];
        $this->is_report = (bool)$bug_data["is_report"];

        // load comments
        if ($load_comments)
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
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getUserName()
    {
        return $this->user_username;
    }

    /**
     * @return int
     */
    public function getAddonId()
    {
        return $this->addon_id;
    }

    /**
     * @return int
     */
    public function getCloseId()
    {
        return $this->close_id;
    }

    /**
     * @return string
     */
    public function getCloseUserName()
    {
        return $this->close_username;
    }

    /**
     * @return string
     */
    public function getCloseReason()
    {
        return $this->close_reason;
    }

    /**
     * @return string
     */
    public function getDateReport()
    {
        return $this->date_report;
    }

    /**
     * @return string
     */
    public function getDateEdit()
    {
        return $this->date_edit;
    }

    /**
     * @return string
     */
    public function getDateClose()
    {
        return $this->date_close;
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
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return bool
     */
    public function isReport()
    {
        return $this->is_report;
    }

    /**
     * Check if the bug is closed
     *
     * @return bool
     */
    public function isClosed()
    {
        return (bool)$this->getCloseId();
    }

    /**
     * The comments data of the bug
     * @return array
     */
    public function getCommentsData()
    {
        return $this->comments_data;
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
        return static::getAllFromTable(
            "SELECT * FROM `{DB_VERSION}_bugs` ORDER BY `date_edit` DESC, `id` ASC",
            $limit,
            $current_page
        );
    }

    /**
     * Factory method to build a Bug by id
     *
     * @param int  $bug_id
     * @param bool $load_comments flag that indicates to load the comments
     *
     * @return Bug
     * @throws BugException
     */
    public static function get($bug_id, $load_comments = true)
    {
        try
        {
            $data = DBConnection::get()->query(
                "SELECT
                    (SELECT `username` FROM `{DB_VERSION}_users` WHERE id = B.user_id) AS user_username,
                    (SELECT `username` FROM `{DB_VERSION}_users` WHERE id = B.close_id) AS close_username,
                    B.*
                FROM `{DB_VERSION}_bugs` AS B
                WHERE B.id = :id",
                DBConnection::FETCH_FIRST,
                [":id" => $bug_id],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("get a bug record")));
        }

        if (!$data)
        {
            throw new BugException(sprintf(_h("There is no bug with id %d"), $bug_id));
        }

        return new Bug($data, $load_comments);
    }

    /**
     * Get the data of a comment by id
     *
     * @param int $comment_id
     *
     * @return array
     * @throws BugException
     */
    public static function getCommentData($comment_id)
    {
        try
        {
            $comment = DBConnection::get()->query(
                "SELECt * FROM `{DB_VERSION}_bugs_comments`
                WHERE `id` = :id LIMIT 1",
                DBConnection::FETCH_FIRST,
                [":id" => $comment_id],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("fetch a bug comments data")));
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
        if (!$search_term)
        {
            throw new BugException(_h("The search term is empty"));
        }

        $query = "SELECT id, addon_id, title, date_edit, date_close, close_id, close_reason FROM `{DB_VERSION}_bugs`";

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
            case "all":
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
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("search bugs")));
        }

        return $bugs;
    }

    /**
     * Add a bug into the database
     *
     * @param int    $user_id         the user who created the bug
     * @param string $addon_id        the addon that has the bug
     * @param string $bug_title       the title of the bug
     * @param string $bug_description the description of the bug
     *
     * @return int bug id
     * @throws BugException the error
     */
    public static function add($user_id, $addon_id, $bug_title, $bug_description)
    {
        // validate
        static::validateTitle($bug_title);
        static::validateDescription($bug_description);
        if (!Addon::exists($addon_id))
        {
            throw new BugException(_h("The addon name does not exist"));
        }
        if (!User::hasPermission(AccessControl::PERM_ADD_BUG))
        {
            throw new BugException(_h("You do not have the necessary permission to add a bug"));
        }

        try
        {
            DBConnection::get()->insert(
                "bugs",
                [
                    ":user_id"     => $user_id,
                    ":addon_id"    => $addon_id,
                    ":title"       => $bug_title,
                    ":description" => $bug_description,
                    "date_report"  => "NOW()"
                ]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("create a bug")));
        }

        return DBConnection::get()->lastInsertId();
    }

    /**
     * Add a bug comment to the database
     *
     * @param int    $user_id
     * @param int    $bug_id
     * @param string $comment_description
     *
     * @return int comment id
     * @throws BugException
     */
    public static function addComment($user_id, $bug_id, $comment_description)
    {
        // validate
        static::validateCommentDescription($comment_description);
        if (!static::exists($bug_id))
        {
            throw new BugException(_h("The bug does not exist"));
        }
        if (!User::hasPermission(AccessControl::PERM_ADD_BUG_COMMENT))
        {
            throw new BugException(_h("You do not have the necessary permission to add a comment"));
        }

        try
        {
            DBConnection::get()->insert(
                "bugs_comments",
                [
                    ":user_id"     => $user_id,
                    ":bug_id"      => $bug_id,
                    ":description" => $comment_description,
                    "date"         => "NOW()"
                ]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("create a bug comment")));
        }

        return DBConnection::get()->lastInsertId();
    }

    /**
     * Update a bug title and description
     *
     * @param int    $bug_id
     * @param string $bug_title
     * @param string $bug_description
     *
     * @throws BugException
     */
    public static function update($bug_id, $bug_title, $bug_description)
    {
        static::validateTitle($bug_title);
        static::validateDescription($bug_description);

        // get bug and also verify if it exists
        $bug = static::get($bug_id, false);

        // TODO check if we can update bug after it is closed
        $is_owner = (User::getLoggedId() === $bug->getUserId());
        $can_edit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$is_owner && !$can_edit)
        {
            throw new BugException(_h("You do not have the necessary permission to update this bug"));
        }

        try
        {
            DBConnection::get()->update(
                "bugs",
                "`id` = :id",
                [
                    ":id"          => $bug_id,
                    ":title"       => $bug_title,
                    ":description" => $bug_description
                ],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("update a bug")));
        }
    }

    /**
     * Update a bug comment description
     *
     * @param int    $comment_id
     * @param string $comment_description
     *
     * @throws BugException
     */
    public static function updateComment($comment_id, $comment_description)
    {
        // validate
        static::validateCommentDescription($comment_description);
        if (!static::commentExists($comment_id))
        {
            throw new BugException(_h("The bug comment does not exist"));
        }

        $can_edit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$can_edit)
        {
            throw new BugException(_h("You do not have the necessary permission to update this bug comment"));
        }

        try
        {
            DBConnection::get()->update(
                "bugs_comments",
                "`id` = :id",
                [
                    ":id"          => $comment_id,
                    ":description" => $comment_description
                ],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("update a bug comment")));
        }
    }

    /**
     * Close a bug
     *
     * @param int    $bug_id       the bug id to close
     * @param string $close_reason the closing reason
     *
     * @throws BugException
     */
    public static function close($bug_id, $close_reason)
    {
        static::validateCloseReason($close_reason);

        // get bug and also verify if it exists
        $bug = static::get($bug_id, false);

        // is already closed
        if ($bug->isClosed())
        {
            throw new BugException(_h("The bug is already closed"));
        }

        $is_owner = (User::getLoggedId() === $bug->getUserId());
        $can_edit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$is_owner && !$can_edit)
        {
            throw new BugException(_h("You do not have the necessary permission to close this bug"));
        }

        try
        {
            DBConnection::get()->update(
                "bugs",
                "`id` = :id",
                [
                    ":id"           => $bug_id,
                    ":close_id"     => User::getLoggedId(),
                    ":close_reason" => $close_reason,
                    "date_close"    => "NOW()"
                ],
                [
                    ":id"       => DBConnection::PARAM_INT,
                    ":close_id" => DBConnection::PARAM_INT
                ]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("close a bug")));
        }
    }

    /**
     * Delete a bug
     *
     * @param int $bug_id the comment to delete
     *
     * @throws BugException
     */
    public static function delete($bug_id)
    {
        // validate
        if (!static::exists($bug_id))
        {
            throw new BugException(_h("The bug does not exist"));
        }

        $can_delete = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$can_delete)
        {
            throw new BugException(_h("You do not have the necessary permission to delete this bug"));
        }

        try
        {
            DBConnection::get()->delete(
                "bugs",
                "`id` = :id",
                [":id" => $bug_id],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("delete a bug")));
        }
    }

    /**
     * Delete a bug comment
     *
     * @param int $comment_id the comment to delete
     *
     * @throws BugException
     */
    public static function deleteComment($comment_id)
    {
        // validate
        if (!static::commentExists($comment_id))
        {
            throw new BugException(_h("The bug comment does not exist"));
        }

        //$isOwner = (User::getLoggedId() === $comment["user_id"]);
        $can_edit = User::hasPermission(AccessControl::PERM_EDIT_BUGS);

        // check permission
        if (!$can_edit)
        {
            throw new BugException(_h("You do not have the necessary permission to delete this bug comment"));
        }

        try
        {
            DBConnection::get()->delete(
                "bugs_comments",
                "`id` = :id",
                [":id" => $comment_id],
                [":id" => DBConnection::PARAM_INT]
            );
        }
        catch (DBException $e)
        {
            throw new BugException(exception_message_db(_("delete a bug comment")));
        }
    }

    /**
     * @return BugException
     */
    public static function getException()
    {
        return new BugException();
    }

    /**
     * Validate a title description
     *
     * @param string $title
     *
     * @throws BugException
     */
    public static function validateTitle($title)
    {
        static::validateFieldLength(_h("title"), $title, static::MIN_TITLE, static::MAX_TITLE);
    }

    /**
     * Validate a bug description
     *
     * @param string $description
     *
     * @throws BugException
     */
    public static function validateDescription($description)
    {
        static::validateFieldLength(_h("description"), $description, static::MIN_DESCRIPTION, static::MAX_DESCRIPTION);
    }

    /**
     * Validate a comment description
     *
     * @param string $comment_description
     *
     * @throws BugException
     */
    public static function validateCommentDescription($comment_description)
    {
        static::validateFieldLength(
            _h("comment description"),
            $comment_description,
            static::MIN_COMMENT_DESCRIPTION,
            static::MAX_COMMENT_DESCRIPTION
        );
    }

    /**
     * Validate a bug close reason
     *
     * @param string $close_reason
     *
     * @throws BugException
     */
    public static function validateCloseReason($close_reason)
    {
        static::validateFieldLength(
            _h("close reason"),
            $close_reason,
            static::MIN_CLOSE_REASON,
            static::MAX_CLOSE_REASON
        );
    }
}
