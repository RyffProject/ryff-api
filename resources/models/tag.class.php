<?php

class Tag {
    public static function search_users($query_str) {
        global $db;
        
        $query = "
            SELECT `tag` FROM `user_tags`
            WHERE `tag` LIKE '%".$db->real_escape_string($query_str)."%'
            GROUP BY `tag`
            ORDER BY COUNT(*) DESC
            LIMIT 10";
        $results = $db->query($query);
        
        if ($results) {
            $tags = array();
            while ($row = $results->fetch_assoc()) {
                $tags[] = $row['tag'];
            }
            return $tags;
        }
        return null;
    }
    
    public static function get_trending($from_date) {
        global $db;
        
        $query = "
            SELECT t.`tag`, COUNT(up.`upvote_id`) AS `score`
            FROM `post_tags` AS t
            JOIN `upvotes` AS up
            ON up.`post_id` = t.`post_id`
            WHERE t.`date_created` >= '".$db->real_escape_string($from_date)."'
            GROUP BY t.`tag`
            ORDER BY `score` DESC
            LIMIT 10";
        $results = $db->query($query);
        
        if ($results) {
            $tags = array();
            while ($row = $results->fetch_assoc()) {
                $tags[] = $row['tag'];
            }
            return $tags;
        }
        return null;
    }
    
    public static function get_suggested($user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "SELECT t.`tag`, COUNT(t.`tag`) AS `score`
                  FROM (
                    -- Tags on posts the user has upvoted
                    SELECT pt.`tag` AS `tag`
                    FROM `post_tags` AS pt
                    JOIN `posts` AS p
                    ON p.`post_id` = pt.`post_id`
                    JOIN `upvotes` AS up
                    ON up.`post_id` = p.`post_id`
                    WHERE up.`user_id` = ".$db->real_escape_string((int)$user_id)."

                    UNION ALL

                    -- Tags on posts by people the user follows
                    SELECT pt.`tag` AS `tag`
                    FROM `post_tags` AS pt
                    JOIN `posts` AS p
                    ON p.`post_id` = pt.`post_id`
                    JOIN `users` AS u
                    ON u.`user_id` = p.`user_id`
                    JOIN `follows` AS f
                    ON f.`from_id` = p.`user_id`
                    WHERE f.`from_id` = ".$db->real_escape_string((int)$user_id)."

                    UNION ALL

                    -- Tags on people the user follows
                    SELECT ut.`tag` AS `tag`
                    FROM `user_tags` AS ut
                    JOIN `users` AS u
                    ON u.`user_id` = ut.`user_id`
                    JOIN `follows` AS f
                    ON f.`from_id` = u.`user_id`
                    WHERE f.`from_id` = ".$db->real_escape_string((int)$user_id)."
                  ) AS t
                  GROUP BY t.`tag`
                  ORDER BY `score` DESC
                  LIMIT 10";
        $results = $db->query($query);
        
        if ($results) {
            $tags = array();
            while ($row = $results->fetch_assoc()) {
                $tags[] = $row['tag'];
            }
            return $tags;
        }
        return null;
    }
}
