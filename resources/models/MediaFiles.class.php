<?php

/**
 * @class MediaFiles
 * =================
 * 
 * Provides static functions for deleting media files.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
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
     * @param int $post_id
     */
    public static function delete_from_post($post_id) {
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
     * @global NestedPDO $dbh
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
    
    /**
     * Returns a GD image resource if the given file is a valid
     * GIF, JPEG, or PNG, or false on failure.
     * 
     * @param string $path
     * @return resource|false
     */
    protected static function get_image_resource($path) {
        $info = @getimagesize($path);
        if (!$info || !$info[0] || !$info[1]) {
            return false;
        }
        
        switch ($info[2]) {
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($path);
                break;
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($path);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($path);
                break;
            default:
                return false;
        }
        
        return $image;
    }
    
    /**
     * Saves the image from $source_path to the file given by $dest_path, as
     * either a JPEG or PNG depending on $image_type. If $width and $height are
     * set, the source image is scaled and cropped to the given size. If saving
     * a JPEG, $quality can be from 0 to 100.
     * 
     * @param string $source_path
     * @param string $dest_path
     * @param int $image_type Must be IMAGETYPE_JPEG or IMAGETYPE_PNG.
     * @param int $width [optional]
     * @param int $height [optional]
     * @param int $quality [optional] Defaults to 100.
     * @return boolean
     */
    public static function save_image($source_path, $dest_path, $image_type,
            $width = null, $height = null, $quality = 100) {
        if (!in_array($image_type, array(IMAGETYPE_JPEG, IMAGETYPE_PNG))) {
            return false;
        }
        
        $source_image = static::get_image_resource($source_path);
        if (!$source_image) {
            return false;
        }
        
        if ((int)$width <= 0 || (int)$height <= 0) {
            $dest_image = $source_image;
        } else {
            $dest_image = imagecreatetruecolor($width, $height);
            if ($image_type === IMAGETYPE_JPEG) {
                $white = imagecolorallocate($dest_image, 255, 255, 255);
                imagefill($dest_image, 0, 0, $white);
            } else if ($image_type === IMAGETYPE_PNG) {
                imagealphablending($dest_image, false);
                imagesavealpha($dest_image, true);
            }
            $original_width = imagesx($source_image);
            $original_height = imagesy($source_image);
            $scale_factor = max((double)$width / $original_width, (double)$height / $original_height);
            imagecopyresampled($dest_image, $source_image,
                ($width - ($original_width * $scale_factor)) / 2,
                ($height - ($original_height * $scale_factor)) / 2,
                0, 0,
                $scale_factor * $original_width, $scale_factor * $original_height,
                $original_width, $original_height
            );
        }
        
        if ($image_type === IMAGETYPE_JPEG) {
            return imagejpeg($dest_image, $dest_path, $quality);
        } else if ($image_type === IMAGETYPE_PNG) {
            return imagepng($dest_image, $dest_path);
        }
    }
    
    /**
     * Saves an avatar image as a full size 800x800 .png and a 100x100 .jpg
     * thumbnail.
     * 
     * @param string $avatar_source_path
     * @param int $user_id
     * @return boolean
     */
    public static function save_avatar($avatar_source_path, $user_id) {
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        $avatar_dest_path = "$media_dir/avatars/$user_id.png";
        $avatar_dest_small_path = "$media_dir/avatars/small/$user_id.jpg";
        
        if (!static::save_image($avatar_source_path, $avatar_dest_path,
                IMAGETYPE_PNG, 800, 800)) {
            return false;
        } else if (!static::save_image($avatar_source_path, $avatar_dest_small_path,
                IMAGETYPE_JPEG, 100, 100, 80)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Saves a post image as a full size 800x600 .png, a 400x400 .jpg medium
     * thumbnail, and a 100x100 .jpg small thumbnail.
     * 
     * @param string $post_image_source_path
     * @param int $post_id
     * @return boolean
     */
    public static function save_post_image($post_image_source_path, $post_id) {
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        $post_image_dest_path = "$media_dir/posts/$post_id.png";
        $post_image_medium_dest_path = "$media_dir/posts/medium/$post_id.jpg";
        $post_image_small_dest_path = "$media_dir/posts/small/$post_id.jpg";
        
        if (!static::save_image($post_image_source_path, $post_image_dest_path,
                IMAGETYPE_PNG, 800, 600)) {
            return false;
        } else if (!static::save_image($post_image_source_path, $post_image_medium_dest_path,
                IMAGETYPE_JPEG, 400, 400, 80)) {
            return false;
        } else if (!static::save_image($post_image_source_path, $post_image_small_dest_path,
                IMAGETYPE_JPEG, 100, 100, 80)) {
            return false;
        }
        
        return true;
    }
}
