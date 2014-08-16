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
}
