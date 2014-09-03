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
        if (TEST_MODE) {
            $path = TEST_MEDIA_ABSOLUTE_PATH."/riffs/".((int)$riff_id).".m4a";
        } else {
            $path = MEDIA_ABSOLUTE_PATH."/riffs/".((int)$riff_id).".m4a";
        }
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
        if (TEST_MODE) {
            $path = TEST_MEDIA_ABSOLUTE_PATH."/posts/".((int)$post_id).".png";
        } else {
            $path = MEDIA_ABSOLUTE_PATH."/posts/".((int)$post_id).".png";
        }
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
        
        $riff_query = "
            SELECT `riff_id` FROM `riffs`
            WHERE `post_id` = :post_id";
        $riff_sth = $dbh->prepare($riff_query);
        $riff_sth->bindValue('post_id', $post_id);
        if ($riff_sth->execute() && $riff_sth->rowCount()) {
            $row = $riff_sth->fetch(PDO::FETCH_ASSOC);
            MediaFiles::delete_riff_audio((int)$row['riff_id']);
        }
        
        MediaFiles::delete_post_image((int)$post_id);
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
        
        $riff_ids_query = "
            SELECT a.`riff_id` FROM `riffs` AS a
            JOIN `posts` AS b ON b.`post_id` = a.`post_id`
            WHERE b.`user_id` = :user_id";
        $riff_ids_sth = $dbh->prepare($riff_ids_query);
        $riff_ids_sth->bindValue('user_id', $user_id);
        if ($riff_ids_sth->execute()) {
            while ($row = $riff_ids_sth->fetch(PDO::FETCH_ASSOC)) {
                MediaFiles::delete_riff_audio((int)$row['riff_id']);
            }
        }
        
        $post_ids_query = "
            SELECT `post_id` FROM `posts`
            WHERE `user_id` = :user_id";
        $post_ids_sth = $dbh->prepare($post_ids_query);
        $post_ids_sth->bindValue('user_id', $user_id);
        if ($post_ids_sth->execute()) {
            while ($row = $post_ids_sth->fetch(PDO::FETCH_ASSOC)) {
                MediaFiles::delete_post_image((int)$row['post_id']);
            }
        }
        
        MediaFiles::delete_user_image($user_id);
    }
}
