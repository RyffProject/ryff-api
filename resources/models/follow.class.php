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
            return true;
        }
        return false;
    }
}
