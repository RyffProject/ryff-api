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
    
    protected static function get_from_date($time) {
        switch ($time) {
            case "day":
                $from_time = time() - (60 * 60 * 24);
                break;
            case "week":
                $from_time = time() - (60 * 60 * 24 * 7);
                break;
            case "month":
                $from_time = time() - (60 * 60 * 24 * 30);
                break;
            case "all":
            default:
                $from_time = 0;
                break;
        }
        return date("Y-m-d H:i:s", $from_time);
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
    
    public static function search_latest($tags = array(), $page = 1, $limit = 15) {
        global $db;
        
        $safe_tags = array_map(function($tag) use ($db) {
            return "'".$db->real_escape_string($tag)."'";
        }, $tags);

        $query = "
            SELECT DISTINCT(p.`post_id`)
            FROM `posts` AS p
            ".($tags ? "JOIN `post_tags` AS t
            ON t.`post_id` = p.`post_id`
            WHERE t.`tag` IN (".implode(',', $safe_tags).")" : "")."
            ORDER BY p.`date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        return PostFeed::get_post_array($results);
    }
    
    public static function search_top($time = "week", $tags = array(), $page = 1, $limit = 15) {
        global $db;
        
        $safe_tags = array_map(function($tag) use ($db) {
            return "'".$db->real_escape_string($tag)."'";
        }, $tags);
        $from_date = PostFeed::get_from_date($time);
        
        $query = "
            SELECT DISTINCT(p.`post_id`), COUNT(up.`upvote_id`) AS `num_upvotes`
            FROM `posts` AS p
            ".($tags ? "JOIN `post_tags` AS t
            ON t.`post_id` = p.`post_id`" : "")."
            JOIN `upvotes` AS up
            ON up.`post_id` = p.`post_id`
            WHERE up.`date_created` >= '".$db->real_escape_string($from_date)."'
            ".($tags ? "AND t.`tag` IN (".implode(',', $safe_tags).")" : "")."
            ORDER BY `num_upvotes` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        return PostFeed::get_post_array($results);
    }
    
    public static function search_trending($tags = array(), $page = 1, $limit = 15) {
        global $db;
        
        $safe_tags = array_map(function($tag) use ($db) {
            return "'".$db->real_escape_string($tag)."'";
        }, $tags);
        
        $query = "
            SELECT DISTINCT(p.`post_id`),
                (COUNT(up.`upvote_id`) / (NOW() - p.`date_created`)) AS `score`
            FROM `posts` AS p
            ".($tags ? "JOIN `post_tags` AS t
            ON t.`post_id` = p.`post_id`" : "")."
            JOIN `upvotes` AS up
            ON up.`post_id` = p.`post_id`
            ".($tags ? "WHERE t.`tag` IN (".implode(',', $safe_tags).")" : "")."
            ORDER BY `score` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        return PostFeed::get_post_array($results);
    }
}
