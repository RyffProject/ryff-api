<?php

class Riff {
    public $id;
    public $title;
    public $duration;
    public $link;
    
    protected function __construct($id, $title, $duration, $link) {
        $this->id = (int)$id;
        $this->title = $title;
        $this->duration = $duration;
        $this->link = $link;
    }
    
    public static function add($post_id, $title, $duration, $riff_tmp_path) {
        global $db;
        
        $riff_query = "
            INSERT INTO `riffs` (`post_id`, `title`, `duration`)
            VALUES (
                ".$db->real_escape_string((int)$post_id).",
                '".$db->real_escape_string($title)."',
                ".$db->real_escape_string((int)$duration)."
            )";
        $riff_results = $db->query($riff_query);
        if ($riff_results) {
            $riff_id = $db->insert_id;
            
            $riff_new_path = MEDIA_ABSOLUTE_PATH."/riffs/$riff_id.m4a";
            if (move_uploaded_file($riff_tmp_path, $riff_new_path)) {
                return Riff::get_by_post_id($post_id);
            } else {
                Riff::delete($riff_id);
            }
        }
        return null;
    }
    
    public static function delete($riff_id) {
        global $db;
        
        $query = "
            DELETE FROM `riffs`
            WHERE `riff_id` = ".$db->real_escape_string((int)$riff_id);
        if ($db->query($query)) {
            return true;
        }
        return false;
    }
    
    public static function get_by_post_id($post_id) {
        global $db;
        
        $riff_query = "SELECT * FROM `riffs` WHERE `post_id`=".$db->real_escape_string($post_id);
        $riff_results = $db->query($riff_query);
        if ($riff_results && $riff_results->num_rows && $riff_row = $riff_results->fetch_assoc()) {
            $riff_id = $riff_row['riff_id'];
            $path = MEDIA_ABSOLUTE_PATH."/riffs/$riff_id.m4a";
            if (file_exists($path)) {
                return new Riff($riff_row['riff_id'], $riff_row['title'], 
                                $riff_row['duration'], MEDIA_URL."/riffs/$riff_id.m4a");
            }
        }
        
        return null;
    }
}
