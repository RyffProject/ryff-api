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
            WHERE `notification_id`=".$db->real_escape_string($this->id);
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
            SELECT `notification_id`, `type`, `read`,
                `date_read`, `date_updated`, `date_created`
            FROM `notifications`
            WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id)."
            ORDER BY `date_created` DESC
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
