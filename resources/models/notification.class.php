<?php

/**
 * @class Notification
 * ===================
 * 
 * Provides a class for Notification objects and static functions related to
 * notifications.
 * 
 * Each notification object optionally has one or more of the following
 * member variables: user, post, users, and posts. For notifications of type
 * "follow", users is an array of User objects that did the following. For type
 * "upvote", post is the Post object that was upvoted and users is an array
 * of the User objects that did the upvoting. For type "mention", posts is an
 * array of Post objects that the user was mentioned in. For type "remix", posts
 * is an array of Post objects that used one of the user's posts as a parent.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Notification {
    /**
     * The notification_id.
     * 
     * @var int
     */
    public $id;
    
    /**
     * The type of notifcation.
     * 
     * @var string
     */
    public $type;
    
    /**
     * Whether the notification has been read.
     * 
     * @var boolean
     */
    public $is_read;
    
    /**
     * The date that the notification was updated. Notifications are updated
     * when a similar notification stacks onto them.
     * 
     * @var string
     */
    public $date_updated;
    
    /**
     * The date the notification was created.
     * 
     * @var string
     */
    public $date_created;
    
    /**
     * Constructs a new Notification instance with the given member variable values.
     * 
     * @param int $id
     * @param string $type
     * @param boolean $is_read
     * @param string $date_read
     * @param string $date_updated
     * @param string $date_created
     */
    protected function __construct($id, $type, $is_read,
            $date_read, $date_updated, $date_created) {
        
        $this->id = (int)$id;
        $this->type = $type;
        $this->is_read = (bool)$is_read;
        if ($this->is_read) {
            $this->date_read = $date_read;
        }
        $this->date_updated = $date_updated;
        $this->date_created = $date_created;
        
        $this->get_objects();
    }
    
    /**
     * Helper function for constructor that optionally attaches user, post,
     * users, and posts as member variables.
     * 
     * @global mysqli $db
     */
    protected function get_objects() {
        global $db;
        
        $base_query = "
            SELECT `user_obj_id`, `post_obj_id` FROM `notifications`
            WHERE `notification_id`=".$db->real_escape_string($this->id);
        $base_results = $db->query($base_query);
        if ($base_results && $base_results->num_rows) {
            $base_row = $base_results->fetch_assoc();
            if ($base_row['post_obj_id'] && $post = Post::get_by_id($base_row['post_obj_id'])) {
                $this->post = $post;
            }
            if ($base_row['user_obj_id'] && $user = User::get_by_id($base_row['user_obj_id'])) {
                $this->user = $user;
            }
        }
        
        $leaves_query = "
            SELECT `user_obj_id`, `post_obj_id` FROM `notification_objects`
            WHERE `notification_id`=".$db->real_escape_string($this->id)."
            ORDER BY `date_created` DESC";
        $leaves_results = $db->query($leaves_query);
        if ($leaves_results) {
            while ($leaf_row = $leaves_results->fetch_assoc()) {
                if ($leaf_row['post_obj_id'] && $post = Post::get_by_id($leaf_row['post_obj_id'])) {
                    $this->posts[] = $post;
                }
                if ($leaf_row['user_obj_id'] && $user = User::get_by_id($leaf_row['user_obj_id'])) {
                    $this->users[] = $user;
                }
            }
        }
    }
    
    /**
     * Constructs and returns a Notification instance from a database row.
     * 
     * @param array $row
     * @return Notification|null
     */
    protected static function create($row) {
        $required_keys = array(
            'notification_id' => 0, 'type' => 0, 'read' => 0,
            'date_read' => 0, 'date_updated' => 0, 'date_created' => 0
        );
        if (empty(array_diff_key($required_keys, $row))) {
            return new Notification(
                $row['notification_id'], $row['type'], $row['read'],
                $row['date_read'], $row['date_updated'], $row['date_created']
            );
        }
        return null;
    }
    
    /**
     * Adds a notification for the given user. If the $user_id, $type,
     * $base_post_obj_id, and $base_user_obj_id match a notification that has
     * been updated less than NOTIFICATION_TIMEOUT seconds ago, the new
     * notification will be stacked onto the matching one.
     * 
     * @global mysqli $db
     * @param int $user_id The user who will receive the notification.
     * @param string $type
     * @param int $base_post_obj_id The optional "post", or null.
     * @param int $base_user_obj_id The optional "user", or null.
     * @param int $leaf_post_obj_id One of the optional "posts", or null.
     * @param int $leaf_user_obj_id One of the optional "users", or null.
     * @return Notification|null
     */
    public static function add($user_id, $type, $base_post_obj_id,
            $base_user_obj_id, $leaf_post_obj_id, $leaf_user_obj_id) {
        global $db;
        
        $stack_query = "
            SELECT `notification_id` FROM `notifications`
            WHERE `user_id`=".$db->real_escape_string((int)$user_id)."
            AND `type`='".$db->real_escape_string($type)."'
            AND `post_obj_id`".($base_post_obj_id ? "=".$db->real_escape_string((int)$base_post_obj_id) : " IS NULL")."
            AND `user_obj_id`".($base_user_obj_id ? "=".$db->real_escape_string((int)$base_user_obj_id) : " IS NULL")."
            AND `date_updated` > (NOW() -".NOTIFICATION_TIMEOUT.")";
        $stack_results = $db->query($stack_query);
        if (!$stack_results) {
            return null;
        }
        if ($stack_results->num_rows) {
            $stack_row = $stack_results->fetch_assoc();
            $notification_id = (int)$stack_row['notification_id'];
            $base_query = "
                UPDATE `notifications`
                SET `read`=0, `date_read`=0, `date_updated`=NOW()
                WHERE `notification_id`=".$db->real_escape_string($notification_id);
            if (!$db->query($base_query)) {
                return null;
            }
        } else {
            $base_query = "
                INSERT INTO `notifications` (
                    `user_id`, `type`, `post_obj_id`, `user_obj_id`, `date_updated`
                ) VALUES (
                    ".$db->real_escape_string((int)$user_id).",
                    '".$db->real_escape_string($type)."',
                    ".($base_post_obj_id ? $db->real_escape_string((int)$base_post_obj_id) : "NULL").",
                    ".($base_user_obj_id ? $db->real_escape_string((int)$base_user_obj_id) : "NULL").",
                    NOW()
                )";
            if (!$db->query($base_query)) {
                return null;
            }
            $notification_id = $db->insert_id;
        }
        
        $leaf_query = "
            INSERT INTO `notification_objects` (
                `notification_id`, `post_obj_id`, `user_obj_id`
            ) VALUES (
                ".$db->real_escape_string($notification_id).",
                ".($leaf_post_obj_id ? $db->real_escape_string((int)$leaf_post_obj_id) : "NULL").",
                ".($leaf_user_obj_id ? $db->real_escape_string((Int)$leaf_user_obj_id) : "NULL")."
            )";
        if ($db->query($leaf_query)) {
            return Notification::get_by_id($notification_id, $user_id);
        }
        return null;
    }
    
    /**
     * Deletes a notification. On unfollow, the follow notification has to be
     * removed, and on delete-upvote, the upvote notification has to be removed.
     * For notifications of type "mention" and "remix" the notification row
     * will be automatically deleted due to foreign keys.
     * 
     * @global mysqli $db
     * @param int $user_id The user who will receive the notification.
     * @param string $type
     * @param int $base_post_obj_id The optional "post", or null.
     * @param int $base_user_obj_id The optional "user", or null.
     * @param int $leaf_post_obj_id One of the optional "posts", or null.
     * @param int $leaf_user_obj_id One of the optional "users", or null.
     * @return boolean
     */
    public static function delete($user_id, $type, $base_post_obj_id,
            $base_user_obj_id, $leaf_post_obj_id, $leaf_user_obj_id) {
        global $db;
        
        $query = "
            DELETE obj FROM `notification_objects` AS obj
            JOIN `notifications` AS n ON n.`notification_id`=obj.`notification_id`
            WHERE n.`user_id`=".$db->real_escape_string((int)$user_id)."
            AND n.`type`='".$db->real_escape_string($type)."'
            AND n.`post_obj_id`".($base_post_obj_id ? "=".((int)$base_post_obj_id) : " IS NULL")."
            AND n.`user_obj_id`".($base_user_obj_id ? "=".((int)$base_user_obj_id) : " IS NULL")."
            AND obj.`post_obj_id`".($leaf_post_obj_id ? "=".((int)$leaf_post_obj_id) : " IS NULL")."
            AND obj.`user_obj_id`".($leaf_user_obj_id ? "=".((int)$leaf_user_obj_id) : " IS NULL");
        if ($db->query($query)) {
            return true;
        }
        return false;
    }
    
    /**
     * Adds "mention" notifications for users who are mentioned in a post with
     * an @ preceding them.
     * 
     * @param int $post_id
     * @param string $content
     * @return boolean
     */
    public static function add_mentions($post_id, $content) {
        $usernames = array();
        if (preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $usernames)) {
            $post = Post::get_by_id($post_id);
            foreach (array_unique($usernames[1]) as $username) {
                $user = User::get_by_username($username);
                if (!$user || $user->id === $post->user->id) {
                    continue;
                }
                if (!Notification::add($user->id, "mention", null, null, $post->id, null)) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Marks the given notification as read.
     * 
     * @global mysqli $db
     * @param int $notification_id
     * @return boolean
     */
    public static function set_read($notification_id) {
        global $db;
        $query = "
            UPDATE `notifications`
            SET `read`=1, `date_read`=NOW()
            WHERE `notification_id`=".$db->real_escape_string((int)$notification_id);
        if ($db->query($query)) {
            return true;
        }
        return false;
    }
    
    /**
     * Gets the Notification object with the given notification_id, if its
     * recipient is the given user_id.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $notification_id
     * @param int $user_id
     * @return Notification|null
     */
    public static function get_by_id($notification_id, $user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $query = "
            SELECT `notification_id`, `post_obj_id`, `user_obj_id`,
                `text`, `read`, `date_read`, `date_created`
            FROM `notifications`
            WHERE `notification_id`=".$db->real_escape_string((int)$notification_id)."
            AND `user_id`=".$db->real_escape_string((int)$user_id);
        $result = $db->query($query);
        
        if ($result && $result->num_rows) {
            $row = $result->fetch_assoc();
            return Notification::create($row);
        }
        return null;
    }
    
    /**
     * Gets an array of Notification objects for the current user.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $page [optional] The current page of results, defaults to 1.
     * @param int $limit [optional] The number of results per page, defaults to 15.
     * @return array|null An array of Notification objects or null on failure.
     */
    public static function get_latest($page = 1, $limit = 15) {
        global $db, $CURRENT_USER;
        if (!$CURRENT_USER) {
            return null;
        }
        
        $query = "
            SELECT n.`notification_id`, n.`type`, n.`read`,
                n.`date_read`, n.`date_updated`, n.`date_created`
            FROM `notifications` AS n
            JOIN `notification_objects` AS obj
            ON obj.`notification_id`=n.`notification_id`
            WHERE n.`user_id`=".$db->real_escape_string($CURRENT_USER->id)."
            GROUP BY obj.`notification_id`
            ORDER BY n.`date_updated` DESC
            LIMIT ".(((int)$page - 1) * (int)$limit).", ".((int)$limit);
        $results = $db->query($query);
        if ($results) {
            $notifications = array();
            while ($row = $results->fetch_assoc()) {
                $notifications[] = Notification::create($row);
            }
            return $notifications;
        }
        return null;
    }
}
