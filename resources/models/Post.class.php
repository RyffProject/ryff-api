<?php

/**
 * @class Post
 * ===========
 * 
 * Provides a class for Post objects and static functions related to posts.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */
class Post {
    /**
     * The post_id.
     * 
     * @var int
     */
    public $id;
    
    /**
     * The User object that posted this post.
     * 
     * @var User
     */
    public $user;
    
    /**
     * The title of the post.
     * 
     * @var string
     */
    public $title;
    
    /**
     * The text of the post.
     * 
     * @var string
     */
    public $content;
    
    /**
     * The duration of the post audio in seconds.
     * 
     * @var int
     */
    public $duration;
    
    /**
     * The date this post was created.
     * 
     * @var string
     */
    public $date_created;
    
    /**
     * The number of upvotes this post has.
     * 
     * @var int
     */
    public $upvotes;
    
    /**
     * If this post is upvoted by the current user.
     * 
     * @var boolean
     */
    public $is_upvoted;
    
    /**
     * If this post is starred by the current user.
     * 
     * @var boolean
     */
    public $is_starred;
    
    /**
     * The URL for this post's associated image, or "" if no image is found.
     * 
     * @var string
     */
    public $image_url;
    
    /**
     * The URL for this post's associated medium thumbnail image, or "" if no
     * image is found.
     * 
     * @var string
     */
    public $image_medium_url;
    
    /**
     * The URL for this post's associated small thumbnail image, or "" if no
     * image is found.
     * 
     * @var string
     */
    public $image_small_url;
    
    /**
     * The URL for this post's associated listening quality audio file.
     * 
     * @var string
     */
    public $riff_url;
    
    /**
     * The URL for this post's associated high quality audio file.
     * 
     * @var string
     */
    public $riff_hq_url;
    
    /**
     * Constructs a new Post instance with the given member variable values.
     * 
     * @param int $id
     * @param User $user
     * @param string $title
     * @param string $content
     * @param int $duration
     * @param string $date_created
     */
    protected function __construct($id, $user, $title, $content, $duration, $date_created) {
        $this->id = (int)$id;
        $this->user = $user;
        $this->title = $title;
        $this->content = $content;
        $this->duration = (int)$duration;
        $this->date_created = $date_created;
        
        $this->upvotes = $this->get_num_upvotes();
        $this->is_upvoted = $this->get_is_upvoted();
        $this->is_starred = $this->get_is_starred();
        $this->image_url = $this->get_image_url();
        $this->image_medium_url = $this->get_image_medium_url();
        $this->image_small_url = $this->get_image_small_url();

        $riff_ext_dir = TEST_MODE ? TEST_MEDIA_EXT_PATH : MEDIA_EXT_PATH;
        $this->riff_url = SITE_ROOT.$riff_ext_dir."/riffs/{$this->id}.mp3";;
        $this->riff_hq_url = SITE_ROOT.$riff_ext_dir."/riffs/hq/{$this->id}.mp3";;
    }
    
    /**
     * Helper function that returns the number of upvotes this post has.
     * 
     * @global NestedPDO $dbh
     * @return int The number of upvotes.
     */
    protected function get_num_upvotes() {
        global $dbh;
        
        $query = "
            SELECT COUNT(*) AS `num_upvotes` FROM `upvotes`
            WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $this->id);
        $sth->execute();
        return (int)$sth->fetchColumn();
    }
    
    /**
     * Helper function that returns whether this post is upvoted by the
     * current user.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @return boolean If this post is upvoted by the current user.
     */
    protected function get_is_upvoted() {
        global $dbh, $CURRENT_USER;
        
        if (!$CURRENT_USER) {
            return false;
        }
        
        $query = "
            SELECT 1 FROM `upvotes`
            WHERE `post_id` = :post_id
            AND `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $this->id);
        $sth->bindValue('user_id', $CURRENT_USER->id);
        $sth->execute();
        return (bool)$sth->fetchColumn();
    }
    
    /**
     * Helper function that returns whether this post is starred by the
     * current user.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @return boolean If this post is starred by the current user.
     */
    protected function get_is_starred() {
        global $dbh, $CURRENT_USER;
        
        if (!$CURRENT_USER) {
            return false;
        }
        
        $query = "
            SELECT 1 FROM `stars`
            WHERE `post_id` = :post_id
            AND `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $this->id);
        $sth->bindValue('user_id', $CURRENT_USER->id);
        $sth->execute();
        return (bool)$sth->fetchColumn();
    }
    
    /**
     * Helper function that returns the URL of this post's associated image.
     * 
     * @return string The image URL or "" if not found.
     */
    protected function get_image_url() {
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        $media_ext_dir = TEST_MODE ? TEST_MEDIA_EXT_PATH : MEDIA_EXT_PATH;
        if (file_exists("$media_dir/posts/{$this->id}.png")) {
            return SITE_ROOT.$media_ext_dir."/posts/{$this->id}.png";
        }
        return "";
    }
    
    /**
     * Helper function that returns the URL of this post's associated medium
     * thumbnail image.
     * 
     * @return string The image URL or "" if not found.
     */
    protected function get_image_medium_url() {
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        $media_ext_dir = TEST_MODE ? TEST_MEDIA_EXT_PATH : MEDIA_EXT_PATH;
        if (file_exists("$media_dir/posts/medium/{$this->id}.jpg")) {
            return SITE_ROOT.$media_ext_dir."/posts/medium/{$this->id}.jpg";
        }
        return "";
    }
    
    /**
     * Helper function that returns the URL of this post's associated small
     * thumbnail image.
     * 
     * @return string The image URL or "" if not found.
     */
    protected function get_image_small_url() {
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        $media_ext_dir = TEST_MODE ? TEST_MEDIA_EXT_PATH : MEDIA_EXT_PATH;
        if (file_exists("$media_dir/posts/small/{$this->id}.jpg")) {
            return SITE_ROOT.$media_ext_dir."/posts/small/{$this->id}.jpg";
        }
        return "";
    }
    
    /**
     * Gets the post objects that are parents of this post.
     * 
     * @global NestedPDO $dbh
     * @return array An array of Post objects
     */
    public function get_parents() {
        global $dbh;
        
        $parents = array();
        $query = "
            SELECT `parent_id` FROM `post_families`
            WHERE `child_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $this->id);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $parent = Post::get_by_id((int)$row['parent_id']);
            if ($parent) {
                $parents[] = $parent;
            }
        }
        return $parents;
    }
    
    /**
     * Gets the post objects that are children of this post.
     * 
     * @global NestedPDO $dbh
     * @return array An array of Post objects
     */
    public function get_children() {
        global $dbh;
        
        $children = array();
        $query = "
            SELECT `child_id` FROM `post_families`
            WHERE `parent_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $this->id);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $child = Post::get_by_id((int)$row['child_id']);
            if ($child) {
                $children[] = $child;
            }
        }
        return $children;
    }
    
    /**
     * Adds a new Post.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param string $title The title of the post.
     * @param type $riff_tmp_path The path to the post audio.
     * @param string $content [optional] The text content of the post, "" for none.
     * @param array $parent_ids [optional] The array of parent ids, array() for none.
     * @param type $img_tmp_path [optional] The path to the post image, "" for none.
     * @param type $user_id [optional] Defaults to the current user.
     * @return int|null The added Post object's id or null on failure.
     * @throws AudioQuotaException
     */
    public static function add($title, $riff_tmp_path, $content = "",
            $parent_ids = array(), $img_tmp_path = "", $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        //Make sure if file paths are set that they exist
        if (!file_exists($riff_tmp_path)) {
            return null;
        } else if ($img_tmp_path && !file_exists($img_tmp_path)) {
            return null;
        }
        
        //Check audio quota
        if (!TEST_MODE && (AUDIO_QUOTA_LENGTH || AUDIO_QUOTA_SIZE)) {
            $audio_usage = User::get_audio_usage($user_id, AUDIO_QUOTA_TIMEFRAME);
            $audio_info = MediaFiles::get_audio_info($riff_tmp_path);
            $audio_length = $audio_info ? ceil((double)$audio_info['duration']) : 0;
            $audio_size = filesize($riff_tmp_path);
            if ((AUDIO_QUOTA_LENGTH && $audio_usage['length'] + $audio_length > AUDIO_QUOTA_LENGTH) ||
                    (AUDIO_QUOTA_SIZE && $audio_usage['size'] + $audio_size > AUDIO_QUOTA_SIZE)) {
                throw new AudioQuotaException;
            }
        }
        
        $dbh->beginTransaction();
        
        $query = "
            INSERT INTO `posts` (`user_id`, `title`, `content`)
            VALUES (:user_id, :title, :content)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->bindValue('title', $title);
        $sth->bindValue('content', $content);
        if (!$sth->execute()) {
            $dbh->rollBack();
            return null;
        }
        $post_id = $dbh->lastInsertId();
        
        //Save post image
        if ($img_tmp_path) {
            if (!MediaFiles::save_post_image($img_tmp_path, $post_id)) {
                $dbh->rollBack();
                return null;
            }
        }

        //Save post audio
        if (!MediaFiles::save_riff($riff_tmp_path, $post_id)) {
            $dbh->rollBack();
            return null;
        }
        
        //Add post parents and notify them that they have been remixed
        if ($parent_ids) {
            if (!Post::add_parents($post_id, $parent_ids)) {
                $dbh->rollBack();
                return null;
            }
            foreach ($parent_ids as $parent_id) {
                $parent_post = Post::get_by_id($parent_id);
                if (!$parent_post) {
                    $dbh->rollBack();
                    return null;
                }
                if ($parent_post->user->id !== (int)$user_id) {
                    if (!Notification::add($parent_post->user->id, "remix",
                            $parent_post->id, null, $post_id, $user_id)) {
                        $dbh->rollBack();
                        return null;
                    }
                }
            }
        }
        
        //Add post tags, add notifications for people mentioned, and add upvote
        //by the user adding the post.
        $added_tags = Tag::add_for_post($post_id, $content);
        $added_notification = Notification::add_mentions($post_id, $content);
        $added_upvote = Upvote::add($post_id, $user_id);
        if (!$added_tags || !$added_notification || !$added_upvote) {
            $dbh->rollBack();
            return null;
        }
        
        $dbh->commit();
        return $post_id;
    }
    
    /**
     * Adds the $parent_ids as parents of $post_id. The $parent_ids can be
     * either a comma-separated string or an array of ids.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @param string|array $parent_ids
     * @return boolean
     */
    public static function add_parents($post_id, $parent_ids) {
        global $dbh;
        
        if (!is_array($parent_ids)) {
            $parent_ids = explode(',', $parent_ids);
        }
        $parent_ids = array_unique(array_filter(array_map('intval', $parent_ids)));
        if (empty($parent_ids)) {
            return true;
        }
        
        $query = "
            INSERT IGNORE INTO `post_families` (`parent_id`, `child_id`)
            VALUES ".implode(',', array_map(
                function($i) { return "(:parent_id$i, :post_id)"; },
                range(0, count($parent_ids) - 1)
            ));
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $parent_id_index = 0;
        foreach ($parent_ids as $parent_id) {
            $sth->bindValue('parent_id'.$parent_id_index, $parent_id);
            $parent_id_index++;
        }
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Deletes the given post.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @return boolean
     */
    public static function delete($post_id) {
        global $dbh;
        
        $query = "
            DELETE FROM `posts`
            WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        if ($sth->execute()) {
            MediaFiles::delete_from_post((int)$post_id);
            return true;
        }
        return false;
    }
    
    /**
     * Gets the post object with the given $post_id, if it exists.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @return Post|null
     */
    public static function get_by_id($post_id) {
        global $dbh;
        
        $query = "
            SELECT `user_id`, `title`, `duration`, `content`, `date_created`
            FROM `posts` WHERE `post_id` = :post_id AND `active` = 1";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        if ($sth->execute() && $sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            $user = User::get_by_id((int)$row['user_id']);
            if (!$user) {
                return null;
            }
            $post = new Post($post_id, $user, $row['title'], $row['content'],
                    $row['duration'], $row['date_created']);
            return $post;
        }
        
        return null;
    }
    
    /**
     * Returns the title of the post with the given id, or null if not found.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @return string|null
     */
    public static function get_title($post_id) {
        global $dbh;
        $query = "SELECT `title` FROM `posts` WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        if (!$sth->execute() || !$sth->rowCount()) {
            return null;
        }
        return $sth->fetchColumn();
    }
    
    /**
     * Returns true if the post with the given id exists, or false otherwise.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @return boolean
     */
    public static function exists($post_id) {
        global $dbh;
        
        $query = "
            SELECT 1 FROM `posts` WHERE `post_id` = :post_id AND `active` = 1";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $sth->execute();
        return (bool)$sth->fetchColumn();
    }
    
    /**
     * Checks whether the current post is active and thus ready for listening.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @return boolean
     */
    public static function is_active($post_id) {
        global $dbh;
        $query = "SELECT `active` FROM `posts` WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $sth->execute();
        return (bool)$sth->fetchColumn();
    }
    
    /**
     * Checks whether the current post is converted to high-quality audio if
     * $hq is true, or regular quality if it is false.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @param boolean $hq
     * @return boolean
     */
    public static function is_converted($post_id, $hq) {
        global $dbh;
        $query = "
            SELECT `".($hq ? "hq_" : "")."converted`
            FROM `posts` WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $sth->execute();
        return (bool)$sth->fetchColumn();
    }
    
    /**
     * Sets the duration of a given post, in seconds. Returns true on success
     * or false on failure.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @param int $duration
     * @return boolean
     */
    public static function set_duration($post_id, $duration) {
        global $dbh;
        $query = "UPDATE `posts` SET `duration` = :duration WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $sth->bindValue('duration', $duration);
        if (!$sth->execute()) {
            return false;
        }
        return true;
    }
    
    /**
     * Sets the file size of a given post, in bytes. Returns true on success
     * or false on failure.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @param int $filesize
     * @return boolean
     */
    public static function set_filesize($post_id, $filesize) {
        global $dbh;
        $query = "UPDATE `posts` SET `filesize` = :filesize WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $sth->bindValue('filesize', $filesize);
        if (!$sth->execute()) {
            return false;
        }
        return true;
    }
    
    /**
     * Sets the given post as active, so it is available for streaming and can
     * be found in searches or people's profiles, etc.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @param bool $active
     * @return boolean
     */
    public static function set_active($post_id, $active) {
        global $dbh;
        $query = "UPDATE `posts` SET `active` = :active WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $sth->bindValue('active', $active ? 1 : 0);
        if (!$sth->execute()) {
            return false;
        }
        return true;
    }
    
    /**
     * Sets that the given post has finished its audio processing. $hq is true
     * if the high-quality file is done, or false otherwise. $converted
     * is true for successful conversion or false on error.
     * 
     * @global NestedPDO $dbh
     * @param int $post_id
     * @param boolean $hq
     * @param boolean $converted
     * @return boolean
     */
    public static function set_converted($post_id, $hq, $converted) {
        global $dbh;
        $query = "
            UPDATE `posts` SET `".($hq ? "hq_" : "")."converted` = :converted
            WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('post_id', $post_id);
        $sth->bindValue('converted', $converted ? 1 : 0);
        if (!$sth->execute()) {
            return false;
        }
        return true;
    }
}
