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
     * Sends a POST request to the API with the given field names. Returns the
     * decoded JSON object or false on failure. Also echoes an error message
     * on failure.
     * 
     * @param string $script_name The name of the API script without the file extension.
     * @param array $fields An associative key/value array.
     * @return mixed The decoded JSON response or false on failure.
     */
    private function post_to_api($script_name, $fields = array()) {
        $ch = curl_init();

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
            if (isset($obj->error)) {
                echo "{$obj->error}\n";
            }
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
        return (bool)$results;
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
            return false;
        }
        $result = (bool)$this->post_to_api("get-user", array("id" => $user->id));
        if (!$result) {
            echo "Error: unable to get user after login.\n";
        }
        User::delete($user->id);
        return $result;
    }
    
    /**
     * Overrides abstract method run_test() from class TestEnvironment.
     * 
     * @return boolean If the tests succeeded or not.
     */
    protected function run_tests() {
        $tests = array(
            "login_test" => "Login test"
        );
        foreach ($tests as $test => $message) {
            if (!$this->do_test($test, $message)) {
                return false;
            }
        }
        return true;
    }
}
