<?php

class Follow {
    public static function add($to_id, $from_id = null) {
        global $db, $CURRENT_USER;
        
        if ($from_id === null && $CURRENT_USER) {
            $from_id = $CURRENT_USER->id;
        }
        
        $follow_query = "
            INSERT INTO `follows` (`to_id`, `from_id`)
            VALUES (
                ".$db->real_escape_string((int)$to_id).",
                ".$db->real_escape_string((int)$from_id)."
            )";
        $follow_results = $db->query($follow_query);
        
        if ($follow_results) {
            Notification::add($to_id, "follow", null, null, null, $from_id);
            return true;
        }
        return false;
    }
    
    public static function delete($to_id, $from_id = null) {
        global $db, $CURRENT_USER;
        
        if ($from_id === null && $CURRENT_USER) {
            $from_id = $CURRENT_USER->id;
        }
        
        $follow_query = "
            DELETE FROM `follows`
            WHERE `to_id`=".$db->real_escape_string((int)$to_id)."
            AND `from_id`=".$db->real_escape_string((int)$from_id);
        $follow_results = $db->query($follow_query);
        
        if ($follow_results) {
            Notification::delete($to_id, "follow", null, null, null, $from_id);
            return true;
        }
        return false;
    }
    
    public static function get_users_following($page, $limit, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT u.*
            FROM `users` AS u
            JOIN `follows` AS f
            ON f.`to_id`=u.`user_id`
            AND f.`from_id`=".$db->real_escape_string($user_id)."
            ORDER BY f.`date_created` ASC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        
        if ($results) {
            $users = array();
            while ($row = $results->fetch_assoc()) {
                $users[] = User::create($row);
            }
            return $users;
        }
        return null;
    }
}
