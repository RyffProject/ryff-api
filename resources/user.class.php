<?php

class User {
    public $id;
    public $name;
    public $username;
    public $email;
    public $bio;
    
    function __construct($id, $name, $username, $email, $bio) {
        $this->id = $id;
        $this->name = $name;
        $this->username = $username;
        $this->email = $email;
        $this->bio = $bio;
    }
}