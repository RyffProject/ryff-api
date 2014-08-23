<?php

class Notification {
    public $id;
    public $type;
    public $is_read;
    public $date_updated;
    public $date_created;
    
    protected function __construct($id, $type, $is_read,
            $date_read, $date_updated, $date_created) {
        
        $this->id = (int)$id;
        $this->type = $type;
        $this->is_read = (bool)$is_read;
        if ($this->is_read) {
            $this->date_read = $date_read;
        }
        $this->date_updated = $date_updated;
        $this->date_created = $date_created;
        
        $this->get_objects();
    }
    
    protected function get_objects() {
        global $db;
        
        $base_query = "
            SELECT `user_obj_id`, `post_obj_id` FROM `notifications`
            WHERE `notification_id`=".$db->real_escape_string($this->id);
        $base_results = $db->query($base_query);
        if ($base_results && $base_results->num_rows) {
            $base_row = $base_results->fetch_assoc();
            if ($base_row['post_obj_id'] && $post = Post::get_by_id($base_row['post_obj_id'])) {
                $this->post = $post;
            }
            if ($base_row['user_obj_id'] && $user = User::get_by_id($base_row['user_obj_id'])) {
                $this->user = $user;
            }
        }
        
        $leaves_query = "
            SELECT `user_obj_id`, `post_obj_id` FROM `notification_objects`
            WHERE `notification_id`=".$db->real_escape_string($this->id)."
            ORDER BY `date_created` DESC";
        $leaves_results = $db->query($leaves_query);
        if ($leaves_results) {
            while ($leaf_row = $leaves_results->fetch_assoc()) {
                if ($leaf_row['post_obj_id'] && $post = Post::get_by_id($leaf_row['post_obj_id'])) {
                    $this->posts[] = $post;
                }
                if ($leaf_row['user_obj_id'] && $user = User::get_by_id($leaf_row['user_obj_id'])) {
                    $this->users[] = $user;
                }
            }
        }
    }
    
    protected static function create($row) {
        return new Notification(
            $row['notification_id'], $row['type'], $row['read'],
            $row['date_read'], $row['date_updated'], $row['date_created']
        );
    }
    
    public static function add($user_id, $type, $base_post_obj_id,
            $base_user_obj_id, $leaf_post_obj_id, $leaf_user_obj_id) {
        global $db;
        
        $stack_query = "
            SELECT `notification_id` FROM `notifications`
            WHERE `user_id`=".$db->real_escape_string((int)$user_id)."
            AND `type`='".$db->real_escape_string($type)."'
            AND `post_obj_id`".($base_post_obj_id ? "=".$db->real_escape_string((int)$base_post_obj_id) : " IS NULL")."
            AND `user_obj_id`".($base_user_obj_id ? "=".$db->real_escape_string((int)$base_user_obj_id) : " IS NULL")."
            AND `date_updated` > (NOW() -".NOTIFICATION_TIMEOUT.")";
        $stack_results = $db->query($stack_query);
        if (!$stack_results) {
            return null;
        }
        if ($stack_results->num_rows) {
            $stack_row = $stack_results->fetch_assoc();
            $notification_id = (int)$stack_row['notification_id'];
            $base_query = "
                UPDATE `notifications`
                SET `read`=0, `date_read`=0, `date_updated`=NOW()
                WHERE `notification_id`=".$db->real_escape_string($notification_id);
            if (!$db->query($base_query)) {
                return null;
            }
        } else {
            $base_query = "
                INSERT INTO `notifications` (
                    `user_id`, `type`, `post_obj_id`, `user_obj_id`, `date_updated`
                ) VALUES (
                    ".$db->real_escape_string((int)$user_id).",
                    '".$db->real_escape_string($type)."',
                    ".($base_post_obj_id ? $db->real_escape_string((int)$base_post_obj_id) : "NULL").",
                    ".($base_user_obj_id ? $db->real_escape_string((int)$base_user_obj_id) : "NULL").",
                    NOW()
                )";
            if (!$db->query($base_query)) {
                return null;
            }
            $notification_id = $db->insert_id;
        }
        
        $leaf_query = "
            INSERT INTO `notification_objects` (
                `notification_id`, `post_obj_id`, `user_obj_id`
            ) VALUES (
                ".$db->real_escape_string($notification_id).",
                ".($leaf_post_obj_id ? $db->real_escape_string((int)$leaf_post_obj_id) : "NULL").",
                ".($leaf_user_obj_id ? $db->real_escape_string((Int)$leaf_user_obj_id) : "NULL")."
            )";
        if ($db->query($leaf_query)) {
            return Notification::get_by_id($notification_id, $user_id);
        }
        return null;
    }
    
    public static function delete($user_id, $type, $base_post_obj_id,
            $base_user_obj_id, $leaf_post_obj_id, $leaf_user_obj_id) {
        global $db;
        
        $query = "
            DELETE obj FROM `notification_objects` AS obj
            JOIN `notifications` AS n ON n.`notification_id`=obj.`notification_id`
            WHERE n.`user_id`=".$db->real_escape_string((int)$user_id)."
            AND n.`type`='".$db->real_escape_string($type)."'
            AND n.`post_obj_id`".($base_post_obj_id ? "=".((int)$base_post_obj_id) : " IS NULL")."
            AND n.`user_obj_id`".($base_user_obj_id ? "=".((int)$base_user_obj_id) : " IS NULL")."
            AND obj.`post_obj_id`".($leaf_post_obj_id ? "=".((int)$leaf_post_obj_id) : " IS NULL")."
            AND obj.`user_obj_id`".($leaf_user_obj_id ? "=".((int)$leaf_user_obj_id) : " IS NULL");
        if ($db->query($query)) {
            return true;
        }
        return false;
    }
    
    public static function add_mentions($post_id, $content) {
        $usernames = array();
        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $usernames)) {
            $post = Post::get_by_id($post_id);
            foreach (array_unique($usernames[1]) as $username) {
                $user = User::get_by_username($username);
                if (!$user || $user->id === $post->user->id) {
                    continue;
                }
                if (!Notification::add($user->id, "mention", null, null, $post->id, null)) {
                    return false;
                }
            }
        }
        return true;
    }
    
    public static function set_read($notification_id) {
        global $db;
        $query = "
            UPDATE `notifications`
            SET `read`=1, `date_read`=NOW()
            WHERE `notification_id`=".$db->real_escape_string((int)$notification_id);
        if ($db->query($query)) {
            return true;
        }
        return false;
    }
    
    public static function get_by_id($notification_id, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT `notification_id`, `post_obj_id`, `user_obj_id`,
                `text`, `read`, `date_read`, `date_created`
            FROM `notifications`
            WHERE `notification_id`=".$db->real_escape_string((int)$notification_id)."
            AND `user_id`=".$db->real_escape_string((int)$user_id);
        $result = $db->query($query);
        
        if ($result && $result->num_rows) {
            $row = $result->fetch_assoc();
            return Notification::create($row);
        }
        return null;
    }
    
    public static function get_latest($page = 1, $limit = 15) {
        global $db, $CURRENT_USER;
        if (!$CURRENT_USER) {
            return null;
        }
        
        $query = "
            SELECT n.`notification_id`, n.`type`, n.`read`,
                n.`date_read`, n.`date_updated`, n.`date_created`
            FROM `notifications` AS n
            JOIN `notification_objects` AS obj
            ON obj.`notification_id`=n.`notification_id`
            WHERE n.`user_id`=".$db->real_escape_string($CURRENT_USER->id)."
            GROUP BY obj.`notification_id`
            ORDER BY n.`date_updated` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        if ($results) {
            $notifications = array();
            while ($row = $results->fetch_assoc()) {
                $notifications[] = Notification::create($row);
            }
            return $notifications;
        }
        return null;
    }
}
