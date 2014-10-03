<?php

/**
 * @class Preferences
 * ==================
 * 
 * Provides static functions for regarding user preferences.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */
class Preferences {
    /**
     * Returns an array of $type => $value for all notification preferences,
     * or the preference of the given type.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @param mixed $type [optional] Defaults to all notification preferences.
     * @return boolean
     */
    public static function get_notification_preferences($user_id = null, $type = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT `type`, `value` FROM `notification_preferences`
            WHERE `user_id` = :user_id
            ".($type ? "AND `type` = :type" : "");
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        if ($type) {
            $sth->bindValue('type', $type);
        }
        if (!$sth->execute() || !$sth->rowCount()) {
            return false;
        }
        $preferences = array();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $preferences[$row['type']] = (bool)$row['value'];
        }
        return $preferences;
    }
    
    /**
     * Updates a user's notification preferences. $type is the type of
     * notification, such as follow, upvote, etc, and $value is true if the user
     * should receive that type of notification or false if they should stop
     * receiving them.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param string $type
     * @param boolean $value
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function update_notification_preference($type, $value, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            UPDATE `notification_preferences`
            SET `type` = :type, `value` = :value, `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('type', $type);
        $sth->bindValue('value', $value);
        $sth->bindValue('user_id', $user_id);
        if (!$sth->execute() || !$sth->rowCount()) {
            return false;
        }
        return true;
    }
    
    /**
     * Adds the default notification preferences for the given user.
     * 
     * @global NestedPDO $dbh
     * @param int $user_id
     * @return boolean
     */
    public static function add_default_notification_preferences($user_id) {
        global $dbh;
        $query = "
            INSERT INTO `notification_preferences` (`type`, `user_id`)
            VALUES
                ('follow', :user_id), ('upvote', :user_id), ('remix', :user_id),
                ('mention', :user_id), ('message', :user_id), ('post', :user_id)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        if (!$sth->execute() || !$sth->rowCount()) {
            print_r($sth->errorInfo());
            return false;
        }
        return true;
    }
}
