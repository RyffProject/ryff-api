<?php

/**
 * @class MediaFiles
 * =================
 * 
 * Provides static functions for deleting media files.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class MediaFiles {
    /**
     * Deletes the audio file from the given riff.
     * 
     * @param int $riff_id
     */
    public static function delete_riff_audio($riff_id) {
        $path = MEDIA_ABSOLUTE_PATH."/riffs/".((int)$riff_id).".m4a";
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    /**
     * Deletes the image file from the given post.
     * 
     * @param int $post_id
     */
    public static function delete_post_image($post_id) {
        $path = MEDIA_ABSOLUTE_PATH."/posts/".((int)$post_id).".png";
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    /**
     * Deletes the avatar from the given user.
     * 
     * @param int $user_id
     */
    public static function delete_user_image($user_id) {
        $path = MEDIA_ABSOLUTE_PATH."/avatars/".((int)$user_id).".png";
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    /**
     * Deletes all media files associated with the given post.
     * 
     * @global mysqli $db
     * @param int $post_id
     */
    public static function delete_from_post($post_id) {
        global $db;
        
        $riff_query = "
            SELECT `riff_id` FROM `riffs`
            WHERE `post_id` = ".$db->real_escape_string((int)$post_id);
        $riff_results = $db->query($riff_query);
        if ($riff_results && $riff_results->num_rows) {
            $row = $riff_results->fetch_assoc();
            MediaFiles::delete_riff_audio((int)$row['riff_id']);
        }
        
        MediaFiles::delete_post_image((int)$post_id);
    }
    
    /**
     * Deletes all media files associated with the given user.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     */
    public static function delete_from_user($user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $riff_ids_query = "
            SELECT a.`riff_id` FROM `riffs` AS a
            JOIN `posts` AS b ON b.`post_id` = a.`post_id`
            WHERE b.`user_id` = ".$db->real_escape_string((int)$user_id);
        $riff_ids_results = $db->query($riff_ids_query);
        if ($riff_ids_results) {
            while ($row = $riff_ids_results->fetch_assoc()) {
                MediaFiles::delete_riff_audio((int)$row['riff_id']);
            }
        }
        
        $post_ids_query = "
            SELECT `post_id` FROM `posts`
            WHERE `user_id` = ".$db->real_escape_string((int)$user_id);
        $post_ids_results = $db->query($post_ids_query);
        if ($post_ids_results) {
            while ($row = $post_ids_results->fetch_assoc()) {
                MediaFiles::delete_post_image((int)$row['post_id']);
            }
        }
        
        MediaFiles::delete_user_image($user_id);
    }
}
