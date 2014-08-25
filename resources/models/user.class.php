<?php

/**
 * @class User
 * ===========
 * 
 * Provides a class for User objects and static functions related to posts.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class User {
    /**
     * The user_id.
     * 
     * @var int
     */
    public $id;
    
    /**
     * The user's name.
     * 
     * @var string
     */
    public $name;
    
    /**
     * The user's username.
     * 
     * @var string
     */
    public $username;
    
    /**
     * The user's email.
     * 
     * @var string
     */
    public $email;
    
    /**
     * The user's bio[graphy]
     * 
     * @var string
     */
    public $bio;
    
    /**
     * The date this user registered.
     * 
     * @var string
     */
    public $date_created;
    
    /**
     * The URL for the user's avatar image, or "" if none is set.
     * 
     * @var string
     */
    public $avatar;
    
    /**
     * The total amount of upvotes this user's posts have received.
     * 
     * @var int
     */
    public $karma;
    
    /**
     * An array of Tag objects attached to this user.
     * 
     * @var array
     */
    public $tags;
    
    /**
     * Whether the current user is following this user.
     * 
     * @var boolean
     */
    public $is_following;
    
    /**
     * The number of followers this user has.
     * 
     * @var int
     */
    public $num_followers;
    
    /**
     * The number of users this user follows.
     * 
     * @var int
     */
    public $num_following;
    
    /**
     * Constructs a new User instance with the given member variable values.
     * 
     * @param int $id
     * @param string $name
     * @param string $username
     * @param string $email
     * @param string $bio
     * @param string $date_created
     */
    protected function __construct($id, $name, $username, $email, $bio, $date_created) {
        $this->id = (int)$id;
        $this->name = $name;
        $this->username = $username;
        $this->email = $email;
        $this->bio = $bio;
        $this->date_created = $date_created;
        
        $this->avatar = $this->get_avatar_url();
        $this->karma = $this->get_karma();
        $this->tags = $this->get_tags();
        $this->is_following = $this->get_is_following();
        $this->num_followers = $this->get_num_followers();
        $this->num_following = $this->get_num_following();
    }
    
    /**
     * Helper function that returns the URL of this user's avatar image, or
     * "" if it doesn't exist.
     * 
     * @return string
     */
    protected function get_avatar_url() {
        $path = MEDIA_ABSOLUTE_PATH."/avatars/{$this->id}.png";
        if (file_exists($path)) {
            return MEDIA_URL."/avatars/{$this->id}.png";
        } else {
            return "";
        }
    }
    
    /**
     * Helper function that returns the total amount of upvotes this user's
     * posts have received.
     * 
     * @global mysqli $db
     * @return int
     */
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
    
    /**
     * Helper function that returns an array of Tag objects attached to this
     * User object.
     * 
     * @global mysqli $db
     * @return array An array of Tag objects.
     */
    protected function get_tags() {
        global $db;
        
        $tags = array();
        $tag_query = "SELECT `tag` FROM `user_tags`
                      WHERE `user_id`=".$db->real_escape_string($this->id);
        $tag_results = $db->query($tag_query);
        if ($tag_results) {
            while ($tag_row = $tag_results->fetch_assoc()) {
                $tags[] = Tag::get_by_tag($tag_row['tag']);
            }
        }
        return $tags;
    }
    
    /**
     * Helper function that returns whether the current user is following this
     * user.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @return boolean
     */
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
    
    /**
     * Helper function that returns the number of users who follow this user.
     * 
     * @global mysqli $db
     * @return int
     */
    protected function get_num_followers() {
        global $db;
        
        $num_followers_query = "
            SELECT COUNT(*) AS `num_followers` FROM `follows`
            WHERE `to_id`=".$db->real_escape_string($this->id);
        $num_followers_result = $db->query($num_followers_query);
        if ($num_followers_result && $num_followers_result->num_rows > 0) {
            $row = $num_followers_result->fetch_assoc();
            return (int)$row['num_followers'];
        }
        
        return 0;
    }
    
    /**
     * Helper function that returns the number of users that this user follows.
     * 
     * @global mysqli $db
     * @return int
     */
    protected function get_num_following() {
        global $db;
        
        $num_following_query = "
            SELECT COUNT(*) AS `num_following` FROM `follows`
            WHERE `from_id`=".$db->real_escape_string($this->id);
        $num_following_result = $db->query($num_following_query);
        if ($num_following_result && $num_following_result->num_rows > 0) {
            $row = $num_following_result->fetch_assoc();
            return (int)$row['num_following'];
        }
        
        return 0;
    }
    
    /**
     * Returns this user's latest location.
     * 
     * @global mysqli $db
     * @return Point|null The user's latest location or null if it isn't set.
     */
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
    
    /**
     * Sets this user's latest location.
     * 
     * @global mysqli $db
     * @param double $x
     * @param double $y
     * @return boolean
     */
    public function set_location($x, $y) {
        global $db;
        
        $location_query = "
            INSERT INTO `locations` (`user_id`, `location`)
            VALUES (
                ".$db->real_escape_string($this->id).",
                POINT(
                    ".$db->real_escape_string((double)$x).",
                    ".$db->real_escape_string((double)$y)."
                )
            )";
        $results = $db->query($location_query);
        if ($results) {
            return true;
        }
        return false;
    }
    
    /**
     * Helper function that updates an attribute for the current user both in
     * the database and in this User object.
     * 
     * @global mysqli $db
     * @param string $key
     * @param string $value
     * @return boolean
     */
    protected function set_attribute($key, $value) {
        global $db;
        $query = "UPDATE `users` SET `$key`='".$db->real_escape_string($value)."'
                  WHERE `user_id`=".$db->real_escape_string($this->id);
        if ($db->query($query)) {
            $this->$key = $value;
            return true;
        }
        return false;
    }
    
    /**
     * Updates this user's name in the database.
     * 
     * @param string $name
     * @return boolean
     */
    public function set_name($name) {
        return $this->set_attribute('name', $name);
    }
    
    /**
     * Updates this user's username in the database.
     * 
     * @param string $username
     * @return boolean
     */
    public function set_username($username) {
        return $this->set_attribute('username', $username);
    }
    
    /**
     * Updates this user's email in the database.
     * 
     * @param string $email
     * @return boolean
     */
    public function set_email($email) {
        return $this->set_attribute('email', $email);
    }
    
    /**
     * Updates this user's bio[graphy] in the database.
     * 
     * @param string $bio
     * @return boolean
     */
    public function set_bio($bio) {
        return $this->set_attribute('bio', $bio);
    }
    
    /**
     * Updates this user's password in the database.
     * 
     * @global mysqli $db
     * @param string $password
     * @return boolean
     */
    public function set_password($password) {
        global $db;
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE `users` SET `password`='".$db->real_escape_string($password_hash)."'
                  WHERE `user_id`=".$db->real_escape_string($this->id);
        if ($db->query($query)) {
            return true;
        }
        return false;
    }
    
    /**
     * Sets this user's avatar image.
     * 
     * @param string $avatar_tmp_path
     * @return boolean
     */
    public function set_avatar($avatar_tmp_path) {
        $avatar_new_path = MEDIA_ABSOLUTE_PATH."/avatars/{$this->id}.png";
        if (move_uploaded_file($avatar_tmp_path, $avatar_new_path)) {
            $this->avatar = $this->get_avatar_url();
            return true;
        }
        return false;
    }
    
    /**
     * Constructs and returns a User instance from a database row.
     * 
     * @param type $row
     * @return User|null
     */
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
    
    /**
     * Adds a new user.
     * 
     * @global mysqli $db
     * @param string $name
     * @param string $username
     * @param string $email
     * @param string $bio
     * @param string $password
     * @param string $avatar_tmp_path
     * @return User|null The new User object, or null on failure.
     */
    public static function add($name, $username, $email, $bio, $password, $avatar_tmp_path) {
        global $db;
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "
            INSERT INTO `users` (
                `name`, `username`, `email`,
                `bio`, `password`, `date_updated`
            ) VALUES (
                '".$db->real_escape_string($name)."',
                '".$db->real_escape_string($username)."',
                '".$db->real_escape_string($email)."',
                '".$db->real_escape_string($bio)."',
                '".$db->real_escape_string($password_hash)."',
                NOW()
            )";
        $results = $db->query($query);
        if (!$results) {
            return null;
        }
        
        $user_id = $db->insert_id;
        if ($avatar_tmp_path) {
            $avatar_new_path = MEDIA_ABSOLUTE_PATH."/avatars/$user_id.png";
            if (!move_uploaded_file($avatar_tmp_path, $avatar_new_path)) {
                User::delete($user_id);
                return null;
            }
        }
        return User::get_by_id($user_id);
    }
    
    /**
     * Deletes the given user.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function delete($user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        MediaFiles::delete_from_user($user_id);
        
        $query = "
            DELETE FROM `users`
            WHERE `user_id`=".$db->real_escape_string((int)$user_id);
        $results = $db->query($query);
        if ($results) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns the user with the given username, or null if it doesn't exist.
     * 
     * @global mysqli $db
     * @param string $username
     * @return User|null
     */
    public static function get_by_username($username) {
        global $db;

        $query = "SELECT * FROM `users`
                  WHERE `username`='".$db->real_escape_string($username)."'";
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            return User::create($row);
        }
        
        return null;
    }
    
    /**
     * Returns the user with the given email, or null if it doesn't exist.
     * 
     * @global mysqli $db
     * @param string $email
     * @return User|null
     */
    public static function get_by_email($email) {
        global $db;

        $query = "SELECT * FROM `users`
                  WHERE `email`='".$db->real_escape_string($email)."'";
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            return User::create($row);
        }
        
        return null;
    }
    
    /**
     * Returns the user with the given user_id, or null if it doesn't exist.
     * 
     * @global mysqli $db
     * @param type $user_id
     * @return User|null
     */
    public static function get_by_id($user_id) {
        global $db;

        $query = "SELECT * FROM `users`
                  WHERE `user_id`=".$db->real_escape_string((int)$user_id);
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            return User::create($row);
        }
        
        return null;
    }
}
