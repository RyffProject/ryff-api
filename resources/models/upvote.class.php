<?php

class Upvote {
    public static function add($post_id, $user_id = 0) {
        global $db, $CURRENT_USER;
        
        if (!$user_id && $CURRENT_USER) {
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
            return true;
        }
        return false;
    }
    
    public static function delete($post_id, $user_id = 0) {
        global $db, $CURRENT_USER;
        
        if (!$user_id && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $upvote_query = "
            DELETE FROM `upvotes`
            WHERE `post_id`=".$db->real_escape_string((int)$post_id)."
            AND `user_id`=".$db->real_escape_string((int)$user_id);
        $upvote_results = $db->query($upvote_query);
        
        if ($upvote_results) {
            return true;
        }
        return false;
    }
}
