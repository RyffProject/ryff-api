<?php

/**
 * @class Conversation
 * ===================
 * 
 * Provides a class for Conversation objects and static functions related to
 * conversations.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Conversation {
    /**
     * The conversation_id.
     * 
     * @var int
     */
    public $id;
    
    /**
     * An array of User objects that are members of the conversation.
     * 
     * @var array
     */
    public $users;
    
    /**
     * The latest Message object in the conversation, or null if not found.
     * 
     * @var Message|null
     */
    public $latest;
    
    /**
     * Whether the message has been read by the current user.
     * 
     * @var boolean
     */
    public $is_read;
    
    /**
     * Constructs a conversation objects with the given member variables.
     * 
     * @param array $users An array of User objects.
     * @param Message $latest
     * @param boolean $is_read
     */
    protected function __construct($conversation_id) {
        $this->id = (int)$conversation_id;
        
        $this->users = $this->get_users();
        $this->latest = $this->get_latest();
        $this->is_read = $this->get_is_read();
    }
    
    /**
     * Helper function that returns an array of User objects that are participants
     * in the conversation.
     * 
     * @global mysqli $db
     * @return array
     */
    protected function get_users() {
        global $db;
        
        $query = "
            SELECT u.`user_id`, u.`name`, u.`username`,
                u.`email`, u.`bio`, u.`date_created`
            FROM `users` AS u
            JOIN `conversation_members` AS m
            ON m.`user_id` = u.`user_id`
            WHERE m.`conversation_id` = ".$db->real_escape_string($this->db);
        $results = $db->query($query);
        
        $users = array();
        if ($results) {
            while ($row = $results->fetch_assoc()) {
                $users[] = User::create($row);
            }
        }
        return $users;
    }
    
    /**
     * Helper function that returns the latest Message object in the
     * conversation, or null if it doesn't exist.
     * 
     * @global mysqli $db
     * @return Message|null
     */
    protected function get_latest() {
        global $db;
        
        $query = "
            SELECT m.`message_id`, m.`user_id`, m.`content`, m.`date_created`
            FROM `messages` AS m
            WHERE m.`conversation_id` = {$this->id}
            ORDER BY m.`date_created` DESC
            LIMIT 1";
        $results = $db->query($query);
        if ($results && $results->num_rows) {
            $row = $results->fetch_assoc();
            return Message::create($row);
        }
        return null;
    }
    
    /**
     * Helper function that returns whether this conversation has been read
     * by the current user since it was last updated.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @return boolean
     */
    protected function get_is_read() {
        global $db, $CURRENT_USER;
        
        if (!$CURRENT_USER) {
            return false;
        }
        
        $query = "
            SELECT (m.`date_last_read` > c.`date_updated`) AS `read`
            FROM `conversations` AS c
            JOIN `conversation_members` AS m
            ON m.`conversation_id` = c.`conversation_id`
            WHERE m.`user_id` = {$CURRENT_USER->id}";
        $results = $db->query($query);
        if ($results && $results->num_rows) {
            $row = $results->fetch_assoc();
            return (bool)$row['read'];
        }
        return false;
    }

    /**
     * Returns a Conversation object corresponding to the given conversation_id,
     * or null if it isn't found.
     * 
     * @global mysqli $db
     * @param int $conversation_id
     * @return Conversation|null
     */
    public static function get_by_id($conversation_id) {
        global $db;
        
        $query = "SELECT 1 FROM `conversations` WHERE `conversation_id`=".((int)$conversation_id);
        $results = $db->query($query);
        if ($results && $results->num_rows) {
            return new Conversation($conversation_id);
        }
        return null;
    }
}
