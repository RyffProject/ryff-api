<?php

class User {
    public $id;
    public $name;
    public $username;
    public $email;
    public $bio;
    public $date_created;
    public $avatar;
    public $karma;
    
    function __construct($id, $name, $username, $email, $bio, $date_created) {
        global $db;
        
        $this->id = (int)$id;
        $this->name = $name;
        $this->username = $username;
        $this->email = $email;
        $this->bio = $bio;
        $this->date_created = $date_created;
        $this->avatar = get_avatar_url($id);
        $this->karma = User::getKarma($this->id);
    }
    
    static function getKarma($user_id) {
        global $db;
        
        $karma_query = "SELECT SUM(c.`num_upvotes`) AS `karma`
                        FROM (
                          SELECT COUNT(*) AS `num_upvotes`
                          FROM `upvotes` AS a
                          JOIN `posts` AS b
                          ON b.`post_id`=a.`post_id`
                          WHERE b.`user_id`=".$db->real_escape_string((int)$user_id)."
                        ) AS c";
        $karma_results = $db->query($karma_query);
        if ($karma_results && $karma_results->num_rows) {
            $karma_row = $karma_results->fetch_assoc();
            return (int)$karma_row['karma'];
        }
        return 0;
    }
}