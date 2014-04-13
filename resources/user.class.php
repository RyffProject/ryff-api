<?php

class User {
    public $id;
    public $name;
    public $username;
    public $email;
    public $bio;
    public $date_created;
    public $avatar;
    
    function __construct($id, $name, $username, $email, $bio, $date_created) {
        $this->id = $id;
        $this->name = $name;
        $this->username = $username;
        $this->email = $email;
        $this->bio = $bio;
        $this->date_created = $date_created;
        $this->avatar = get_avatar_url($id);
    }
}