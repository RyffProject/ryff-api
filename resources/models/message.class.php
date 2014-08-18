<?php

class Message {
    public $id;
    public $user_id;
    public $content;
    public $date_created;
    
    protected function __construct($id, $user_id, $content, $date_created) {
        $this->id = (int)$id;
        $this->user_id = (int)$user_id;
        $this->content = $content;
        $this->date_created = $date_created;
    }
    
    public static function create($row) {
        $required_keys = array(
            'message_id' => 0, 'from_id' => 0,
            'content' => 0, 'date_created' => 0
        );
        if (empty(array_diff_key($required_keys, $row))) {
            return new Message(
                $row['message_id'], $row['from_id'],
                $row['content'], $row['date_created']
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
    
    public static function get_conversation($to_id, $page, $limit, $from_id = null) {
        global $db, $CURRENT_USER;
        
        if ($from_id === null && $CURRENT_USER) {
            $from_id = $CURRENT_USER->id;
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
    
    public static function get_conversations_recent($page, $limit, $from_id = null) {
        global $db, $CURRENT_USER;
        
        if ($from_id === null && $CURRENT_USER) {
            $from_id = $CURRENT_USER->id;
        }
        
        //Sanitize once. This will be handled better with the switch to PDO
        //and prepared statements with named parameters.
        $from_id = (int)$from_id;
        
        $query = "
            SELECT m.*
            FROM (
                SELECT m1.*,
                    IF(m1.`from_id`=$from_id,m1.`to_id`,m1.`from_id`) AS `user_id`
                FROM `messages` AS m1
                WHERE m1.`from_id` = $from_id
                OR m1.`to_id` = $from_id
            ) AS m
            WHERE m.`date_created` = (
                SELECT m2.`date_created` FROM `messages` AS m2
                WHERE (
                    m2.`from_id` = $from_id
                    AND m2.`to_id` = m.`user_id`
                ) OR (
                    m2.`from_id` = m.`user_id`
                    AND m2.`to_id` = $from_id
                )
                ORDER BY m2.`date_created` DESC
                LIMIT 1
            )
            ORDER BY m.`date_created`
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        if ($results) {
            $conversations = array();
            while ($row = $results->fetch_assoc()) {
                $conversations[] = array(
                    "user" => User::get_by_id($row['user_id']),
                    "message" => Message::create($row)
                );
            }
            return $conversations;
        }
        return null;
    }
}
