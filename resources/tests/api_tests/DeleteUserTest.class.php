<?php

/**
 * @class DeleteUserTest
 * =====================
 * 
 * Creates a user and then tries to delete it via the API.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class DeleteUserTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Delete User test";
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
        $results = $this->env->post_to_api("delete-user");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete user (API Level).\n";
            $output = false;
        } else if (User::get_by_id($this->state["user"]->id)) {
            echo "Failed to delete user (Database Level).\n";
            $output = false;
        }
        if ($output) {
            unset($this->state["user"]);
        }
        return $output;
    }

    /**
     * Overrides abstract function in Test.
     */
    protected function teardown() {
        if (isset($this->state["user"])) {
            User::delete($this->state["user"]);
        }
    }
}
