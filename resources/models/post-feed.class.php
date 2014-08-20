<?php

class PostFeed {
    protected static function get_post_array($query_results) {
        if ($query_results) {
            $posts = array();
            while ($row = $query_results->fetch_assoc()) {
                $posts[] = Post::get_by_id($row['post_id']);
            }
            return $posts;
        }
        return null;
    }
    
    public static function get_user_latest($page = 1, $limit = 15, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT `post_id` FROM `posts`
            WHERE `user_id`=".$db->real_escape_string((int)$user_id)."
            ORDER BY `date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        return PostFeed::get_post_array($results);
    }
    
    public static function get_friends_latest($page = 1, $limit = 15, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT a.`post_id` FROM `posts` AS a
            JOIN `follows` AS b
            ON b.`to_id` = a.`user_id`
            AND b.`from_id` = ".$db->real_escape_string((int)$user_id)."
            ORDER BY a.`date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        return PostFeed::get_post_array($results);
    }
}
