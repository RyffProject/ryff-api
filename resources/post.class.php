<?php

class Post {
    public $id;
    public $user;
    public $riff;
    public $content;
    public $date_created;
    
    public $upvotes;
    public $is_upvoted;
    
    protected function __construct($id, $user, $riff, $content, $date_created) {
        $this->id = (int)$id;
        $this->user = $user;
        $this->riff = $riff;
        $this->content = $content;
        $this->date_created = $date_created;
        
        $this->upvotes = $this->get_num_upvotes();
        $this->is_upvoted = $this->get_is_upvoted();
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
            $upvotes_query = "SELECT * FROM `upvotes`
                              WHERE `post_id`=".$db->real_escape_string($this->id)."
                              AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
            $upvotes_results = $db->query($upvotes_query);
            if ($upvotes_results && $upvotes_results->num_rows) {
                return true;
            }
        }
        
        return false;
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
