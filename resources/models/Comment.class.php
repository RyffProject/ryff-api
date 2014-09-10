<?php

/**
 * @class Comment
 * ==============
 * 
 * Provides a class for Comment objects and static functions related to comments.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Comment {
    /**
     * This comment's id.
     * 
     * @var int
     */
    public $id;
    
    /**
     * The User object this comment belongs to.
     * 
     * @var User
     */
    public $user;
    
    /**
     * The id of the Post object this comment belongs to.
     * 
     * @var int
     */
    public $post_id;
    
    /**
     * The text content of the post.
     * 
     * @var string
     */
    public $content;
    
    /**
     * Constructs a Comment object with the given parameters.
     * 
     * @param int $id
     * @param User $user
     * @param int $post_id
     * @param string $content
     */
    protected function __construct($id, $user, $post_id, $content) {
        $this->id = $id;
        $this->user = $user;
        $this->post_id = (int)$post_id;
        $this->content = $content;
    }
    
    /**
     * Constructs and returns a Comment instance from a database row.
     * 
     * @param type $row
     * @return User|null
     */
    public static function create($row) {
        $required_keys = array(
            'comment_id' => 0, 'user_id' => 0,
            'post_id' => 0, 'content' => 0
        );
        if (empty(array_diff_key($required_keys, $row))) {
            return new Comment(
                $row['comment_id'], User::get_by_id($row['user_id']),
                $row['post_id'], $row['content']
            );
        }
        return null;
    }
    
    /**
     * Adds a comment with the given text content to the given post from the
     * given user, or null on failure.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param string $content
     * @param int $post_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return Comment|null
     */
    public static function add($content, $post_id, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            INSERT INTO `comments` (`content`, `post_id`, `user_id`)
            VALUES (:content, :post_id, :user_id)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('content', $content);
        $sth->bindValue('post_id', $post_id);
        $sth->bindValue('user_id', $user_id);
        if (!$sth->execute()) {
            return null;
        }
        return Comment::get_by_id($dbh->lastInsertId());
    }
    
    /**
     * Deletes the comment with the given id, if the given user posted it.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $comment_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function delete($comment_id, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            DELETE FROM `comments`
            WHERE `comment_id` = :comment_id
            AND `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('comment_id', $comment_id);
        $sth->bindValue('user_id', $user_id);
        if (!$sth->execute() || !$sth->rowCount()) {
            return false;
        }
        return true;
    }
    
    /**
     * Returns a Comment object with the given id, or null on failure.
     * 
     * @global PDO $dbh
     * @param int $comment_id
     * @return Comment|null
     */
    public static function get_by_id($comment_id) {
        global $dbh;
        $query = "
            SELECT `comment_id`, `post_id`, `user_id`, `content`
            FROM `comments`
            WHERE `comment_id` = :comment_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('comment_id', $comment_id);
        if ($sth->execute() && $sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return Comment::create($row);
        }
        return null;
    }
    
    /**
     * Gets an array of Comment objects on the given post, or null on failure.
     * 
     * @global PDO $dbh
     * @param int $post_id
     * @param int $page [optional] The page number of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @return array|null
     */
    public static function get_for_post($post_id, $page = 1, $limit = 15) {
        global $dbh;
        
        $query = "
            SELECT `comment_id`, `post_id`, `user_id`, `content`
            FROM `comments`
            WHERE `post_id` = :post_id
            ORDER BY `date_created` ASC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        if (!$sth->execute()) {
            return null;
        }
        
        $comments = array();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $comments[] = static::create($row);
        }
        return $comments;
    }
}
