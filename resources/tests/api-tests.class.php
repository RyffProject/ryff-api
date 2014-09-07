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
    public function post_to_api($script_name, $fields = array(), $files = array()) {
        $ch = curl_init();

        foreach ($files as $key => $file) {
            if (!file_exists($file["path"])) {;
                continue;
            }
            $field = "@".$file["path"];
            $field .= ";filename=".basename($file["path"]);
            $field .= ";type=".$file["type"];
            $fields[$key] = $field;
        }
        curl_setopt($ch, CURLOPT_URL, SITE_ROOT."/api/$script_name.php");
        curl_setopt($ch, CURLOPT_COOKIE, implode("; ", $this->cookies));
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
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
    public function log_user_in($username) {
        $fields = array("auth_username" => $username, "auth_password" => "password");
        $results = $this->post_to_api("login", $fields);
        return $results && property_exists($results, "success");
    }
    
    /**
     * Overrides abstract method run_test() from class TestEnvironment.
     * 
     * @return boolean If the tests succeeded or not.
     */
    protected function run_tests() {
        //Find all of the tests and put them into an array
        $tests = array();
        $test_class_paths = glob(__DIR__."/api_tests/*.class.php");
        foreach ($test_class_paths as $test_class_path) {
            require_once($test_class_path);
            $test_class_file = basename($test_class_path);
            $test_class_name = substr($test_class_file, 0, strlen($test_class_file) - strlen(".class.php"));
            $test = new $test_class_name($this);
            $tests[$test_class_name] = $test;
        }
        
        //Run a list of prioritized tests that have to go first
        $test_priority = array("CreateUserTest", "LoginTest", "LogoutTest");
        foreach ($test_priority as $test_class_name) {
            if (!$tests[$test_class_name]->run()) {
                return false;
            }
            unset($tests[$test_class_name]);
        }
        
        //Run the other tests
        foreach ($tests as $test) {
            if (!$test->run()) {
                return false;
            }
        }
        
        return true;
    }
}
