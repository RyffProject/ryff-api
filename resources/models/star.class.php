<?php

class Star {
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
}
