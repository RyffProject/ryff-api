<?php

/**
 * @class Upvote
 * =============
 * 
 * Provides static functions related to upvoting posts.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Upvote {
    /**
     * Upvotes a post for the given user.
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
        
        $upvote_query = "
            INSERT INTO `upvotes` (`post_id`, `user_id`)
            VALUES (
              ".$db->real_escape_string((int)$post_id).",
              ".$db->real_escape_string((int)$user_id)."
            )";
        $upvote_results = $db->query($upvote_query);
        
        if ($upvote_results) {
            $post = Post::get_by_id($post_id);
            if ($post->user->id !== (int)$user_id) {
                Notification::add($post->user->id, "upvote", $post->id, null, null, $user_id);
            }
            return true;
        }
        return false;
    }
    
    /**
     * Removes the given user's upvote from the post.
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
        
        $upvote_query = "
            DELETE FROM `upvotes`
            WHERE `post_id`=".$db->real_escape_string((int)$post_id)."
            AND `user_id`=".$db->real_escape_string((int)$user_id);
        $upvote_results = $db->query($upvote_query);
        
        if ($upvote_results) {
            $post = Post::get_by_id($post_id);
            Notification::delete($post->user->id, "upvote", $post->id, null, null, $user_id);
            return true;
        }
        return false;
    }
}
