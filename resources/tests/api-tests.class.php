<?php

/**
 * @class ApiTests
 * ===============
 * 
 * Implements unit tests for API scripts in /public_html/api.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once("test-environment.class.php");

class ApiTests extends TestEnvironment {
    /**
     * An array of cookies sent with the post_to_api curl requests. Used to
     * keep users logged in.
     * 
     * @var array
     */
    private $cookies = array();
    
    /**
     * Sends a POST request to the API with the given field names and files.
     * Returns the decoded JSON object or false on failure. Also echoes an error
     * message on failure.
     * 
     * @param string $script_name The name of the API script without the file extension.
     * @param array $fields [optional] An associative $key => $value array.
     * @param array $files [optional] An associative $key => $filepath array.
     * @return mixed The decoded JSON response or false on failure.
     */
    private function post_to_api($script_name, $fields = array(), $files = array()) {
        $ch = curl_init();

        foreach ($files as $key => $path) {
            if (!file_exists($path)) {
                continue;
            }
            $fields[$key] = "@".$path.";filename=".basename($path);
        }
        curl_setopt($ch, CURLOPT_URL, SITE_ROOT."/api/$script_name.php");
        curl_setopt($ch, CURLOPT_COOKIE, implode("; ", $this->cookies));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $data = curl_exec($ch);
        if ($data === false) {
            echo "Fatal Error: Unable to complete HTTP request.\n";
            return false;
        }
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($data, 0, $header_size);
        $output = substr($data, $header_size);
        
        curl_close($ch);

        $temp_cookies = array();
        preg_match_all("/^Set-cookie: (.*?);/ism", $header, $temp_cookies);
        foreach( $temp_cookies[1] as $cookie ){
            $key = substr($cookie, 0, strpos($cookie, "="));
            $this->cookies[$key] = $cookie;
        }
        
        $obj = json_decode($output);
        if ($obj === null) {
            echo "Fatal Error: Invalid JSON: $output\n";
            return false;
        } else {
            return $obj;
        }
    }
    
    /**
     * Attempts to log in with the given username using the post_to_api method.
     * Returns true if the request succeeded, or false on failure.
     * 
     * @param string $username
     * @return boolean
     */
    private function log_user_in($username) {
        $fields = array("auth_username" => $username, "auth_password" => "password");
        $results = $this->post_to_api("login", $fields);
        return $results && property_exists($results, "success");
    }
    
    /**
     * Creates some fake credentials and creates a new user, with an avatar
     * image if one is available. Then checks to make sure the user is logged
     * in after creation.
     * 
     * @return boolean
     */
    protected function create_user_test() {
        $output = true;
        $fields = array(
            "username" => $this->get_unique_word(),
            "password" => "password",
            "name" => $this->get_words(2),
            "email" => $this->get_unique_word()."@example.com",
            "bio" => $this->get_words(10),
            "latitude" => 50,
            "longitude" => 50
        );
        $files = array();
        if (!empty($this->sample_avatars)) {
            $files["avatar"] = $this->sample_avatars[0];
        }
        $results = $this->post_to_api("create-user", $fields, $files);
        if (!$results || property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to create user.\n";
            return false;
        }
        $get_results = $this->post_to_api("get-user", array("id" => $results->user->id));
        if (!$get_results || property_exists($get_results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get user after creation.\n";
            $output = false;
        }
        User::delete($results->user->id);
        return $output;
    }
    
    /**
     * Creates a new user, attempts to log them in, then checks that the login
     * worked by calling get-user on the logged in user. Then the user is deleted.
     * 
     * @return boolean
     */
    protected function login_test() {
        $user = $this->get_test_user();
        $output = true;
        if (!$this->log_user_in($user->username)) {
            echo "Failed to log user in.\n";
            $output = false;
        }
        $results = $this->post_to_api("get-user", array("id" => $user->id));
        if (!$results || property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get user after login.\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates a user, logs them in, logs them out, then tests that get-user
     * fails.
     * 
     * @return boolean
     */
    protected function logout_test() {
        $user = $this->get_test_user();
        $output = true;
        $this->log_user_in($user->username);
        $results = $this->post_to_api("logout");
        if (!$results || property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to log user out.\n";
            $output = false;
        }
        $get_results = $this->post_to_api("get-user", array("id" => $user->id));
        if (!$get_results || property_exists($get_results, "success")) {
            echo "Error, not logged out after calling logout.\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates a user, logs them in, and calls add-apns-token. Then it verifies
     * that the token has been set in the database. Deletes the user on exit.
     * 
     * @return boolean
     */
    protected function add_apns_token_test() {
        $output = true;
        $user = $this->get_test_user();
        $this->log_user_in($user->username);
        $fields = array(
            "token" => str_repeat("0", 64),
            "uuid" => str_repeat("0", 36)
        );
        $results = $this->post_to_api("add-apns-token", $fields);
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add APNs token (API level).\n";
            $output = false;
        } else {
            $tokens = PushNotification::get_apns_tokens($user->id);
            if (empty($tokens)) {
                echo "Failed to add APNs token (Database level).\n";
                $output = false;
            }
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates two users, logs one of them in, ands adds a conversation with the
     * other's id. Then tries to add a conversation with only itself as an id,
     * which should fail. Deletes the users and conversation on exit.
     * 
     * @return boolean
     */
    protected function add_conversation_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("add-conversation", array("ids" => $user2->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add conversation (API Level).\n";
            $output = false;
        } else {
            if (!Conversation::get_by_id($results->conversation->id, $user1->id)) {
                echo "Failed to add conversation (Database Level).\n";
                $output = false;
            }
            Conversation::delete($results->conversation->id);
        }
        $fail_results = $this->post_to_api("add-conversation", array("ids" => ""));
        if (!$fail_results) {
            $output = false;
        } else if (!property_exists($fail_results, "error")) {
            echo "Failed to detect conversation with not enough ids.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Creates two users and has one try to follow the other. Deletes the users
     * on exit.
     * 
     * @return boolean
     */
    protected function add_follow_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("add-follow", array("id" => $user2->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add follow (API Level).\n";
            $output = false;
        } else if (Follow::get_followers(1, 1, $user2->id) != array(User::get_by_id($user1->id))) {
            echo "Failed to add follow (Database Level).\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Creates a user. Adds as many fields as possible, depending on the
     * availability of sample post images and riff audio. Then adds a post via
     * the API. Deletes the user on exit.
     * 
     * @return boolean
     */
    protected function add_post_test() {
        $output = true;
        $user = $this->get_test_user();
        $parent_post = $this->get_test_post($user->id);
        $this->log_user_in($user->username);
        $fields = array(
            "content" => $this->get_words(10),
            "parent_ids" => $parent_post->id
        );
        if (!empty($this->sample_post_images)) {
            $fields["image"] = $this->sample_post_images[0];
        }
        if (!empty($this->sample_riffs)) {
            $fields["riff"] = $this->sample_riffs[0];
            $fields["title"] = $this->get_words(2);
            $fields["duration"] = 150;
        }
        $results = $this->post_to_api("add-post", $fields);
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add post (API Level).\n";
            $output = false;
        } else if (!Post::get_by_id($results->post->id)) {
            echo "Failed to add post (Database Level).\n";
            $output = false;
        } else if (!$results->post->is_upvoted) {
            echo "The new post is not upvoted by the logged in user.\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates a user and a post, then has the user star that post. Deletes the
     * user on exit.
     * 
     * @return boolean
     */
    protected function add_star_test() {
        $output = true;
        $user = $this->get_test_user();
        $post = $this->get_test_post($user->id);
        $this->log_user_in($user->username);
        $results = $this->post_to_api("add-star", array("id" => $post->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add star (API Level).\n";
            $output = false;
        } else if (Star::get_starred_posts($user->id) != array($post)) {
            echo "Failed to add star (Database Level).\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates a user and a post, then uses the model to remove the user's
     * upvote from their own post. Then adds the upvote back via the API.
     * Deletes the user on exit.
     * 
     * @return boolean
     */
    protected function add_upvote_test() {
        $output = true;
        $user = $this->get_test_user();
        $post = $this->get_test_post($user->id);
        Upvote::delete($post->id, $user->id);
        $this->log_user_in($user->username);
        $results = $this->post_to_api("add-upvote", array("id" => $post->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add upvote (API Level).\n";
            $output = false;
        } else if (Post::get_by_id($post->id)->upvotes !== 1) {
            echo "Failed to add upvote (Database Level).\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates two users and has one follow the other, then tries to delete
     * that follow via the API.
     * 
     * @return boolean
     */
    protected function delete_follow_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        Follow::add($user1->id, $user2->id);
        $this->log_user_in($user2->username);
        $results = $this->post_to_api("delete-follow", array("id" => $user1->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete follow (API Level).\n";
            $output = false;
        } else if (!empty(Follow::get_following(1, 1, $user2->id))) {
            echo "Failed to delete follow (Database Level).\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Creates a user and a post, then tries to delete the post via the API.
     * 
     * @return boolean
     */
    protected function delete_post_test() {
        $output = true;
        $user = $this->get_test_user();
        $post = $this->get_test_post($user->id);
        $this->log_user_in($user->username);
        $results = $this->post_to_api("delete-post", array("id" => $post->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete post (API Level).\n";
            $output = false;
        } else if (Post::get_by_id($post->id)) {
            echo "Failed to delete post (Database Level).\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates a user and a post, stars the post, then tries to make the user
     * unstar the post via the API.
     * 
     * @return boolean
     */
    protected function delete_star_test() {
        $output = true;
        $user = $this->get_test_user();
        $post = $this->get_test_post($user->id);
        Star::add($post->id, $user->id);
        $this->log_user_in($user->username);
        $results = $this->post_to_api("delete-star", array("id" => $post->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete star (API Level).\n";
            $output = false;
        } else if (!empty(Star::get_starred_posts($user->id))) {
            echo "Failed to delete star (Database Level).\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates a user and a post, then tries to delete the default upvote
     * from the post.
     * 
     * @return boolean
     */
    protected function delete_upvote_test() {
        $output = true;
        $user = $this->get_test_user();
        $post = $this->get_test_post($user->id);
        $this->log_user_in($user->username);
        $results = $this->post_to_api("delete-upvote", array("id" => $post->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete upvote (API Level).\n";
            $output = false;
        } else if (Post::get_by_id($post->id)->upvotes !== 0) {
            echo "Failed to delete upvote (Database Level).\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates a user and then tries to delete it via the API.
     * 
     * @return boolean
     */
    protected function delete_user_test() {
        $output = true;
        $user = $this->get_test_user();
        $this->log_user_in($user->username);
        $results = $this->post_to_api("delete-user");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete user (API Level).\n";
            $output = false;
        } else if (User::get_by_id($user->id)) {
            echo "Failed to delete user (Database Level).\n";
            $output = false;
        }
        if (!$output) {
            User::delete($user->id);
        }
        return $output;
    }
    
    /**
     * Creates three users and two conversations, then logs one user in and
     * tries to get the two conversations from the API.
     * 
     * @return boolean
     */
    protected function get_conversations_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $user3 = $this->get_test_user();
        $conversation1 = Conversation::add(array($user1->id, $user2->id));
        $conversation2 = Conversation::add(array($user1->id, $user3->id));
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("get-conversations");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get conversations.\n";
            $output = false;
        } else if (count($results->conversations) !== 2) {
            echo "Failed to get the right number of conversations.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        User::delete($user3->id);
        Conversation::delete($conversation1->id);
        Conversation::delete($conversation2->id);
        return $output;
    }
    
    /**
     * Creates two users, has one follow the other, then makes sure that user
     * is in the array of followers from the API.
     * 
     * @return boolean
     */
    protected function get_followers_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        Follow::add($user1->id, $user2->id);
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("get-followers", array("id" => $user1->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get followers.\n";
            $output = false;
        } else if ($results->users[0]->id !== $user2->id) {
            echo "Failed to get the right follower.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Creates two users, has one follow the other, then makes sure the right
     * user is in the array of users following from the API.
     * 
     * @return boolean
     */
    protected function get_following_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        Follow::add($user1->id, $user2->id);
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("get-following", array("id" => $user2->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get following.\n";
            $output = false;
        } else if ($results->users[0]->id !== $user1->id) {
            echo "Failed to get the right following.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Creates a conversation between two users, and has one user send a number
     * of messages. Then logs the other user in and makes sure the right amount
     * of messages come back from the API.
     * 
     * @return boolean
     */
    protected function get_messages_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $conversation = Conversation::add(array($user1->id, $user2->id));
        $num_messages = 5;
        for ($i = 0; $i < $num_messages; $i++) {
            Message::send($this->get_words(10), $conversation->id, $user2->id);
        }
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("get-messages", array("id" => $conversation->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get messages.\n";
            $output = false;
        } else if (count($results->messages) !== $num_messages) {
            echo "Failed to get the right number of messages.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        Conversation::delete($conversation->id);
        return $output;
    }
    
    /**
     * Creates a user that follows two other users with one post each. Makes
     * sure that getting the news feed returns both these posts.
     * 
     * @return boolean
     */
    protected function get_news_feed_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $user3 = $this->get_test_user();
        $this->get_test_post($user2->id);
        $this->get_test_post($user3->id);
        Follow::add($user2->id, $user1->id);
        Follow::add($user3->id, $user1->id);
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("get-news-feed");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get news feed.\n";
            $output = false;
        } else if (count($results->posts) !== 2) {
            echo "Failed to get the right number of news feed posts.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        User::delete($user3->id);
        return $output;
    }
    
    /**
     * Creates two users and has one follow the other to create a notification.
     * Attempts to get this notification and makes sure it's the right one.
     * 
     * @return boolean
     */
    protected function get_notification_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        Follow::add($user1->id, $user2->id);
        $notifications = Notification::get_latest(1, 1, $user1->id);
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("get-notification", array("id" => $notifications[0]->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get notification.\n";
            $output = false;
        } else if ($results->notification->users[0]->id !== $user2->id) {
            echo "Failed to get the correct notification id.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Creates two users and has the second follow, upvote, mention, and remix
     * the first. Then logs in as the first and makes sure there are four
     * notifications from the API.
     * 
     * @return boolean
     */
    protected function get_notifications_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        //Follow notification
        Follow::add($user1->id, $user2->id);
        //Mention notification
        $this->get_test_post($user2->id, array(), array(), array($user1->username));
        //Remix notification
        $post = $this->get_test_post($user1->id);
        $this->get_test_post($user2->id, array($post->id));
        //Upvote notification
        Upvote::add($post->id, $user2->id);
        
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("get-notifications");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get notifications.\n";
            $output = false;
        } else if (count($results->notifications) !== 4) {
            echo "Failed to get all four types of notification.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Creates a user and makes a tree of posts so there is a post in the middle
     * with two parents and two children. Gets the family via the API and
     * verifies that both parents and children were returned.
     * 
     * @return boolean
     */
    protected function get_post_family_test() {
        $output = true;
        $user = $this->get_test_user();
        $grandmother = $this->get_test_post($user->id);
        $grandfather = $this->get_test_post($user->id);
        $post = $this->get_test_post($user->id, array($grandmother->id, $grandfather->id));
        $son = $this->get_test_post($user->id, array($post->id));
        $daughter = $this->get_test_post($user->id, array($post->id));
        $this->log_user_in($user->username);
        $results = $this->post_to_api("get-post-family", array("id" => $post->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get post family.\n";
            $output = false;
        } else if (count($results->parents) !== 2) {
            echo "Failed to get both parents.\n";
            $output = false;
        } else if (count($results->children) !== 2) {
            echo "Failed to get both children.\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates two users, then has the second user create a set number of posts.
     * Logs in as the first user, and gets the second's posts via the API and
     * verifies that it is the correct number.
     * 
     * @return boolean
     */
    protected function get_posts_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $num_posts = 5;
        for ($i = 0; $i < $num_posts; $i++) {
            $this->get_test_post($user2->id);
        }
        $this->log_user_in($user1->username);
        $results = $this->post_to_api("get-posts", array("id" => $user2->id));
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get posts.\n";
            $output = false;
        } else if (count($results->posts) !== $num_posts) {
            echo "Failed to get the right number of posts.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Creates a user, then has it create a set number of posts and star them.
     * Then gets the user's starred posts via the API and verifies that it is
     * the correct number.
     * 
     * @return boolean
     */
    protected function get_starred_posts_test() {
        $output = true;
        $user = $this->get_test_user();
        $num_stars = 5;
        for ($i = 0; $i < $num_stars; $i++) {
            $post = $this->get_test_post($user->id);
            Star::add($post->id, $user->id);
        }
        $this->log_user_in($user->username);
        $results = $this->post_to_api("get-starred-posts");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get starred posts.\n";
            $output = false;
        } else if (count($results->posts) !== $num_stars) {
            echo "Failed to get the right number of starred posts.\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates a user. This user is following two other users, who each have
     * one tag. These two other users have also each made one post, with one
     * tag. A third other user who the user does not follow has made two posts
     * each with one tag, and the user has upvoted these. All six of these tags
     * should be returned as suggestions for the user.
     * 
     * @return boolean
     */
    protected function get_tags_suggested_test() {
        $output = true;
        $user = $this->get_test_user();
        $tags = explode(" ", $this->get_words(6));
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        Tag::add_for_user($tags[0], $user1->id);
        Tag::add_for_user($tags[1], $user2->id);
        $this->get_test_post($user1->id, array(), array($tags[2]));
        $this->get_test_post($user2->id, array(), array($tags[3]));
        $user3 = $this->get_test_user();
        $post1 = $this->get_test_post($user3->id, array(), array($tags[4]));
        $post2 = $this->get_test_post($user3->id, array(), array($tags[5]));
        Follow::add($user1->id, $user->id);
        Follow::add($user2->id, $user->id);
        Upvote::add($post1->id, $user->id);
        Upvote::add($post2->id, $user->id);
        $this->log_user_in($user->username);
        $results = $this->post_to_api("get-tags-suggested");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get suggested tags.\n";
            $output = false;
        } else {
            $returned_tags = array_map(function($t) {
                return $t->tag;
            }, $results->tags);
            if (!empty(array_diff($returned_tags, $tags)) ||
                    !empty(array_diff($tags, $returned_tags))) {
                echo "Failed to get the correct tags.\n";
                echo "Returned: ".implode(", ", $returned_tags)."\n";
                echo "Expected: ".implode(", ", $tags)."\n";
                $output = false;
            }
        }
        User::delete($user->id);
        User::delete($user1->id);
        User::delete($user2->id);
        User::delete($user3->id);
        return $output;
    }
    
    /**
     * Creates a user and three tags, with each tag having successively more
     * posts. Then checks that the trending tags are returned in the right order.
     * 
     * @return boolean
     */
    protected function get_tags_trending_test() {
        $output = true;
        $user = $this->get_test_user();
        $tag1 = $this->get_unique_word();
        for ($i = 0; $i < 5; $i++) {
            $this->get_test_post($user->id, array(), array($tag1));
        }
        $tag2 = $this->get_unique_word();
        for ($i = 0; $i < 4; $i++) {
            $this->get_test_post($user->id, array(), array($tag2));
        }
        $tag3 = $this->get_unique_word();
        for ($i = 0; $i < 3; $i++) {
            $this->get_test_post($user->id, array(), array($tag3));
        }
        $this->log_user_in($user->username);
        $results = $this->post_to_api("get-tags-trending");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get trending tags.\n";
            $output = false;
        } else if ($results->tags[0]->tag !== $tag1 ||
                $results->tags[1]->tag !== $tag2 ||
                $results->tags[2]->tag !== $tag3) {
            echo "Failed to get the trending tags in the right order.\n";
            $output = false;
        }
        User::delete($user->id);
        return $output;
    }
    
    /**
     * Creates two users, and logs the first in. Then tries to get the second
     * user by both id and username.
     * 
     * @return boolean
     */
    protected function get_user_test() {
        $output = true;
        $user1 = $this->get_test_user();
        $user2 = $this->get_test_user();
        $this->log_user_in($user1->username);
        $by_id_result = $this->post_to_api("get-user", array("id" => $user2->id));
        if (!$by_id_result) {
            $output = false;
        } else if (property_exists($by_id_result, "error")) {
            echo "{$by_id_result->error}\n";
            echo "Failed to get user by id.\n";
            $output = false;
        } else if ($by_id_result->user->username !== $user2->username) {
            echo "Failed to get the right user by id.\n";
            $output = false;
        }
        $by_username_result = $this->post_to_api("get-user", array("username" =>$user2->username));
        if (!$by_username_result) {
            $output = false;
        } else if (property_exists($by_username_result, "error")) {
            echo "{$by_username_result->error}\n";
            echo "Failed to get user by username.\n";
            $output = false;
        } else if ($by_username_result->user->id !== $user2->id) {
            echo "Failed to get the right user by username.\n";
            $output = false;
        }
        User::delete($user1->id);
        User::delete($user2->id);
        return $output;
    }
    
    /**
     * Overrides abstract method run_test() from class TestEnvironment.
     * 
     * @return boolean If the tests succeeded or not.
     */
    protected function run_tests() {
        $tests = array(
            "create_user_test" => "Create user test",
            "login_test" => "Login test",
            "logout_test" => "Logout test",
            "add_apns_token_test" => "Add APNs token test",
            "add_conversation_test" => "Add conversation test",
            "add_follow_test" => "Add follow test",
            "add_post_test" => "Add post test",
            "add_star_test" => "Add star test",
            "add_upvote_test" => "Add upvote test",
            "delete_follow_test" => "Delete follow test",
            "delete_post_test" => "Delete post test",
            "delete_star_test" => "Delete star test",
            "delete_upvote_test" => "Delete upvote test",
            "delete_user_test" => "Delete user test",
            "get_conversations_test" => "Get conversations test",
            "get_followers_test" => "Get followers test",
            "get_following_test" => "Get following test",
            "get_messages_test" => "Get messages test",
            "get_news_feed_test" => "Get news feed test",
            "get_notification_test" => "Get notification test",
            "get_notifications_test" => "Get notifications test",
            "get_post_family_test" => "Get post family test",
            "get_posts_test" => "Get posts test",
            "get_starred_posts_test" => "Get starred posts test",
            "get_tags_suggested_test" => "Get tags suggested test",
            "get_tags_trending_test" => "Get tags trending test",
            "get_user_test" => "Get user test"
        );
        foreach ($tests as $test => $message) {
            if (!$this->do_test($test, $message)) {
                return false;
            }
        }
        return true;
    }
}
