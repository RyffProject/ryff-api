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
            SELECT (m.`date_last_read` >= c.`date_updated`) AS `read`
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
     * Creates and returns a new Conversation object with the given users
     * as participants.
     * 
     * @global mysqli $db
     * @param array $user_ids Cannot be less than two unique ids.
     * @return Conversation|null The new conversation, or null on failure.
     */
    public static function add($user_ids) {
        global $db;
        
        if (!is_array($user_ids)) {
            return null;
        }
        $user_ids = array_unique($user_ids);
        if (count($user_ids) < 2) {
            return null;
        }
        
        $conversation_query = "
            INSERT INTO `conversations` (`date_updated`) VALUES (NOW())";
        $conversation_results = $db->query($conversation_query);
        if (!$conversation_results) {
            return null;
        }
        
        $conversation_id = $db->insert_id;
        $members_query = "
            INSERT INTO `conversation_members` (
                `conversation_id`, `user_id`, `date_last_updated`
            ) VALUES ";
        $member_query_pieces = array();
        foreach ($user_ids as $user_id) {
            $member_query_pieces[] = "(
                    ".$db->real_escape_string((int)$conversation_id).",
                    ".$db->real_escape_string((int)$user_id).",
                    NOW()
                )";
        }
        $members_query .= implode(',', $member_query_pieces);
        $members_results = $db->query($members_query);
        if (!$members_results) {
            Conversation::delete($conversation_id);
            return null;
        }
        
        return new Conversation($conversation_id);
    }
    
    /**
     * Deletes the conversation with the given id.
     * 
     * @global mysqli $db
     * @param int $conversation_id
     * @return boolean
     */
    public static function delete($conversation_id) {
        global $db;
        
        $query = "
            DELETE FROM `conversations`
            WHERE `conversation_id` = ".((int)$conversation_id);
        $results = $db->query($query);
        if ($results) {
            return true;
        }
        return false;
    }
    
    /**
     * Sets the conversation as read for the given user.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $conversation_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function set_read($conversation_id, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            UPDATE `conversation_members`
            SET `date_last_read` = NOW()
            WHERE `conversation_id` = ".((int)$conversation_id)."
            AND `user_id` = ".((int)$user_id);
        if ($db->query($query)) {
            return true;
        }
        return false;
    }

    /**
     * Returns a Conversation object corresponding to the given conversation_id,
     * if the given user is a participant. Otherwise returns null.
     * 
     * @global mysqli $db
     * @param int $conversation_id
     * @return Conversation|null
     */
    public static function get_by_id($conversation_id, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT 1 FROM `conversations` AS c
            JOIN `conversation_members` AS m
            ON m.`conversation_id` = c.`conversation_id`
            WHERE c.`conversation_id`=".((int)$conversation_id)."
            AND m.`user_id` = ".((int)$user_id);
        $results = $db->query($query);
        if ($results && $results->num_rows) {
            return new Conversation($conversation_id);
        }
        return null;
    }
    
    /**
     * Gets the existing Conversation object between the two users, or creates
     * and returns one if it doesn't already exist.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $to_id The first user's id.
     * @param int $from_id [optional] The second user's id, defaults to the current user.
     * @return Conversation|null The Conversation object, or null on failure.
     */
    public static function get_for_user($to_id, $from_id = null) {
        global $db, $CURRENT_USER;
        
        if ($from_id === null && $CURRENT_USER) {
            $from_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT c.`conversation_id` FROM `conversations` AS c
            JOIN `conversation_members` AS m
            ON m.`conversation_id` = c.`conversation_id`
            WHERE COUNT(m.`user_id`) = 2
            AND (
                m.`user_id` = ".((int)$to_id)."
                OR m.`user_id` = ".((int)$from_id)."
            )
            GROUP BY m.`conversation_id`";
        $results = $db->query($query);
        if ($results && $results->num_rows) {
            $row = $results->fetch_assoc();
            return new Conversation((int)$row['conversation_id']);
        } else {
            return Conversation::add(array($to_id, $from_id));
        }
    }
    
    /**
     * Gets the most recent conversations that a user is involved in.
     * If $unread is true, only returns unread conversations.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $page The page number of results.
     * @param int $limit The number of results per page.
     * @param boolean $unread [optional] Defaults to false.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null The array of Conversation objects, or null on failure.
     */
    public static function get_conversations_recent($page, $limit,
            $unread = false, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT c.`conversation_id` FROM `conversations` AS c
            JOIN `conversation_members` AS m
            ON m.`conversation_id` = c.`conversation_id`
            WHERE m.`user_id` = ".((int)$user_id)."
            ".($unread ? "AND c.`date_updated` > m.`date_last_read`" : "")."
            GROUP BY m.`conversation_id`
            ORDRE BY c.`date_updated` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        if ($results) {
            $conversations = array();
            while ($row = $results->fetch_assoc()) {
                $conversations[] = new Conversation((int)$row['conversation_id']);
            }
            return $conversations;
        }
        return null;
    }
}
