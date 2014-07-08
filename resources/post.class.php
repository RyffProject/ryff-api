<?php

class Post {
    public $id;
    public $parent_id;
    public $user;
    public $riff;
    public $content;
    public $date_created;
    
    public $upvotes;
    public $is_upvoted;
    
    protected function __construct($id, $parent_id, $user, $riff, $content, $date_created,
            $upvotes = 0, $is_upvoted = false) {
        $this->id = (int)$id;
        $this->parent_id = (int)$parent_id;
        $this->user = $user;
        $this->riff = $riff;
        $this->content = $content;
        $this->date_created = $date_created;
        
        $this->upvotes = $this->get_num_upvotes();
        $this->is_upvoted = $this->is_upvoted();
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
    
    protected function is_upvoted() {
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
    
    public static function get_by_id($post_id) {
        global $db;

        $post_query = "SELECT * FROM `posts` WHERE `post_id`=".$db->real_escape_string($post_id);
        $post_results = $db->query($post_query);
        if ($post_results && $post_results->num_rows && $post_row = $post_results->fetch_assoc()) {
            $user = User::get_by_id($post_row['user_id']);
            $riff = Riff::get_by_post_id($post_id);
            
            $post = new Post($post_id, $post_row['parent_id'], $user, $riff, 
                    $post_row['content'], $post_row['date_created']);
            return $post;
        }
        
        return false;
    }
}
