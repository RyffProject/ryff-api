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
            echo "Failed to create user.\n";
            return false;
        }
        $get_results = $this->post_to_api("get-user", array("id" => $results->user->id));
        if (!$get_results || property_exists($get_results, "error")) {
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
        if (!$fail_results || !property_exists($fail_results, "error")) {
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
            "delete_user_test" => "Delete user test"
        );
        foreach ($tests as $test => $message) {
            if (!$this->do_test($test, $message)) {
                return false;
            }
        }
        return true;
    }
}
