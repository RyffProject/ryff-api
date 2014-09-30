<?php

/**
 * @class User
 * ===========
 * 
 * Provides a class for User objects and static functions related to posts.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
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
    public $avatar_url;
    
    /**
     * The URL for the user's avatar thumbnail, or "" if none is set.
     * 
     * @var string
     */
    public $avatar_small_url;
    
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
        
        $this->avatar_url = $this->get_avatar_url();
        $this->avatar_small_url = $this->get_avatar_small_url();
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
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        if (file_exists("$media_dir/avatars/{$this->id}.png")) {
            return SITE_ROOT."/media/avatars/{$this->id}.png";
        } else {
            return "";
        }
    }
    
    /**
     * Helper function that returns the URL of this user's avatar image, or
     * "" if it doesn't exist.
     * 
     * @return string
     */
    protected function get_avatar_small_url() {
        $media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
        if (file_exists("$media_dir/avatars/small/{$this->id}.jpg")) {
            return SITE_ROOT."/media/avatars/small/{$this->id}.jpg";
        } else {
            return "";
        }
    }
    
    /**
     * Helper function that returns the total amount of upvotes this user's
     * posts have received.
     * 
     * @global NestedPDO $dbh
     * @return int
     */
    protected function get_karma() {
        global $dbh;
        
        $query = "
            SELECT SUM(c.`num_upvotes`) AS `karma`
            FROM (
              SELECT COUNT(*) AS `num_upvotes`
              FROM `upvotes` AS a
              JOIN `posts` AS b
              ON b.`post_id`=a.`post_id`
              WHERE b.`user_id` = :user_id
            ) AS c";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $this->id);
        $sth->execute();
        return (int)$sth->fetchColumn();
    }
    
    /**
     * Helper function that returns an array of Tag objects attached to this
     * User object.
     * 
     * @global NestedPDO $dbh
     * @return array An array of Tag objects.
     */
    protected function get_tags() {
        global $dbh;
        
        $tags = array();
        $query = "
            SELECT `tag` FROM `user_tags`
            WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $this->id);
        if ($sth->execute()) {
            while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
                $tags[] = Tag::get_by_tag($row['tag']);
            }
        }
        return $tags;
    }
    
    /**
     * Helper function that returns whether the current user is following this
     * user.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @return boolean
     */
    protected function get_is_following() {
        global $dbh, $CURRENT_USER;
        
        if (!isset($CURRENT_USER)) {
            return false;
        }
        
        $query = "
            SELECT `follow_id` FROM `follows`
            WHERE `from_id` = :from_id
            AND `to_id` = :to_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('from_id', $CURRENT_USER->id);
        $sth->bindValue('to_id', $this->id);
        $sth->execute();
        if ($sth->rowCount()) {
            return true;
        }
        return false;
    }
    
    /**
     * Helper function that returns the number of users who follow this user.
     * 
     * @global NestedPDO $dbh
     * @return int
     */
    protected function get_num_followers() {
        global $dbh;
        
        $query = "
            SELECT COUNT(*) AS `num_followers` FROM `follows`
            WHERE `to_id` = :to_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('to_id', $this->id);
        $sth->execute();
        return (int)$sth->fetchColumn();
    }
    
    /**
     * Helper function that returns the number of users that this user follows.
     * 
     * @global NestedPDO $dbh
     * @return int
     */
    protected function get_num_following() {
        global $dbh;
        
        $query = "
            SELECT COUNT(*) AS `num_following` FROM `follows`
            WHERE `from_id` = :from_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('from_id', $this->id);
        $sth->execute();
        return (int)$sth->fetchColumn();
    }
    
    /**
     * Returns this user's latest location.
     * 
     * @global NestedPDO $dbh
     * @return Point|null The user's latest location or null if it isn't set.
     */
    public function get_location() {
        global $dbh;
    
        $query = "
            SELECT X(`location`) AS `x`, Y(`location`) AS `y`
            FROM `locations` WHERE `user_id` = :user_id
            ORDER BY `date_created` DESC LIMIT 1";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $this->id);
        $sth->execute();
        if ($sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return new Point($row['x'], $row['y']);
        }
        return null;
    }
    
    /**
     * Sets this user's latest location.
     * 
     * @global NestedPDO $dbh
     * @param double $x
     * @param double $y
     * @return boolean
     */
    public function set_location($x, $y) {
        global $dbh;
        
        $query = "
            INSERT INTO `locations` (`user_id`, `location`)
            VALUES (:user_id, POINT(:x, :y))";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $this->id);
        $sth->bindValue('x', $x);
        $sth->bindValue('y', $y);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    /**
     * Helper function that updates an attribute for the current user both in
     * the database and in this User object.
     * 
     * @global NestedPDO $dbh
     * @param string $key
     * @param string $value
     * @return boolean
     */
    protected function set_attribute($key, $value) {
        global $dbh;
        
        $query = "
            UPDATE `users` SET `$key` = :value
            WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('value', $value);
        $sth->bindValue('user_id', $this->id);
        if ($sth->execute()) {
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
     * @global NestedPDO $dbh
     * @param string $password
     * @return boolean
     */
    public function set_password($password) {
        global $dbh;
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "
            UPDATE `users` SET `password` = :password_hash
            WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('password_hash', $password_hash);
        $sth->bindValue('user_id', $this->id);
        if ($sth->execute()) {
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
     * @global NestedPDO $dbh
     * @param string $name
     * @param string $username
     * @param string $email
     * @param string $bio
     * @param string $password
     * @param string $avatar_tmp_path
     * @param string $activation_code [optional]
     * @return User|null The new User object, or null on failure.
     */
    public static function add($name, $username, $email, $bio, $password,
            $avatar_tmp_path, $activation_code = null) {
        global $dbh;
        
        $dbh->beginTransaction();
        
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "
            INSERT INTO `users` (
                `name`, `username`, `email`,
                `bio`, `password`, `date_updated`
            ) VALUES (
                :name, :username, :email,
                :bio, :password_hash, NOW()
            )";
        $sth = $dbh->prepare($query);
        $sth->bindValue('name', $name);
        $sth->bindValue('username', $username);
        $sth->bindValue('email', $email);
        $sth->bindValue('bio', $bio);
        $sth->bindValue('password_hash', $password_hash);
        if (!$sth->execute()) {
            $dbh->rollBack();
            return null;
        }
        
        $user_id = $dbh->lastInsertId();
        if ($avatar_tmp_path) {
            if (!MediaFiles::save_avatar($avatar_tmp_path, $user_id)) {
                $dbh->rollBack();
                return null;
            }
        }
        
        $user = User::get_by_id($user_id);
        if (!$user) {
            $dbh->rollBack();
            return null;
        }
        
        if (!REGISTRATION_OPEN && !Preregister::set_used($activation_code, $user_id)) {
            $dbh->rollBack();
            return null;
        }
        
        $dbh->commit();
        return $user;
    }
    
    /**
     * Deletes the given user.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function delete($user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        MediaFiles::delete_from_user($user_id);
        
        $query = "
            DELETE FROM `users`
            WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        if ($sth->execute()) {
            return true;
        }
        return false;
    }
    
    public static function get_audio_usage($user_id, $timeframe) {
        global $dbh;
        
        $from_date = date("Y-m-d H:i:s", time() - $timeframe);
        $query = "
            SELECT SUM(p.`duration`) AS `length`, SUM(p.`filesize`) AS `size`
            FROM `posts` AS p
            WHERE p.`user_id` = :user_id
            AND p.`date_created` >= :from_date";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->bindValue('from_date', $from_date);
        if (!$sth->execute() || !$sth->rowCount() || !($row = $sth->fetch(PDO::FETCH_ASSOC))) {
            return null;
        }
        return $row;
    }
    
    /**
     * Returns the username corresponding to the given $user_id, or null if
     * not found.
     * 
     * @global NestedPDO $dbh
     * @param int $user_id
     * @return string|null
     */
    public static function get_username($user_id) {
        global $dbh;
        
        $query = "
            SELECT `username` FROM `users`
            WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        if ($sth->execute()) {
            return $sth->fetchColumn();
        }
        return null;
    }
    
    /**
     * Returns the user with the given username, or null if it doesn't exist.
     * 
     * @global NestedPDO $dbh
     * @param string $username
     * @return User|null
     */
    public static function get_by_username($username) {
        global $dbh;

        $query = "
            SELECT `user_id`, `name`, `username`,
                `email`, `bio`, `date_created`
            FROM `users`
            WHERE `username` = :username";
        $sth = $dbh->prepare($query);
        $sth->bindValue('username', $username);
        $sth->execute();
        if ($sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return User::create($row);
        }
        return null;
    }
    
    /**
     * Returns the user with the given email, or null if it doesn't exist.
     * 
     * @global NestedPDO $dbh
     * @param string $email
     * @return User|null
     */
    public static function get_by_email($email) {
        global $dbh;

        $query = "
            SELECT `user_id`, `name`, `username`,
                `email`, `bio`, `date_created`
            FROM `users`
            WHERE `email` = :email";
        $sth = $dbh->prepare($query);
        $sth->bindValue('email', $email);
        $sth->execute();
        if ($sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return User::create($row);
        }
        return null;
    }
    
    /**
     * Returns the user with the given user_id, or null if it doesn't exist.
     * 
     * @global NestedPDO $dbh
     * @param int $user_id
     * @return User|null
     */
    public static function get_by_id($user_id) {
        global $dbh;

        $query = "
            SELECT `user_id`, `name`, `username`,
                `email`, `bio`, `date_created`
            FROM `users`
            WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->execute();
        if ($sth->rowCount()) {
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            return User::create($row);
        }
        return null;
    }
    
    /**
     * Returns true if the user with the given id exists, or false otherwise.
     * 
     * @global NestedPDO $dbh
     * @param int $user_id
     * @return boolean
     */
    public static function exists($user_id) {
        global $dbh;
        
        $query = "
            SELECT 1 FROM `users` WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->execute();
        return (bool)$sth->fetchColumn();
    }
}
