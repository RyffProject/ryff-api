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
     * Deletes the avatar from the given user.
     * 
     * @param int $user_id
     */
    public static function delete_user_image($user_id) {
        if (TEST_MODE) {
            $path = TEST_MEDIA_ABSOLUTE_PATH."/avatars/".((int)$user_id).".png";
        } else {
            $path = MEDIA_ABSOLUTE_PATH."/avatars/".((int)$user_id).".png";
        }
        if (file_exists($path)) {
            unlink($path);
        }
    }
    
    /**
     * Deletes all media files associated with the given post.
     * 
     * @global PDO $dbh
     * @param int $post_id
     */
    public static function delete_from_post($post_id) {
        global $dbh;
        
        //Delete audio
        if (TEST_MODE) {
            $riff_path = TEST_MEDIA_ABSOLUTE_PATH."/riffs/".((int)$post_id).".m4a";
        } else {
            $riff_path = MEDIA_ABSOLUTE_PATH."/riffs/".((int)$post_id).".m4a";
        }
        if (file_exists($riff_path)) {
            unlink($riff_path);
        }
        
        //Delete image
        if (TEST_MODE) {
            $img_path = TEST_MEDIA_ABSOLUTE_PATH."/posts/".((int)$post_id).".png";
        } else {
            $img_path = MEDIA_ABSOLUTE_PATH."/posts/".((int)$post_id).".png";
        }
        if (file_exists($img_path)) {
            unlink($img_path);
        }
    }
    
    /**
     * Deletes all media files associated with the given user.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     */
    public static function delete_from_user($user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $post_ids_query = "
            SELECT `post_id` FROM `posts`
            WHERE `user_id` = :user_id";
        $post_ids_sth = $dbh->prepare($post_ids_query);
        $post_ids_sth->bindValue('user_id', $user_id);
        if ($post_ids_sth->execute()) {
            while ($row = $post_ids_sth->fetch(PDO::FETCH_ASSOC)) {
                MediaFiles::delete_from_post((int)$row['post_id']);
            }
        }
        
        MediaFiles::delete_user_image($user_id);
    }
}
