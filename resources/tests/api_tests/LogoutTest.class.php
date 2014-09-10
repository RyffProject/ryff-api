<?php

/**
 * @class LogoutTest
 * =================
 * 
 * Creates a user, logs them in, logs them out, then tests that get-user
 * fails.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class LogoutTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Logout test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->env->log_user_in($this->state["user"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api("logout");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to log user out.\n";
            $output = false;
        } else {
            $get_results = $this->env->post_to_api(
                "get-user",
                array("id" => $this->state["user"]->id)
            );
            if (!$get_results) {
                $output = false;
            } else if (property_exists($get_results, "success")) {
                echo "Error, not logged out after calling logout.\n";
                $output = false;
            }
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
