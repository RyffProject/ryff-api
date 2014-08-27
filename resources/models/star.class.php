<?php

/**
 * @class Star
 * ===========
 * 
 * Provides static functions related to starring posts.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Star {
    /**
     * Stars a post for the given user.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $post_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function add($post_id, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            INSERT INTO `stars` (`post_id`, `user_id`)
            VALUES (:post_id, :user_id)";
        $sth = $dbh->prepare($query);
        $sth->bindParam('post_id', $post_id);
        $sth->bindParam('user_id', $user_id);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Removes the post from the given user's starred posts.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $post_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function delete($post_id, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            DELETE FROM `stars`
            WHERE `post_id` = :post_id
            AND `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('post_id', $post_id);
        $sth->bindParam('user_id', $user_id);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns an array of the given user's starred Post objects.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of Post objects, or null on failure.
     */
    public static function get_starred_posts($user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT `post_id` FROM `stars`
            WHERE `user_id` = :user_id
            ORDER BY `date_created` DESC";
        $sth = $dbh->prepare($query);
        $sth->bindParam('user_id', $user_id);
        if ($sth->execute()) {
            $posts = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $posts[] = Post::get_by_id((int)$row['post_id']);
            }
            return $posts;
        }
        return null;
    }
}
