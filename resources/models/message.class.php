<?php

/**
 * @class Message
 * ==============
 * 
 * Provides a class for Message objects and static functions for sending
 * and receiving messages.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Message {
    /**
     * The message_id.
     * 
     * @var int
     */
    public $id;
    
    /**
     * The sender's user_id.
     * 
     * @var int
     */
    public $user_id;
    
    /**
     * The message text.
     * 
     * @var string
     */
    public $content;
    
    /**
     * Whether the message is read.
     * 
     * @var boolean
     */
    public $is_read;
    
    /**
     * The date the message was sent.
     * 
     * @var string
     */
    public $date_created;
    
    /**
     * Constructs a new Message instance with the given member variable values.
     * 
     * @param int $id
     * @param int $user_id
     * @param string $content
     * @param boolean $is_read
     * @param string $date_read
     * @param string $date_created
     */
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
    
    /**
     * Constructs and returns a Message instance from a database row.
     * 
     * @param array $row
     * @return Message|null
     */
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
    
    /**
     * Returns the message object with the given id, or null if it does not exist.
     * 
     * @global mysqli $db
     * @param int $message_id
     * @return Message|null
     */
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
    
    /**
     * Sends a message from one user to another.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param string $content
     * @param int $to_id
     * @param int $from_id [optional] Defaults to the current user.
     * @return boolean
     */
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
    
    /**
     * Gets messages in a conversation between two users and marks messages as
     * read for the user who is requesting them.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $from_id
     * @param int $page The page number of results.
     * @param int $limit The number of results per page.
     * @param int $to_id [optional] Defaults to the current user.
     * @return array|null An array of Message objects or null on failure.
     */
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
    
    /**
     * Gets the most recent conversations that a user is involved in. Each
     * conversation has the User involved, the most recent Message, and whether
     * the conversation is read. If $unread is true, only returns unread
     * conversations.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $page The page number of results.
     * @param int $limit The number of results per page.
     * @param boolean $unread [optional] Defaults to false.
     * @param int $to_id [optional] Defaults to the current user.
     * @return array|null An array of conversations or null on failure.
     */
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
