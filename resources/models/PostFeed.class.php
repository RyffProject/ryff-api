<?php

/**
 * @class PostFeed
 * ===============
 * 
 * Provides static functions for getting a feed of posts.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */
class PostFeed {
    /**
     * Gets the Post objects from a given user in chronological order starting
     * with the most recent.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of Post objects or null on failure.
     */
    public static function get_user_latest($page = 1, $limit = 15, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT `post_id` FROM `posts`
            WHERE `user_id` = :user_id
            ORDER BY `date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        if ($sth->execute()) {
            $posts = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $posts[] = Post::get_by_id((int)$row['post_id']);
            }
            return $posts;
        }
        return null;
    }
    
    /**
     * Gets the Post objects from the users that the given user follows in
     * chronological order starting with the most recent.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of Post objects or null on failure.
     */
    public static function get_friends_latest($page = 1, $limit = 15, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT a.`post_id` FROM `posts` AS a
            JOIN `follows` AS b
            ON b.`to_id` = a.`user_id`
            AND b.`from_id` = :user_id
            ORDER BY a.`date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        if ($sth->execute()) {
            $posts = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $posts[] = Post::get_by_id((int)$row['post_id']);
            }
            return $posts;
        }
        return null;
    }
    
    /**
     * Gets Post objects in chronological order starting with the most recent,
     * optionally matching the given tags.
     * 
     * @global PDO $dbh
     * @param array $tags [optional]
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @return array|null An array of Post objects, or null on failure.
     */
    public static function search_latest($tags = array(), $page = 1, $limit = 15) {
        global $dbh;

        $query = "
            SELECT DISTINCT(p.`post_id`)
            FROM `posts` AS p
            ".($tags ? "JOIN `post_tags` AS t
            ON t.`post_id` = p.`post_id`
            WHERE t.`tag` IN (".implode(',', array_map(
                function($i) { return ':tag'.$i; },
                range(0, count($tags) - 1)
            )).")" : "")."
            ORDER BY p.`date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        foreach ($tags as $i => $tag) {
            $sth->bindValue('tag'.$i, $tag);
        }
        if ($sth->execute()) {
            $posts = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $posts[] = Post::get_by_id((int)$row['post_id']);
            }
            return $posts;
        }
        return null;
    }
    
    /**
     * Gets Post objects with the most upvotes in the given time frame,
     * optionally matching the given tags.
     * 
     * @global PDO $dbh
     * @param string $time [optional] "day", "week" (default), "month", or "all".
     * @param array $tags [optional]
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @return array|null An array of Post objects, or null on failure.
     */
    public static function search_top($time = "week", $tags = array(), $page = 1, $limit = 15) {
        global $dbh;
        
        $from_date = Util::get_from_date($time);
        
        $query = "
            SELECT DISTINCT(p.`post_id`), (
                    SELECT COUNT(*) FROM `upvotes`
                    WHERE `post_id` = p.`post_id`
                    AND `date_created` >= :from_date
                ) AS `num_upvotes`
            FROM `posts` AS p
            ".($tags ? "JOIN `post_tags` AS t
            ON t.`post_id` = p.`post_id`
            WHERE t.`tag` IN (".implode(',', array_map(
                function($i) { return ':tag'.$i; },
                range(0, count($tags) - 1)
            )).")" : "")."
            ORDER BY `num_upvotes` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindValue('from_date', $from_date);
        foreach ($tags as $i => $tag) {
            $sth->bindValue('tag'.$i, $tag);
        }
        if ($sth->execute()) {
            $posts = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                if (!$row['post_id']) {
                    continue;
                }
                $posts[] = Post::get_by_id((int)$row['post_id']);
            }
            return $posts;
        }
        return null;
    }
    
    /**
     * Gets Post the currently trending posts, optionally matching the given
     * tags.
     * 
     * @global PDO $dbh
     * @param array $tags [optional]
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @return array|null An array of Post objects, or null on failure.
     */
    public static function search_trending($tags = array(), $page = 1, $limit = 15) {
        global $dbh;
        
        $query = "
            SELECT DISTINCT(p.`post_id`), (
                    SELECT 1000000 * COUNT(*) FROM `upvotes`
                    WHERE `post_id` = p.`post_id`
                ) / (NOW() - p.`date_created`) AS `score`
            FROM `posts` AS p
            ".($tags ? "JOIN `post_tags` AS t
            ON t.`post_id` = p.`post_id`
            WHERE t.`tag` IN (".implode(',', array_map(
                function($i) { return ':tag'.$i; },
                range(0, count($tags) - 1)
            )).")" : "")."
            ORDER BY `score` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        foreach ($tags as $i => $tag) {
            $sth->bindValue('tag'.$i, $tag);
        }
        if ($sth->execute()) {
            $posts = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                if (!$row['post_id']) {
                    continue;
                }
                $posts[] = Post::get_by_id((int)$row['post_id']);
            }
            return $posts;
        }
        return null;
    }
}
