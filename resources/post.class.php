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
    
    function __construct($id, $parent_id, $user, $riff, $content, $date_created,
            $upvotes = 0, $is_upvoted = false) {
        $this->id = (int)$id;
        $this->parent_id = (int)$parent_id;
        $this->user = $user;
        $this->riff = $riff;
        $this->content = $content;
        $this->date_created = $date_created;
        
        $this->upvotes = $upvotes;
        $this->is_upvoted = $is_upvoted;
    }
}