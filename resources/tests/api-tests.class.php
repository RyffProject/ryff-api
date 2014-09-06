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

        $this->cookies = array();
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
        }
        User::delete($results->user->id);
        return $get_results && !property_exists($get_results, "error");
    }
    
    /**
     * Creates a new user, attempts to log them in, then checks that the login
     * worked by calling get-user on the logged in user. Then the user is deleted.
     * 
     * @return boolean
     */
    protected function login_test() {
        $user = $this->get_test_user();
        if (!$this->log_user_in($user->username)) {
            echo "Failed to log user in.\n";
            return false;
        }
        $results = $this->post_to_api("get-user", array("id" => $user->id));
        if (!$results || property_exists($results, "error")) {
            echo "Failed to get user after login.\n";
        }
        User::delete($user->id);
        return $results && !property_exists($results, "error");
    }
    
    /**
     * Creates a user, logs them in, logs them out, then tests that get-user
     * fails.
     * 
     * @return boolean
     */
    protected function logout_test() {
        $user = $this->get_test_user();
        if (!$this->log_user_in($user->username)) {
            echo "Failed to log user in.\n";
            return false;
        }
        $results = $this->post_to_api("logout");
        if (!$results || property_exists($results, "error")) {
            echo "Failed to log user out.\n";
            User::delete($user->id);
            return false;
        }
        $get_results = $this->post_to_api("get-user", array("id" => $user->id));
        if (!$get_results || property_exists($get_results, "success")) {
            echo "Error, not logged out after calling logout.\n";
        }
        User::delete($user->id);
        return $get_results && property_exists($get_results, "error");
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
            "logout_test" => "Logout test"
        );
        foreach ($tests as $test => $message) {
            if (!$this->do_test($test, $message)) {
                return false;
            }
        }
        return true;
    }
}
