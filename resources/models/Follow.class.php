<?php

/**
 * @class Follow
 * =============
 * 
 * Provides static functions related to following users.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */
class Follow {
    /**
     * Adds a follow from $to_id to $from_id.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param int $to_id
     * @param int $from_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function add($to_id, $from_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($from_id === null && $CURRENT_USER) {
            $from_id = $CURRENT_USER->id;
        }
        
        $dbh->beginTransaction();
        
        $query = "
            INSERT IGNORE INTO `follows` (`to_id`, `from_id`)
            VALUES (:to_id, :from_id)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('to_id', $to_id);
        $sth->bindValue('from_id', $from_id);
        if (!$sth->execute()) {
            $dbh->rollBack();
            return false;
        }
        
        if (!Notification::add($to_id, "follow", null, null, null, $from_id)) {
            $dbh->rollBack();
            return false;
        }
        
        $dbh->commit();
        return true;
    }
    
    /**
     * Deletes the follow from $to_id to $from_id.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param int $to_id
     * @param int $from_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function delete($to_id, $from_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($from_id === null && $CURRENT_USER) {
            $from_id = $CURRENT_USER->id;
        }
        
        $dbh->beginTransaction();
        
        $query = "
            DELETE FROM `follows`
            WHERE `to_id` = :to_id
            AND `from_id` = :from_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('to_id', $to_id);
        $sth->bindValue('from_id', $from_id);
        if (!$sth->execute()) {
            $dbh->rollBack();
            return false;
        }
        
        if (!Notification::delete($to_id, "follow", null, null, null, $from_id)) {
            $dbh->rollBack();
            return false;
        }
        
        $dbh->commit();
        return true;
    }
    
    /**
     * Gets an array of User objects that follow the given user.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param int $page The current page number.
     * @param int $limit The number of results per page.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array An array of User objects or null on error.
     */
    public static function get_followers($page, $limit, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT u.* FROM `users` AS u
            JOIN `follows` AS f ON f.`from_id` = u.`user_id`
            AND f.`to_id` = :to_id
            ORDER BY f.`date_created` ASC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindValue('to_id', $user_id);
        if ($sth->execute()) {
            $users = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $users[] = User::create($row);
            }
            return $users;
        }
        return null;
    }
    
    /**
     * Gets an array of User objects that the given user follows.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param int $page The current page number.
     * @param int $limit The number of results per page.
     * @param int $user_id [optional] Defaults to the current user.
     * @return array|null An array of User objects or null on error.
     */
    public static function get_following($page, $limit, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT u.* FROM `users` AS u
            JOIN `follows` AS f ON f.`to_id` = u.`user_id`
            AND f.`from_id` = :from_id
            ORDER BY f.`date_created` ASC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $sth = $dbh->prepare($query);
        $sth->bindValue('from_id', $user_id);
        if ($sth->execute()) {
            $users = array();
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $users[] = User::create($row);
            }
            return $users;
        }
        return null;
    }
}
