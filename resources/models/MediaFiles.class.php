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
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        $avatar_path = "$media_dir/avatars/$user_id.png";
        $avatar_small_path = "$media_dir/avatars/small/$user_id.jpg";
        if (file_exists($avatar_path)) {
            unlink($avatar_path);
        }
        if (file_exists($avatar_small_path)) {
            unlink($avatar_small_path);
        }
    }
    
    /**
     * Deletes all media files associated with the given post.
     * 
     * @param int $post_id
     * @return boolean
     */
    public static function delete_from_post($post_id) {
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        $paths = array(
            "$media_dir/posts/$post_id.png",
            "$media_dir/posts/medium/$post_id.jpg",
            "$media_dir/posts/small/$post_id.jpg",
            "$media_dir/riffs/$post_id.mp3",
            "$media_dir/riffs/hq/$post_id.mp3",
            "$media_dir/riffs/raw/$post_id.mp3"
        );
        foreach ($paths as $path) {
            if (file_exists($path) && !unlink($path)) {
                return false;
            }
        }
        return true;
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
    
    /**
     * Uses ffprobe or avconv (set in AUDIO_INFO_COMMAND) to get audio codec
     * information as JSON, which it then decodes. Returns information on
     * codec name, number of channels, duration, bitrate, etc. If the command
     * fails or the file is not a recognized audio file with a single stream,
     * this function returns null.
     * 
     * @param string $audio_path
     * @return array|null
     */
    public static function get_audio_info($audio_path) {
        if (!(fileperms($audio_path) & 0x0004)) {
            if (!chmod($audio_path, 0644)) {
                return null;
            }
        }
        
        $command = sprintf(AUDIO_INFO_COMMAND, escapeshellarg($audio_path));
        exec($command, $output_array, $return_var);
        if ($return_var) {
            return null;
        }
        $output = json_decode(implode("", $output_array), true);
        if (!$output || !isset($output["streams"]) ||
                !is_array($output["streams"]) || count($output["streams"]) !== 1 ||
                !isset($output["streams"][0]["codec_type"]) ||
                $output["streams"][0]["codec_type"] !== "audio") {
            return null;
        }
        
        $interesting_data = array(
            "codec_name", "codec_long_name", "channels",
            "channel_layout", "duration", "bit_rate"
        );
        $info = array();
        foreach ($interesting_data as $key) {
            if (isset($output["streams"][0][$key])) {
                $info[$key] = $output["streams"][0][$key];
            } else {
                $info[$key] = "";
            }
        }
        return $info;
    }
    
    /**
     * Attempts to convert audio from $source_path and save it as an MP3
     * file for the given $post_id, using ffmpeg. Saves as either 256k if
     * $is_hq is true or $128k otherwise. Returns true if the conversion
     * succeeded or false on failure.
     * 
     * @param string $source_path
     * @param int $post_id
     * @param boolean $hq
     * @return boolean
     */
    public static function save_audio($source_path, $post_id, $hq) {
        if (!file_exists($source_path)) {
            return false;
        }
        
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        if ($hq) {
            $riff_dest_path = "$media_dir/riffs/hq/$post_id.mp3";
        } else {
            $riff_dest_path = "$media_dir/riffs/$post_id.mp3";
        }
        $bitrate = $hq ? "256k" : "128k";
        
        $command = FFMPEG_COMMAND." -y -i ".escapeshellarg($source_path).
                " -c:a ".FFMPEG_CODEC." -b:a $bitrate ".escapeshellarg($riff_dest_path).
                " > /dev/null 2>&1";
        exec($command, $output, $return_var);
        if ($return_var) {
            if (file_exists($riff_dest_path)) {
                unlink($riff_dest_path);
            }
            return false;
        }
        
        return true;
    }
    
    /**
     * Gets the audio info from $source_path, then if it is in mp3 format and
     * in the right bitrate it will be copied to media/riffs/hq, otherwise
     * it will be copied to media/riffs/raw. Also sets the post as active if
     * the audio is available for listening.
     * 
     * @global NestedPDO $dbh
     * @param string $source_path
     * @param int $post_id
     * @return boolean
     */
    public static function save_riff($source_path, $post_id) {
        global $dbh;
        
        $dbh->beginTransaction();
        
        $audio_info = static::get_audio_info($source_path);
        if (!$audio_info) {
            $dbh->rollBack();
            return false;
        } else if (!Post::set_duration($post_id, ceil((double)$audio_info['duration']))) {
            $dbh->rollBack();
            return false;
        } else if (!Post::set_filesize($post_id, filesize($source_path))) {
            $dbh->rollBack();
            return false;
        }
        
        $copy_func = is_uploaded_file($source_path) ? "move_uploaded_file" : "copy";
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        $dest_hq_path = "$media_dir/riffs/hq/$post_id.mp3";
        $dest_raw_path = "$media_dir/riffs/raw/$post_id.mp3";
        if ($audio_info['codec_name'] === "mp3") {
            if ((int)$audio_info['bit_rate'] >= 192000 && (int)$audio_info['bit_rate'] <= 320000) {
                if (!$copy_func($source_path, $dest_hq_path) || !Post::set_active($post_id, true) ||
                        !Post::set_converted($post_id, true, true)) {
                    $dbh->rollBack();
                    return false;
                }
            } else if (!$copy_func($source_path, $dest_raw_path) || !Post::set_active($post_id, true)) {
                $dbh->rollBack();
                return false;
            }
        } else if (!$copy_func($source_path, $dest_raw_path)) {
            $dbh->rollBack();
            return false;
        }
        
        $dbh->commit();
        return true;
    }
}
