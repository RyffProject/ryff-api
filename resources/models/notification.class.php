<?php

class Notification {
    public $id;
    public $text;
    public $is_read;
    public $date_created;
    
    protected function __construct($id, $text, $is_read,
            $date_read, $date_created, $object, $type) {
        
        $this->id = (int)$id;
        $this->text = $text;
        $this->is_read = (bool)$is_read;
        if ($this->is_read) {
            $this->date_read = $date_read;
        }
        $this->date_created = $date_created;
        $this->$type = $object;
    }
    
    public static function set_read($notification_id, $is_read) {
        global $db;
        $query = "
            UPDATE `notifications`
            SET `read`=".($is_read ? 1 : 0).",
                `date_read`=NOW()
            WHERE `notification_id`=".$db->real_escape_string((int)$notification_id);
        if ($db->query($query)) {
            return true;
        }
        return false;
    }
    
    public static function get_latest($page = 1, $limit = 15) {
        global $db, $CURRENT_USER;
        if (!$CURRENT_USER) {
            return null;
        }
        
        $query = "
            SELECT `notification_id`, `message_obj_id`, `post_obj_id`,
                `user_obj_id`, `text`, `read`, `date_read`, `date_created`
            FROM `notifications`
            WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id)."
            ORDER BY `date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        if ($results) {
            $notifications = array();
            while ($row = $results) {
                $object = null;
                $type = "object";
                if ($row['message_obj_id']) {
                    $object = Message::get_by_id((int)$row['message_obj_id']);
                    $type = "message";
                } else if ($row['post_obj_id']) {
                    $object = Post::get_by_id((int)$row['post_obj_id']);
                    $type = "post";
                } else if ($row['user_obj_id']) {
                    $object = User::get_by_id((int)$row['user_obj_id']);
                    $type = "user";
                }
                $notifications[] = new Notification(
                    $row['notification_id'], $row['text'], $row['read'],
                    $row['date_read'], $row['date_created'], $object, $type
                );
            }
            return $notifications;
        }
        return null;
    }
}
