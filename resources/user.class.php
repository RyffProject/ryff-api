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
    public $genres;
    public $instruments;
    public $is_following;
    
    protected function __construct($id, $name, $username, $email, $bio, $date_created) {
        $this->id = (int)$id;
        $this->name = $name;
        $this->username = $username;
        $this->email = $email;
        $this->bio = $bio;
        $this->date_created = $date_created;
        
        $this->avatar = $this->get_avatar_url();
        $this->karma = $this->get_karma();
        $this->genres = $this->get_genres();
        $this->instruments = $this->get_instruments();
        $this->is_following = $this->get_is_following();
    }
    
    protected function get_avatar_url() {
        $path = AVATAR_ABSOLUTE_PATH."/$this->id.png";
        if (file_exists($path)) {
            return SITE_ROOT."/avatars/$this->id.png";
        } else {
            return "";
        }
    }
    
    protected function get_karma() {
        global $db;
        
        $karma_query = "SELECT SUM(c.`num_upvotes`) AS `karma`
                        FROM (
                          SELECT COUNT(*) AS `num_upvotes`
                          FROM `upvotes` AS a
                          JOIN `posts` AS b
                          ON b.`post_id`=a.`post_id`
                          WHERE b.`user_id`=".$db->real_escape_string($this->id)."
                        ) AS c";
        $karma_results = $db->query($karma_query);
        if ($karma_results && $karma_results->num_rows) {
            $karma_row = $karma_results->fetch_assoc();
            return (int)$karma_row['karma'];
        }
        
        return 0;
    }
    
    protected function get_genres() {
        global $db;
        
        $genres = array();
        $genre_query = "SELECT `genre` FROM `genres`
                        WHERE `user_id`=".$db->real_escape_string($this->id);
        $genre_results = $db->query($genre_query);
        if ($genre_results && $genre_results->num_rows) {
            while ($genre_row = $genre_results->fetch_assoc()) {
                $genres[] = $genre_row['genre'];
            }
        }
        return $genres;
    }
    
    protected function get_instruments() {
        global $db;
        
        $instruments = array();
        $instrument_query = "SELECT `instrument` FROM `instruments`
                             WHERE `user_id`=".$db->real_escape_string($this->id);
        $instrument_results = $db->query($instrument_query);
        if ($instrument_results && $instrument_results->num_rows) {
            while ($instrument_row = $instrument_results->fetch_assoc()) {
                $instruments[] = $instrument_row['instrument'];
            }
        }
        return $instruments;
    }
    
    protected function get_is_following() {
        global $db, $CURRENT_USER;
        
        if (!isset($CURRENT_USER)) {
            return false;
        }
        
        $is_following_query = "
            SELECT `follow_id` FROM `follows`
            WHERE `from_id`=".$db->real_escape_string($CURRENT_USER->id)."
            AND `to_id`=".$db->real_escape_string($this->id);
        $is_following_result = $db->query($is_following_query);
        if ($is_following_result && $is_following_result->num_rows > 0) {
            return true;
        }
        
        return false;
    }
    
    public function get_location() {
        global $db;
    
        $query = "SELECT X(`location`) AS `x`, Y(`location`) AS `y`
                  FROM `locations` WHERE `user_id`=".$db->real_escape_string($this->id)."
                  ORDER BY `date_created` DESC LIMIT 1";
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            return new Point($row['x'], $row['y']);
        }
        
        return null;
    }
    
    public function set_logged_in() {
        global $db;
        
        $expiration = time() + COOKIE_LIFESPAN;
        $expiration_date = date('Y-m-d H:i:s', $expiration);
        $auth_token = bin2hex(openssl_random_pseudo_bytes(32));
        
        $insert_auth_query = "
            INSERT INTO `auth_tokens` (`user_id`, `token`, `date_expires`)
            VALUES
            (
                ".$db->real_escape_string($this->id).",
                '".$db->real_escape_string($auth_token)."',
                '".$db->real_escape_string($expiration_date)."'
            )";
        
        if (!$db->query($insert_auth_query)) {
            return false;
        }
        
        setcookie('user_id', $this->id, $expiration);
        setcookie('auth_token', $auth_token, $expiration);
        
        return true;
    }
    
    public static function create($row) {
        $required_keys = array(
            'user_id' => 0, 'name' => 0, 'username' => 0, 
            'email' => 0, 'bio' => 0, 'date_created' => 0
        );
        if (empty(array_diff_key($required_keys, $row))) {
            return new User(
                $row['user_id'], $row['name'], $row['username'],
                $row['email'], $row['bio'], $row['date_created']
            );
        }
        return null;
    }
    
    public static function get_by_username($username) {
        global $db;

        $query = "SELECT * FROM `users`
                  WHERE `username`='".$db->real_escape_string($username)."'
                  AND `active`=1";
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            return User::create($row);
        }
        
        return null;
    }
    
    public static function get_by_email($email) {
        global $db;

        $query = "SELECT * FROM `users`
                  WHERE `email`='".$db->real_escape_string($email)."'
                  AND `active`=1";
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            return User::create($row);
        }
        
        return null;
    }
    
    public static function get_by_id($user_id) {
        global $db;

        $query = "SELECT * FROM `users`
                  WHERE `user_id`=".$db->real_escape_string((int)$user_id)."
                  AND `active`=1";
        $results = $db->query($query);
        if ($results) {
            if ($row = $results->fetch_assoc()) {
                $user = User::create($row);
                return $user;
            }
        }
        
        return null;
    }
    
    public static function is_login_valid($username, $password) {
        global $db;

        $query = "SELECT `password` FROM `users`
                  WHERE `username`='".$db->real_escape_string($username)."'
                  AND `active`=1";
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            $password_hash = $row['password'];
            if (password_verify($password, $password_hash)) {
                return true;
            }
        }
        
        return false;
    }
}
