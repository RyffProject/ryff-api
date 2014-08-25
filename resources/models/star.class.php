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
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $post_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function add($post_id, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $star_query = "
            INSERT INTO `stars` (`post_id`, `user_id`)
            VALUES (
              ".$db->real_escape_string((int)$post_id).",
              ".$db->real_escape_string((int)$user_id)."
            )";
        $star_results = $db->query($star_query);
        
        if ($star_results) {
            return true;
        }
        return false;
    }
    
    /**
     * Removes the post from the given user's starred posts.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $post_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function delete($post_id, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $star_query = "
            DELETE FROM `stars`
            WHERE `post_id`=".$db->real_escape_string((int)$post_id)."
            AND `user_id`=".$db->real_escape_string((int)$user_id);
        $star_results = $db->query($star_query);
        
        if ($star_results) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns an array of the given user's starred Post objects.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of Post objects, or null on failure.
     */
    public static function get_starred_posts($user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $starred_query = "
            SELECT `post_id` FROM `stars`
            WHERE `user_id`=".$db->real_escape_string((int)$user_id)."
            ORDER BY `date_created` DESC";
        $starred_results = $db->query($starred_query);
        
        if ($starred_results) {
            $posts = array();
            while ($row = $starred_results->fetch_assoc()) {
                $posts[] = Post::get_by_id((int)$row['post_id']);
            }
            return $posts;
        }
        return null;
    }
}
