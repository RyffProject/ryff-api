<?php

/**
 * @class UserFeed
 * ===============
 * 
 * Provides static functions for getting a feed of users.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class UserFeed {
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
     * Returns an array of User objects sorted by proximity to the given user.
     * The users can optionally match an array of tags.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param Point $location The latitude and longitude coordinates that are
     *                        being queried.
     * @param array $tags [optional] Tags that the returned users should match.
     * @param int $page [optional] The page number of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of User objects, or null on failure.
     */
    public static function search_nearby(Point $location, $tags = array(),
            $page = 1, $limit = 15, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT DISTINCT(u.`user_id`), u.`name`, u.`username`, u.`email`, u.`bio`, u.`date_created`,
            SQRT(POW(X(l.`location`) - :x, 2) + POW(Y(l.`location`) - :y, 2)) AS `distance`
            FROM `users` AS u
            ".($tags ? "JOIN `user_tags` AS t
            ON t.`user_id` = u.`user_id`" : "")."
            JOIN `locations` AS l
            ON l.`user_id` = u.`user_id`
            WHERE l.`date_created`=(
                SELECT MAX(l2.`date_created`) 
                FROM `locations` AS l2 
                WHERE l2.`user_id`= l.`user_id`
            )
            ".($tags ? "AND t.`tag` IN (".implode(',', array_map(
                function($i) { return ':tag'.$i; },
                range(0, count($tags) - 1)
            )).")" : "")."
            AND l.`user_id` != :user_id
            ORDER BY `distance` ASC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindParam('x', $location->x);
        $sth->bindParam('y', $location->y);
        $sth->bindParam('user_id', $CURRENT_USER->id);
        foreach ($tags as $i => $tag) {
            $sth->bindValue('tag'.$i, $tag);
        }
        if ($sth->execute()) {
            $users = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                if (!$row['user_id']) {
                    continue;
                }
                $users[] = User::create($row);
            }
            return $users;
        }
        return null;
    }
    
    /**
     * Gets User objects with the most karma in the given time frame,
     * optionally matching the given tags.
     * 
     * @global PDO $dbh
     * @param string $time [optional] "day", "week" (default), "month", or "all".
     * @param array $tags [optional]
     * @param int $page [optional] The page number of results, defaults to 1.
     * @param int $limit [optional] The number of results per page defaults to 15.
     * @return array|null An array of User objects, or null on failure.
     */
    public static function search_trending($time = "week", $tags = array(), $page = 1, $limit = 15) {
        global $dbh;
        
        $from_date = UserFeed::get_from_date($time);
        
        $query = "
            SELECT DISTINCT(u.`user_id`), u.`name`, u.`username`,
                u.`email`, u.`bio`, u.`date_created`,
                COUNT(up.`upvote_id`) AS `num_upvotes`
            FROM `users` AS u
            ".($tags ? "JOIN `user_tags` AS t
            ON t.`user_id` = u.`user_id`" : "")."
            JOIN `posts` AS p
            ON p.`user_id` = u.`user_id`
            JOIN `upvotes` AS up
            ON up.`post_id` = p.`post_id`
            WHERE up.`date_created` >= :from_date
            ".($tags ? "AND t.`tag` IN (".implode(',', array_map(
                function($i) { return ':tag'.$i; },
                range(0, count($tags) - 1)
            )).")" : "")."
            ORDER BY `num_upvotes` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindParam('from_date', $from_date);
        foreach ($tags as $i => $tag) {
            $sth->bindValue('tag'.$i, $tag);
        }
        if ($sth->execute()) {
            $users = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                if (!$row['user_id']) {
                    continue;
                }
                $users[] = User::create($row);
            }
            return $users;
        }
        return null;
    }
}
