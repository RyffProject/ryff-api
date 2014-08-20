<?php

class Tag {
    public $tag;
    public $num_users;
    public $num_posts;

    protected function __construct($tag) {
        $this->tag = $tag;

        $this->num_users = $this->get_num_users();
        $this->num_posts = $this->get_num_posts();
    }

    protected function get_num_users() {
        global $db;
        
        $users_query = "SELECT COUNT(*) AS `num_users` FROM `user_tags`
                          WHERE `tag`='".$db->real_escape_string($this->tag)."'";
        $users_results = $db->query($users_query);
        if ($users_results && $users_results->num_rows) {
            $users_row = $users_results->fetch_assoc();
            return (int)$users_row['num_users'];
        }
        
        return 0;
    }

    protected function get_num_posts() {
        global $db;
        
        $posts_query = "SELECT COUNT(*) AS `num_posts` FROM `post_tags`
                          WHERE `tag`='".$db->real_escape_string($this->tag)."'";
        $posts_results = $db->query($posts_query);
        if ($posts_results && $posts_results->num_rows) {
            $posts_row = $posts_results->fetch_assoc();
            return (int)$posts_row['num_posts'];
        }
        
        return 0;
    }
    
    public static function add_for_post($post_id, $content) {
        global $db;
        
        $tags = array();
        if (preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $tags)) {
            $post_tags_query = "INSERT INTO `post_tags` (`post_id`, `tag`) VALUES ";
            $post_tags_query_pieces = array();
            foreach ($tags[1] as $tag) {
                $post_tags_query_pieces[] = "(
                    ".$db->real_escape_string((int)$post_id).",
                    '".$db->real_escape_string($tag)."'
                )"; 
            }
            $post_tags_query .= implode(',', $post_tags_query_pieces);
            if ($db->query($post_tags_query)) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }
    
    public static function add_for_user($tag, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            INSERT INTO `user_tags` (`user_id`, `tag`)
            VALUES (
                ".$db->real_escape_string((int)$user_id).",
                '".$db->real_escape_string($tag)."'
            )";
        if ($db->query($query)) {
            return true;
        }
        return false;
    }
    
    public static function delete_from_user($tag, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            DELETE FROM `user_tags`
            WHERE `user_id` = ".$db->real_escape_string((int)$user_id)."
            AND `tag` = '".$db->real_escape_string($tag)."'";
        if ($db->query($query)) {
            return true;
        }
        return false;
    }

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
                $newTag = Tag::get_by_tag($row['tag']);
                $tags[] = $newTag;
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
                $newTag = Tag::get_by_tag($row['tag']);
                $tags[] = $newTag;
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
                $newTag = Tag::get_by_tag($row['tag']);
                $tags[] = $newTag;
            }
            return $tags;
        }
        return null;
    }

    public static function get_by_tag($tag) {
        return new Tag($tag);
    }
}
