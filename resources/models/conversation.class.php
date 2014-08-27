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
     * @global PDO $dbh
     * @return array
     */
    protected function get_users() {
        global $dbh;
        
        $query = "
            SELECT u.`user_id`, u.`name`, u.`username`,
                u.`email`, u.`bio`, u.`date_created`
            FROM `users` AS u
            JOIN `conversation_members` AS m
            ON m.`user_id` = u.`user_id`
            WHERE m.`conversation_id` = :conversation_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('conversation_id', $this->id);
        
        $users = array();
        if ($sth->execute()) {
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $users[] = User::create($row);
            }
        }
        return $users;
    }
    
    /**
     * Helper function that returns the latest Message object in the
     * conversation, or null if it doesn't exist.
     * 
     * @global PDO $dbh
     * @return Message|null
     */
    protected function get_latest() {
        global $dbh;
        
        $query = "
            SELECT m.`message_id`, m.`user_id`, m.`content`, m.`date_created`
            FROM `messages` AS m
            WHERE m.`conversation_id` = :conversation_id
            ORDER BY m.`date_created` DESC
            LIMIT 1";
        $sth = $dbh->prepare($query);
        $sth->bindParam('conversation_id', $this->id);
        if ($sth->execute() && $sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return Message::create($row);
        }
        return null;
    }
    
    /**
     * Helper function that returns whether this conversation has been read
     * by the current user since it was last updated.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @return boolean
     */
    protected function get_is_read() {
        global $dbh, $CURRENT_USER;
        
        if (!$CURRENT_USER) {
            return false;
        }
        
        $query = "
            SELECT (m.`date_last_read` >= c.`date_updated`) AS `read`
            FROM `conversations` AS c
            JOIN `conversation_members` AS m
            ON m.`conversation_id` = c.`conversation_id`
            WHERE m.`user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('user_id', $CURRENT_USER->id);
        return (bool)$sth->fetchColumn();
    }
    
    /**
     * Creates and returns a new Conversation object with the given users
     * as participants.
     * 
     * @global PDO $dbh
     * @param array $user_ids Cannot be less than two unique ids.
     * @return Conversation|null The new conversation, or null on failure.
     */
    public static function add($user_ids) {
        global $dbh;
        
        if (!is_array($user_ids)) {
            return null;
        }
        $user_ids = array_unique($user_ids);
        if (count($user_ids) < 2) {
            return null;
        }
        
        $conversation_query = "
            INSERT INTO `conversations` (`date_updated`) VALUES (NOW())";
        if (!$dbh->exec($conversation_query)) {
            return null;
        }
        $conversation_id = $dbh->lastInsertId();
        
        $members_query = "
            INSERT INTO `conversation_members` (
                `conversation_id`, `user_id`, `date_last_updated`
            ) VALUES ".implode(',', array_map(
                function($i) { return "(:conversation_id, :user_id$i, NOW())"; },
                range(0, count($user_ids) - 1)
            ));
        $members_sth = $dbh->prepare($members_query);
        $members_sth->bindParam('conversation_id', $conversation_id);
        foreach ($user_ids as $i => $user_id) {
            $members_sth->bindValue('user_id'.$i, $user_id);
        }
        if (!$members_sth->execute()) {
            Conversation::delete($conversation_id);
            return null;
        }
        
        return new Conversation($conversation_id);
    }
    
    /**
     * Deletes the conversation with the given id.
     * 
     * @global PDO $dbh
     * @param int $conversation_id
     * @return boolean
     */
    public static function delete($conversation_id) {
        global $dbh;
        
        $query = "
            DELETE FROM `conversations`
            WHERE `conversation_id` = :conversation_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('conversation_id', $conversation_id);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Removes a member with the given user_id from the given conversation.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $conversation_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function delete_member($conversation_id, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            DELETE FROM `conversation_members`
            WHERE `conversation_id` = :conversation_id
            AND `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('conversation_id', $conversation_id);
        $sth->bindParam('user_id', $user_id);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Sets the conversation as read for the given user.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $conversation_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function set_read($conversation_id, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            UPDATE `conversation_members`
            SET `date_last_read` = NOW()
            WHERE `conversation_id` = :conversation_id
            AND `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('conversation_id', $conversation_id);
        $sth->bindParam('user_id', $user_id);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }

    /**
     * Returns a Conversation object corresponding to the given conversation_id,
     * if the given user is a participant. Otherwise returns null.
     * 
     * @global PDO $dbh
     * @param int $conversation_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return Conversation|null
     */
    public static function get_by_id($conversation_id, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT 1 FROM `conversations` AS c
            JOIN `conversation_members` AS m
            ON m.`conversation_id` = c.`conversation_id`
            WHERE c.`conversation_id` = :conversation_id
            AND m.`user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('conversation_id', $conversation_id);
        $sth->bindParam('user_id', $user_id);
        if ($sth->fetchColumn()) {
            return new Conversation($conversation_id);
        }
        return null;
    }
    
    /**
     * Gets the most recent conversations that a user is involved in.
     * If $unread is true, only returns unread conversations.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $page The page number of results.
     * @param int $limit The number of results per page.
     * @param boolean $unread [optional] Defaults to false.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null The array of Conversation objects, or null on failure.
     */
    public static function get_conversations_recent($page, $limit,
            $unread = false, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT c.`conversation_id` FROM `conversations` AS c
            JOIN `conversation_members` AS m
            ON m.`conversation_id` = c.`conversation_id`
            WHERE m.`user_id` = :user_id
            ".($unread ? "AND c.`date_updated` > m.`date_last_read`" : "")."
            GROUP BY m.`conversation_id`
            ORDRE BY c.`date_updated` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindParam('user_id', $user_id);
        if ($sth->execute()) {
            $conversations = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $conversations[] = new Conversation((int)$row['conversation_id']);
            }
            return $conversations;
        }
        return null;
    }
}
