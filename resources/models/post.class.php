<?php

/**
 * @class Post
 * ===========
 * 
 * Provides a class for Post objects and static functions related to posts.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
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
     * The Riff object associated with this post, or null if not found.
     * 
     * @var Riff|null
     */
    public $riff;
    
    /**
     * The text of the post.
     * 
     * @var string
     */
    public $content;
    
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
     * Constructs a new Post instance with the given member variable values.
     * 
     * @param int $id
     * @param User $user
     * @param Riff $riff
     * @param string $content
     * @param string $date_created
     */
    protected function __construct($id, $user, $riff, $content, $date_created) {
        $this->id = (int)$id;
        $this->user = $user;
        $this->riff = $riff;
        $this->content = $content;
        $this->date_created = $date_created;
        
        $this->upvotes = $this->get_num_upvotes();
        $this->is_upvoted = $this->get_is_upvoted();
        $this->is_starred = $this->get_is_starred();
        $this->image_url = $this->get_image_url();
    }
    
    /**
     * Helper function that returns the number of upvotes this post has.
     * 
     * @global PDO $dbh
     * @return int The number of upvotes.
     */
    protected function get_num_upvotes() {
        global $dbh;
        
        $query = "
            SELECT COUNT(*) AS `num_upvotes` FROM `upvotes`
            WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('post_id', $this->id);
        $sth->execute();
        return (int)$sth->fetchColumn();
    }
    
    /**
     * Helper function that returns whether this post is upvoted by the
     * current user.
     * 
     * @global PDO $dbh
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
        $sth->bindParam('post_id', $this->id);
        $sth->bindParam('user_id', $CURRENT_USER->id);
        $sth->execute();
        return (bool)$sth->fetchColumn();
    }
    
    /**
     * Helper function that returns whether this post is starred by the
     * current user.
     * 
     * @global PDO $dbh
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
        $sth->bindParam('post_id', $this->id);
        $sth->bindParam('user_id', $CURRENT_USER->id);
        $sth->execute();
        return (bool)$sth->fetchColumn();
    }
    
    /**
     * Helper function that returns the URL of this post's associated image.
     * 
     * @return string The image URL or "" if not found.
     */
    protected function get_image_url() {
        $image_path = MEDIA_ABSOLUTE_PATH."/posts/{$this->id}.png";
        if (file_exists($image_path)) {
            return MEDIA_URL."/posts/{$this->id}.png";
        }
        return "";
    }
    
    /**
     * Gets the post objects that are parents of this post.
     * 
     * @global PDO $dbh
     * @return array An array of Post objects
     */
    public function get_parents() {
        global $dbh;
        
        $parents = array();
        $query = "
            SELECT `parent_id` FROM `post_families`
            WHERE `child_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('post_id', $this->id);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $parents[] = Post::get_by_id((int)$row['parent_id']);
        }
        return $parents;
    }
    
    /**
     * Gets the post objects that are children of this post.
     * 
     * @global PDO $dbh
     * @return array An array of Post objects
     */
    public function get_children() {
        global $dbh;
        
        $children = array();
        $query = "
            SELECT `child_id` FROM `post_families`
            WHERE `parent_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('post_id', $this->id);
        $sth->execute();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $children[] = Post::get_by_id((int)$row['child_id']);
        }
        return $children;
    }
    
    /**
     * Adds a new Post.
     * 
     * @global PDO $dbh
     * @global User $CURRENT_USER
     * @param string $content The text of the post.
     * @param array $parent_ids The array of parent ids, array() for none.
     * @param type $img_tmp_path The path to the post image, "" for none.
     * @param type $title The title of the associated Riff, "" for none.
     * @param type $duration The duration of the associated Riff, 0 for none.
     * @param type $riff_tmp_path The path to the riff audio, "" for none.
     * @param type $user_id [optional] Defaults to the current user.
     * @return Post|null The added Post object or null on failure.
     */
    public static function add($content, $parent_ids, $img_tmp_path,
            $title, $duration, $riff_tmp_path, $user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            INSERT INTO `posts` (`user_id`, `content`)
            VALUES (:user_id, :content)";
        $sth = $dbh->prepare($query);
        $sth->bindParam('user_id', $user_id);
        $sth->bindParam('content', $content);
        if ($sth->execute()) {
            $post_id = $dbh->lastInsertId();
            
            if ($parent_ids) {
                if (!Post::add_parents($parent_ids)) {
                    Post::delete($post_id);
                    return null;
                }
                foreach ($parent_ids as $parent_id) {
                    $parent_post = Post::get_by_id($parent_id);
                    if ($parent_post->user->id !== (int)$user_id) {
                        Notification::add($parent_post->user->id, "remix", $parent_post->id, null, $post_id, null);
                    }
                }
            }
            
            if ($img_tmp_path) {
                $img_new_path = MEDIA_ABSOLUTE_PATH."/posts/$post_id.png";
                if (!move_uploaded_file($img_tmp_path, $img_new_path)) {
                    Post::delete($post_id);
                    return null;
                }
            }
            
            if ($riff_tmp_path) {
                if (!Riff::add($post_id, $title, $duration, $riff_tmp_path)) {
                    Post::delete($post_id);
                    return null;
                }
            }
            
            Tag::add_for_post($post_id, $content);
            Notification::add_mentions($post_id, $content);
            
            return Post::get_by_id($post_id);
        }
        return null;
    }
    
    /**
     * Adds the $parent_ids as parents of $post_id. The $parent_ids can be
     * either a comma-separated string or an array of ids.
     * 
     * @global PDO $dbh
     * @param int $post_id
     * @param string|array $parent_ids
     * @return boolean
     */
    public static function add_parents($post_id, $parent_ids) {
        global $dbh;
        
        if (!is_array($parent_ids)) {
            $parent_ids = explode(',', $parent_ids);
        }
        
        $query = "INSERT INTO `post_families` (`parent_id`, `child_id`) VALUES ";
        $query_pieces = array();
        $params = array();
        foreach ($parent_ids as $parent_id) {
            $query_pieces[] = "(?, ?)";
            $params[] = $parent_id;
            $params[] = $post_id;
        }
        $query .= implode(',', $query_pieces);
        $sth = $dbh->prepare($query);
        if ($sth->execute($params)) {
            return true;
        }
        return false;
    }
    
    /**
     * Deletes the given post.
     * 
     * @global PDO $dbh
     * @param int $post_id
     * @return boolean
     */
    public static function delete($post_id) {
        global $dbh;
        
        MediaFiles::delete_from_post((int)$post_id);
        
        $query = "
            DELETE FROM `posts`
            WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('post_id', $post_id);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Gets the post object with the given $post_id, if it exists.
     * 
     * @global PDO $dbh
     * @param int $post_id
     * @return Post|null
     */
    public static function get_by_id($post_id) {
        global $dbh;
        
        $query = "
            SELECT `user_id`, `content`, `date_created`
            FROM `posts`
            WHERE `post_id` = :post_id";
        $sth = $dbh->prepare($query);
        $sth->bindParam('post_id', $post_id);
        if ($sth->execute() && $sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            
            $user = User::get_by_id((int)$row['user_id']);
            $riff = Riff::get_by_post_id($post_id);
            
            $post = new Post($post_id, $user, $riff, 
                    $row['content'], $row['date_created']);
            return $post;
        }
        
        return null;
    }
}
