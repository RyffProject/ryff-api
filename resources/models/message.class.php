<?php

class Message {
    public $id;
    public $user_id;
    public $content;
    public $is_read;
    public $date_created;
    
    protected function __construct($id, $user_id, $content, $is_read, $date_read, $date_created) {
        $this->id = (int)$id;
        $this->user_id = (int)$user_id;
        $this->content = $content;
        $this->is_read = (bool)$is_read;
        if ($this->is_read) {
            $this->date_read = $date_read;
        }
        $this->date_created = $date_created;
    }
    
    public static function create($row) {
        $required_keys = array(
            'message_id' => 0, 'from_id' => 0, 'content' => 0,
            'read' => 0, 'date_read' => 0, 'date_created' => 0
        );
        if (empty(array_diff_key($required_keys, $row))) {
            return new Message(
                $row['message_id'], $row['from_id'], $row['content'],
                $row['read'], $row['date_read'], $row['date_created']
            );
        }
        return null;
    }
    
    public static function get_by_id($message_id) {
        global $db;

        $query = "SELECT * FROM `messages`
                  WHERE `message_id`=".$db->real_escape_string((int)$message_id);
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            return Message::create($row);
        }
        
        return null;
    }
    
    public static function send($content, $to_id, $from_id = null) {
        global $db, $CURRENT_USER;
        
        if ($from_id === null && $CURRENT_USER) {
            $from_id = $CURRENT_USER->id;
        }
        
        $query = "
            INSERT INTO `messages` (`to_id`, `from_id`, `content`)
            VALUES (
                ".$db->real_escape_string((int)$to_id).",
                ".$db->real_escape_string((int)$from_id).",
                '".$db->real_escape_string($content)."'
            )";
        $results = $db->query($query);
        
        if ($results) {
            return true;
        }
        return false;
    }
    
    public static function get_conversation($from_id, $page, $limit, $to_id = null) {
        global $db, $CURRENT_USER;
        
        if ($to_id === null && $CURRENT_USER) {
            $to_id = $CURRENT_USER->id;
        }
        
        $set_read_query = "
            UPDATE `messages`
            SET `read`=1, `date_read`=NOW()
            WHERE (
                `from_id` = ".$db->real_escape_string((int)$from_id)."
                AND `to_id` = ".$db->real_escape_string((int)$to_id)."
            )";
        if (!$db->query($set_read_query)) {
            return null;
        }
        
        $query = "
            SELECT * FROM `messages`
            WHERE (
                `from_id` = ".$db->real_escape_string((int)$from_id)."
                AND `to_id` = ".$db->real_escape_string((int)$to_id)."
            ) OR (
                `from_id` = ".$db->real_escape_string((int)$to_id)."
                AND `to_id` = ".$db->real_escape_string((int)$from_id)."
            )
            ORDER BY `date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        if ($results) {
            $messages = array();
            while ($row = $results->fetch_assoc()) {
                $messages[] = Message::create($row);
            }
            return $messages;
        }
        return null;
    }
    
    public static function get_conversations_recent($page, $limit, $unread = false, $to_id = null) {
        global $db, $CURRENT_USER;
        
        if ($to_id === null && $CURRENT_USER) {
            $to_id = $CURRENT_USER->id;
        }
        
        //Sanitize once. This will be handled better with the switch to PDO
        //and prepared statements with named parameters.
        $to_id = (int)$to_id;
        
        $query = "
            SELECT m.*
            FROM (
                SELECT m1.*,
                    IF(m1.`from_id`=$to_id,m1.`to_id`,m1.`from_id`) AS `user_id`
                FROM `messages` AS m1
                WHERE m1.`from_id` = $to_id
                OR m1.`to_id` = $to_id
            ) AS m
            WHERE m.`date_created` = (
                SELECT m2.`date_created` FROM `messages` AS m2
                WHERE (
                    m2.`from_id` = $to_id
                    AND m2.`to_id` = m.`user_id`
                ) OR (
                    m2.`from_id` = m.`user_id`
                    AND m2.`to_id` = $to_id
                )
                ORDER BY m2.`date_created` DESC
                LIMIT 1
            )
            ".($unread ? "AND m.`user_id` != $to_id AND m.`read`=0" : "")."
            ORDER BY m.`date_created`
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        if ($results) {
            $conversations = array();
            while ($row = $results->fetch_assoc()) {
                $user = User::get_by_id($row['user_id']);
                $message = Message::create($row);
                if ($message->user_id === $user->id && !$message->is_read) {
                    $is_read = false;
                } else {
                    $is_read = true;
                }
                $conversations[] = array(
                    "user" => $user,
                    "message" => $message,
                    "is_read" => $is_read
                );
            }
            return $conversations;
        }
        return null;
    }
}
