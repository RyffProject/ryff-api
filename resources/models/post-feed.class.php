<?php

/**
 * @class PostFeed
 * ===============
 * 
 * Provides static functions for getting a feed of posts.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class PostFeed {
    /**
     * Helper function that gets an array of posts from a mysqli_result object
     * with rows containing the post_id column.
     * 
     * @param mysqli_result $query_results
     * @return array|null An array of Post objects or null on failure.
     */
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
    
    /**
     * Helper function that gets a timestamp that represents one day ago, or
     * one week ago, etc.
     * 
     * @param string $time One of "day", "week", "month", or "all".
     * @return string The timestamp.
     */
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
    
    /**
     * Gets the Post objects from a given user in chronological order starting
     * with the most recent.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of Post objects or null on failure.
     */
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
    
    /**
     * Gets the Post objects from the users that the given user follows in
     * chronological order starting with the most recent.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of Post objects or null on failure.
     */
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
    
    /**
     * Gets Post objects in chronological order starting with the most recent,
     * optionally matching the given tags.
     * 
     * @global mysqli $db
     * @param array $tags [optional]
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @return array|null An array of Post objects, or null on failure.
     */
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
    
    /**
     * Gets Post objects with the most upvotes in the given time frame,
     * optionally matching the given tags.
     * 
     * @global mysqli $db
     * @param string $time [optional] "day", "week" (default), "month", or "all".
     * @param array $tags [optional]
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @return array|null An array of Post objects, or null on failure.
     */
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
    
    /**
     * Gets Post the currently trending posts, optionally matching the given
     * tags.
     * 
     * @global mysqli $db
     * @param array $tags [optional]
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @return array|null An array of Post objects, or null on failure.
     */
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
