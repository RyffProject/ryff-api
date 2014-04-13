<?php

class Riff {
    public $id;
    public $title;
    public $duration;
    public $link;
    
    function __construct($id, $title, $duration, $link) {
        $this->id = $id;
        $this->title = $title;
        $this->duration = $duration;
        $this->link = $link;
    }
}