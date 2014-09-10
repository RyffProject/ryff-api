<?php

/**
 * @class Message
 * ==============
 * 
 * Provides a class for Message objects and static functions for sending
 * and receiving messages.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
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
     * @param string $date_created
     */
    protected function __construct($id, $user_id, $content, $date_created) {
        $this->id = (int)$id;
        $this->user_id = (int)$user_id;
        $this->content = $content;
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
            'message_id' => 0, 'user_id' => 0,
            'content' => 0, 'date_created' => 0
        );
        if (empty(array_diff_key($required_keys, $row))) {
            return new Message(
                $row['message_id'], $row['user_id'],
                $row['content'], $row['date_created']
            );
        }
        return null;
    }
    
    /**
     * Returns the message object with the given id, or null if it does not exist.
     * 
     * @global PDO $dbh
     * @param int $message_id
     * @return Message|null
     */
    public static function get_by_id($message_id) {
        global $dbh;

        $query = "
            SELECT `message_id`, `user_id`, `content`, `date_created`
            FROM `messages`
            WHERE `message_id` = :message_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('message_id', $message_id);
        if ($sth->execute() && $sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return Message::create($row);
        }
        
        return null;
    }
    
    /**
     * Sends a message from one user to a conversation.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param string $content
     * @param int $conversation_id
     * @param int $user_id [optional] Defaults to the current user.
     * @return Message|null The new Message object, or null on failure.
     */
    public static function send($content, $conversation_id, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            INSERT INTO `messages` (`conversation_id`, `user_id`, `content`)
            VALUES (:conversation_id, :user_id, :content)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('conversation_id', $conversation_id);
        $sth->bindValue('user_id', $user_id);
        $sth->bindValue('content', $content);
        if ($sth->execute()) {
            $message_id = $dbh->lastInsertId();
            
            $update_conversation_query = "
                UPDATE `conversations`
                SET `date_updated` = NOW()
                WHERE `conversation_id` = :conversation_id";
            $update_conversation_sth = $dbh->prepare($update_conversation_query);
            $update_conversation_sth->bindValue('conversation_id', $conversation_id);
            $update_conversation_sth->execute();
            
            Conversation::set_read($conversation_id, $user_id);
            
            return Message::get_by_id($message_id);
        }
        return null;
    }
    
    /**
     * Gets messages in a conversation and marks the conversation as
     * read for the user who is requesting them. If $unread is true, only gets
     * unread messages.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $conversation_id
     * @param int $page The page number of results.
     * @param int $limit The number of results per page.
     * @param boolean $unread [optional] Defaults to false.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of Message objects or null on failure.
     */
    public static function get_for_conversation($conversation_id, $page, $limit,
            $unread = false, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT m.`message_id`, m.`user_id`, m.`content`, m.`date_created`
            FROM `messages` AS m
            ".($unread ? "JOIN `conversation_members` AS cm
                ON cm.`conversation_id` = m.`conversation_id`" : "")."
            WHERE m.`conversation_id` = :conversation_id
            ".($unread ? "AND m.`date_created` > cm.`date_last_read`
                AND cm.`user_id` = :user_id" : "")."
            GROUP BY m.`message_id`
            ORDER BY m.`date_created` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindValue('conversation_id', $conversation_id);
        if ($unread) {
            $sth->bindValue('user_id', $user_id);
        }
        if ($sth->execute()) {
            $messages = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $messages[] = Message::create($row);
            }
            
            Conversation::set_read($conversation_id, $user_id);
            
            return $messages;
        }
        return null;
    }
}
