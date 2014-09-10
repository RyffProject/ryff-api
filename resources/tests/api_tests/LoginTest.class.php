<?php

/**
 * @class LoginTest
 * ================
 * 
 * Creates a new user, attempts to log them in, then checks that the login
 * worked by calling get-user on the logged in user.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class LoginTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Login test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        if (!$this->env->log_user_in($this->state["user"]->username)) {
            echo "Failed to log user in.\n";
            $output = false;
        }
        $results = $this->env->post_to_api(
            "get-user",
            array("id" => $this->state["user"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get user after login.\n";
            $output = false;
        }
        return $output;
    }

    /**
     * Overrides abstract function in Test.
     */
    protected function teardown() {
        User::delete($this->state["user"]->id);
    }
}
