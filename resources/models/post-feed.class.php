<?php

class PostFeed {
    public static function get_by_user_latest($page = 1, $limit = 15, $user_id = null) {
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
        
        if ($results) {
            $posts = array();
            while ($row = $results->fetch_assoc()) {
                $posts[] = Post::get_by_id($row['post_id']);
            }
            return $posts;
        }
        return null;
    }
}
