<?php

/**
 * @class Tag
 * ==========
 * 
 * Provides a class for Tag objects and static functions related to tags.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Tag {
    /**
     * The tag.
     * 
     * @var string
     */
    public $tag;
    
    /**
     * The number of users that have this tag.
     * 
     * @var int
     */
    public $num_users;
    
    /**
     * The number of posts that have this tag.
     * 
     * @var int
     */
    public $num_posts;

    /**
     * Constructs a new Tag object.
     * 
     * @param string $tag
     */
    protected function __construct($tag) {
        $this->tag = $tag;

        $this->num_users = $this->get_num_users();
        $this->num_posts = $this->get_num_posts();
    }

    /**
     * Helper function that returns the number of users who have this tag.
     * 
     * @global PDO $dbh
     * @return int The number of users who have this tag.
     */
    protected function get_num_users() {
        global $dbh;
        
        $query = "
            SELECT COUNT(*) AS `num_users`
            FROM `user_tags`
            WHERE `tag` = :tag";
        $sth = $dbh->prepare($query);
        $sth->bindValue('tag', $this->tag);
        $sth->execute();
        return (int)$sth->fetchColumn();
    }

    /**
     * Helper function that returns the number of posts that have this tag.
     * 
     * @global PDO $dbh
     * @return int The number of posts that have this tag.
     */
    protected function get_num_posts() {
        global $dbh;
        
        $query = "
            SELECT COUNT(*) AS `num_posts`
            FROM `post_tags`
            WHERE `tag` = :tag";
        $sth = $dbh->prepare($query);
        $sth->bindValue('tag', $this->tag);
        $sth->execute();
        return (int)$sth->fetchColumn();
    }
    
    /**
     * Parses a post's content and adds tags that are preceded by a #.
     * 
     * @global PDO $dbh
     * @param int $post_id
     * @param string $content
     * @return boolean
     */
    public static function add_for_post($post_id, $content) {
        global $dbh;
        
        $tags = array();
        if (preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $tags)) {
            $tags = array_values(array_unique($tags[1]));
            $query = "
                INSERT INTO `post_tags` (`post_id`, `tag`)
                VALUES ".implode(',', array_map(
                    function($i) { return "(:post_id, :tag$i)"; },
                    range(0, count($tags) - 1)
                ));
            $sth = $dbh->prepare($query);
            $sth->bindValue('post_id', $post_id);
            foreach ($tags as $i => $tag) {
                $sth->bindValue('tag'.$i, $tag);
            }
            if ($sth->execute()) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Adds a single tag to the given user.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param string $tag
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function add_for_user($tag, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            INSERT IGNORE INTO `user_tags` (`user_id`, `tag`)
            VALUES (:user_id, :tag)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->bindValue('tag', $tag);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Deletes a single tag from the given user.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param string $tag
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function delete_from_user($tag, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            DELETE FROM `user_tags`
            WHERE `user_id` = :user_id
            AND `tag` = :tag";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->bindValue('tag', $tag);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array of the 10 most popular user Tag objects that match the
     * given query.
     * 
     * @global PDO $dbh
     * @param string $query_str The text that the matched tags should contain.
     * @return array|null An array of Tag objects, or null on failure.
     */
    public static function search_users($query_str) {
        global $dbh;
        
        $query = "
            SELECT `tag` FROM `user_tags`
            WHERE `tag` LIKE :query_str
            GROUP BY `tag`
            ORDER BY COUNT(*) DESC
            LIMIT 10";
        $sth = $dbh->prepare($query);
        $sth->bindValue('query_str', '%'.$query_str.'%');
        if ($sth->execute()) {
            $tags = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $tags[] = Tag::get_by_tag($row['tag']);
            }
            return $tags;
        }
        return null;
    }
    
    /**
     * Returns an array of the 10 most popular post Tag objects since a given
     * date. Popularity is measured by number of upvotes on posts with that tag
     * attached.
     * 
     * @global PDO $dbh
     * @param string $time One of "day", "week", "month", or "all".
     * @return array|null An array of Tag objects, or null on failure.
     */
    public static function get_trending($time) {
        global $dbh;
        
        $from_date = Util::get_from_date($time);
        $query = "
            SELECT t.`tag`, COUNT(up.`upvote_id`) AS `score`
            FROM `post_tags` AS t
            JOIN `upvotes` AS up
            ON up.`post_id` = t.`post_id`
            WHERE t.`date_created` >= :from_date
            GROUP BY t.`tag`
            ORDER BY `score` DESC
            LIMIT 10";
        $sth = $dbh->prepare($query);
        $sth->bindValue('from_date', $from_date);
        if ($sth->execute()) {
            $tags = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $tags[] = Tag::get_by_tag($row['tag']);
            }
            return $tags;
        }
        return null;
    }
    
    /**
     * Returns an array of up to 10 tags that the given user would want to see
     * based on their current tags, their posts, posts they've upvoted, etc.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of Tag objects, or null on failure.
     */
    public static function get_suggested($user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT t.`tag`, COUNT(t.`tag`) AS `score`
            FROM (
                -- Tags on posts the user has upvoted
                SELECT pt.`tag` AS `tag`
                FROM `post_tags` AS pt
                JOIN `posts` AS p
                ON p.`post_id` = pt.`post_id`
                JOIN `upvotes` AS up
                ON up.`post_id` = p.`post_id`
                WHERE up.`user_id` = :user_id

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
                WHERE f.`from_id` = :user_id

                UNION ALL

                -- Tags on people the user follows
                SELECT ut.`tag` AS `tag`
                FROM `user_tags` AS ut
                JOIN `users` AS u
                ON u.`user_id` = ut.`user_id`
                JOIN `follows` AS f
                ON f.`from_id` = u.`user_id`
                WHERE f.`from_id` = :user_id
            ) AS t
            GROUP BY t.`tag`
            ORDER BY `score` DESC
            LIMIT 10";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        if ($sth->execute()) {
            $tags = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $tags[] = Tag::get_by_tag($row['tag']);
            }
            return $tags;
        }
        return null;
    }

    /**
     * Returns a Tag object with the given tag.
     * 
     * @param string $tag
     * @return Tag
     */
    public static function get_by_tag($tag) {
        return new Tag($tag);
    }
}
