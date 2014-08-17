<?php

class Post {
    public $id;
    public $user;
    public $riff;
    public $content;
    public $date_created;
    
    public $upvotes;
    public $is_upvoted;
    public $is_starred;
    public $image_url;
    
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
    
    protected function get_num_upvotes() {
        global $db;
        
        $upvotes_query = "SELECT COUNT(*) AS `num_upvotes` FROM `upvotes`
                          WHERE `post_id`=".$db->real_escape_string($this->id);
        $upvotes_results = $db->query($upvotes_query);
        if ($upvotes_results && $upvotes_results->num_rows) {
            $upvotes_row = $upvotes_results->fetch_assoc();
            return (int)$upvotes_row['num_upvotes'];
        }
        
        return 0;
    }
    
    protected function get_is_upvoted() {
        global $db, $CURRENT_USER;
        
        if ($CURRENT_USER) {
            $upvote_query = "SELECT * FROM `upvotes`
                             WHERE `post_id`=".$db->real_escape_string($this->id)."
                             AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
            $upvote_results = $db->query($upvote_query);
            if ($upvote_results && $upvote_results->num_rows > 0) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function get_is_starred() {
        global $db, $CURRENT_USER;
        
        if ($CURRENT_USER) {
            $star_query = "SELECT * FROM `stars`
                           WHERE `post_id`=".$db->real_escape_string($this->id)."
                           AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
            $star_results = $db->query($star_query);
            if ($star_results && $star_results->num_rows > 0) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function get_image_url() {
        $image_path = MEDIA_ABSOLUTE_PATH."/posts/{$this->id}.png";
        if (file_exists($image_path)) {
            return MEDIA_URL."/posts/{$this->id}.png";
        }
        return "";
    }
    
    public function get_parents() {
        global $db;
        
        $parents = array();
        $parents_query = "SELECT `parent_id` FROM `post_families`
                          WHERE `child_id`=".$db->real_escape_string($this->id);
        $parents_results = $db->query($parents_query);
        if ($parents_results) {
            while ($parent_row = $parents_results->fetch_assoc()) {
                $parents[] = Post::get_by_id((int)$parent_row['parent_id']);
            }
        }
        return $parents;
    }
    
    public function get_children() {
        global $db;
        
        $children = array();
        $children_query = "SELECT `child_id` FROM `post_families`
                           WHERE `parent_id`=".$db->real_escape_string($this->id);
        $children_results = $db->query($children_query);
        if ($children_results) {
            while ($child_row = $children_results->fetch_assoc()) {
                $children[] = Post::get_by_id((int)$child_row['child_id']);
            }
        }
        return $children;
    }
    
    public static function get_by_id($post_id) {
        global $db;
        
        $post_id = (int)$post_id;
        $post_query = "SELECT * FROM `posts` WHERE `post_id`=".$db->real_escape_string($post_id);
        $post_results = $db->query($post_query);
        if ($post_results && $post_results->num_rows && $post_row = $post_results->fetch_assoc()) {
            $user = User::get_by_id($post_row['user_id']);
            $riff = Riff::get_by_post_id($post_id);
            
            $post = new Post($post_id, $user, $riff, 
                    $post_row['content'], $post_row['date_created']);
            return $post;
        }
        
        return null;
    }
}