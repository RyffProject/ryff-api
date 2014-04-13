<?php

class Riff {
    public $id;
    public $title;
    public $link;
    
    function __construct($id, $title, $link) {
        $this->id = $id;
        $this->title = $title;
        $this->link = $link;
    }
}