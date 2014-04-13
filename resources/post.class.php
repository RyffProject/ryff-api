<?php

class Post {
    public $id;
    public $user;
    public $riff;
    public $content;
    public $date_created;
    
    function __construct($id, $user, $riff, $content, $date_created) {
        $this->id = $id;
        $this->user = $user;
        $this->riff = $riff;
        $this->content = $content;
        $this->date_created = $date_created;
    }
}