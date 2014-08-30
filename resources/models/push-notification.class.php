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
}