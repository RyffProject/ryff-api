<?php

/**
 * @class PushNotification
 * =======================
 * 
 * Provides a class static functions related to push notifications.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class PushNotification {
    /**
     * Removes any old device tokens that had the same device UUID, then adds
     * the new token with that UUID.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param string $token
     * @param string $uuid
     * @param int $user_id
     * @return boolean
     */
    public static function add_apns_token($token, $uuid, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $del_query = "
            DELETE FROM `apns_tokens`
            WHERE `user_id` = :user_id
            AND `device_uuid` = :uuid";
        $del_sth = $dbh->prepare($del_query);
        $del_sth->bindValue('user_id', $user_id);
        $del_sth->bindValue('uuid', $uuid);
        if (!$del_sth->execute()) {
            return false;
        }
        
        $add_query = "
            INSERT INTO `apns_tokens` (`user_id`, `device_token`, `device_uuid`)
            VALUES (:user_id, :token, :uuid)";
        $add_sth = $dbh->prepare($add_query);
        $add_sth->bindValue('user_id', $user_id);
        $add_sth->bindValue('token', $token);
        $add_sth->bindValue('uuid', $uuid);
        return $add_sth->execute();
    }
    
    /**
     * Connects to the APNs and returns a socket for sending notifications.
     * 
     * @return resource
     */
    protected static function get_apns_socket() {
        $context = stream_context_create();
        stream_context_set_option($context, 'ssl', 'local_cert', APNS_CERTIFICATE);
        stream_context_set_option($context, 'ssl', 'passphrase', APNS_PASSPHRASE);
        
        $socket = stream_socket_client(APNS_GATEWAY, $err, $errstr, APNS_CONNECT_TIMEOUT,
                STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $context);
        return $socket;
    }
    
    /**
     * Returns an array of all the device tokens this user has for APNs.
     * 
     * @global PDO $dbh
     * @param int $user_id
     * @return array
     */
    public static function get_apns_tokens($user_id) {
        global $dbh;
        
        $query = "
            SELECT `device_token` FROM `apns_tokens`
            WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->execute();
        $tokens = array();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $tokens[] = $row['device_token'];
        }
        return $tokens;
    }
    
    protected static function get_message($type, $base_user_id, $base_post_id,
            $leaf_user_ids, $leaf_post_ids) {
        $message = "";
        if (count($leaf_user_ids)) {
            $message .= User::get_username($leaf_user_ids[0]);
            if (count($leaf_user_ids) > 1) {
                $message .= " and ".(count($leaf_user_ids) - 1)." others";
            }
        }
        switch ($type) {
            case 'follow':
                $message .= " followed you.";
                break;
            case 'upvote':
                $message .= " upvoted your post.";
                break;
            case 'mention':
                $message .= " mentioned you in a post.";
                break;
            case 'remix':
                $message .= " remixed your post.";
                break;
        }
        return $message;
    }
    
    /**
     * Constructs a notification message from the type and associated notification
     * objects, sends it to all of the devices that the user has registered,
     * and marks the notification objects as sent.
     * 
     * @global PDO $dbh
     * @param resource $apns_socket
     * @param int $notification_id
     * @param int $user_id
     * @param string $type
     * @param int $base_user_id
     * @param int $base_post_id
     * @param array $leaf_user_ids
     * @param array $leaf_post_ids
     * @param array $notification_object_ids
     * @return boolean
     */
    protected static function send_one(&$apns_socket, $notification_id, $user_id,
            $type, $base_user_id, $base_post_id, $leaf_user_ids, $leaf_post_ids,
            $notification_object_ids) {
        global $dbh;
        
        $message = static::get_message(
                $type, $base_user_id, $base_post_id,
                $leaf_user_ids, $leaf_post_ids);
        
        $payload = json_encode(array(
            'aps' => array('alert' => $message),
            'type' => $type,
            'id' => $notification_id
        ));
        
        $apns_tokens = static::get_apns_tokens($user_id);
        foreach ($apns_tokens as $token) {
            $msg = chr(0).pack('n', 32).pack('H*', $token).pack('n', strlen($payload)).$payload;
            if (!fwrite($apns_socket, $msg, strlen($msg))) {
                return false;
            }
        }
        $query = "
            UPDATE `notification_objects`
            SET `sent` = 1, `date_sent` = NOW()
            WHERE `notification_object_id` IN (
                ".implode(',', array_map(
                    function($i) { return ':id'.$i; },
                    range(0, count($notification_object_ids) - 1)))."
            )";
        $sth = $dbh->prepare($query);
        foreach ($notification_object_ids as $i => $id) {
            $sth->bindValue('id'.$i, $id);
        }
        return $sth->execute();
    }
    
    /**
     * This function sends push notifications to iOS devices. Entries in the
     * notification_objects table that have not yet been sent are sent in
     * order of creation.
     * 
     * @global PDO $dbh
     * @param int $limit [optional] How many notifications should be sent, 0 for no limit.
     * @return int The number of notifications sent.
     */
    public static function send_all($limit = 0) {
        global $dbh;
        
        $apns_socket = static::get_apns_socket();
        if (!$apns_socket) {
            return 0;
        }
        
        $start_time = date('Y-m-d H:i:s');
        $num_sent = 0;
        $query = "
            SELECT n.`notification_id`, n.`user_id`, n.`type`,
                n.`user_obj_id` AS `base_user_id`, n.`post_obj_id` AS `base_post_id`,
                obj1.`user_obj_id` AS `leaf_user_id`, obj1.`post_obj_id` AS `leaf_post_id`,
                obj1.`notification_object_id`
            FROM `notifications` AS n
            JOIN `notification_objects` AS obj1
            ON obj1.`notification_id` = n.`notification_id`
            WHERE obj1.`notification_id` = (
                SELECT obj2.`notification_id`
                FROM `notification_objects` AS obj2
                WHERE obj2.`sent` = 0
                AND obj2.`date_created` < :start_time
                ORDER BY obj2.`date_created` ASC
                LIMIT 1
            )
            AND n.`read` = 0
            AND obj1.`sent` = 0
            AND obj1.`date_created` < :start_time";
        $sth = $dbh->prepare($query);
        $sth->bindValue('start_time', $start_time);
        while ($sth->execute() && $sth->rowCount() && (!$limit || $num_sent < $limit)) {
            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
            
            $notification_id = $rows[0]['notification_id'];
            $user_id = $rows[0]['user_id'];
            $type = $rows[0]['type'];
            $base_user_id = $rows[0]['base_user_id'];
            $base_post_id = $rows[0]['base_post_id'];
            $leaf_user_ids = array_map(function($row) {
                return $row['leaf_user_id'];
            }, $rows);
            $leaf_post_ids = array_map(function($row) {
                return $row['leaf_post_id'];
            }, $rows);
            $notification_object_ids = array_map(function($row) {
                return $row['notification_object_id'];
            }, $rows);
            
            if (static::send_one($apns_socket, $notification_id,
                    $user_id, $type, $base_user_id, $base_post_id,
                    $leaf_user_ids, $leaf_post_ids, $notification_object_ids)) {
                $num_sent++;
            } else {
                break;
            }
        }
        
        fclose($apns_socket);
        return $num_sent;
    }
    
    /**
     * Sends a push notification about a message. Sends a payload with the
     * conversation_id, a type of message, and the text of the message.
     * The array of recipient ids should be whoever has not yet read the message.
     * After notifications are sent, the message is marked as sent.
     * 
     * @global PDO $dbh
     * @param resource $apns_socket
     * @param int $message_id
     * @param int $conversation_id
     * @param int $sender_id
     * @param array $recipient_ids
     * @param string $content
     * @return boolean
     */
    protected static function send_one_message(&$apns_socket, $message_id,
            $conversation_id, $sender_id, $recipient_ids, $content) {
        global $dbh;
        
        $sender_username = User::get_username($sender_id);
        $fitted_content = strlen($content) > 50 ? substr($content, 0, 50).'...' : $content;
        $payload = json_encode(array(
            'aps' => array('alert' => $sender_username.': '.$fitted_content),
            'type' => 'message',
            'id' => $conversation_id
        ));
        
        foreach ($recipient_ids as $user_id) {
            $apns_tokens = static::get_apns_tokens($user_id);
            foreach ($apns_tokens as $token) {
                $msg = chr(0).pack('n', 32).pack('H*', $token).pack('n', strlen($payload)).$payload;
                if (!fwrite($apns_socket, $msg, strlen($msg))) {
                    return false;
                }
            }
        }
        
        $query = "
            UPDATE `messages`
            SET `sent` = 1, `date_sent` = NOW()
            WHERE `message_id` = :message_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('message_id', $message_id);
        return $sth->execute();
    }
    
    /**
     * This function sends push notifications to iOS devices for the messaging
     * system. Messages are sent in order of creation.
     * 
     * @global PDO $dbh
     * @param int $limit [optional] How many messages should be sent, 0 for no limit.
     * @return int The number of messages sent.
     */
    public static function send_all_messages($limit = 0) {
        global $dbh;
        
        $apns_socket = static::get_apns_socket();
        if (!$apns_socket) {
            return 0;
        }
        
        $start_time = date('Y-m-d H:i:s');
        $num_sent = 0;
        $query = "
            SELECT m.*
            FROM `messages` AS m
            WHERE m.`sent` = 0
            AND m.`date_created` < :start_time
            ORDER BY m.`date_created` ASC
            LIMIT 1";
        $sth = $dbh->prepare($query);
        $sth->bindValue('start_time', $start_time);
        while ($sth->execute() && $sth->rowCount() && (!$limit || $num_sent < $limit)) {
            $message_row = $sth->fetch(PDO::FETCH_ASSOC);
            
            $to_query = "
                SELECT cm.`user_id`
                FROM `conversation_members` AS cm
                WHERE cm.`conversation_id` = :conversation_id
                AND cm.`date_last_read` < :date_created";
            $to_sth = $dbh->prepare($to_query);
            $to_sth->bindValue('conversation_id', $message_row['conversation_id']);
            $to_sth->bindValue('date_created', $message_row['date_created']);
            $to_sth->execute();
            $to_rows = $to_sth->fetchAll(PDO::FETCH_ASSOC);
            $to_user_ids = array_map(function($row) {
                return $row['user_id'];
            }, $to_rows);
            
            if (static::send_one_message($apns_socket, $message_row['message_id'],
                    $message_row['conversation_id'], $message_row['user_id'],
                    $to_user_ids, $message_row['content'])) {
                $num_sent++;
            } else {
                break;
            }
        }
        
        fclose($apns_socket);
        return $num_sent;
    }
}
