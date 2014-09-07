<?php

/**
 * @class GetUserTest
 * ==================
 * 
 * Creates two users, and logs the first in. Then tries to get the second
 * user by both id and username.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class GetUserTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get User test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        $this->env->log_user_in($this->state["user1"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        
        $by_id_result = $this->env->post_to_api(
            "get-user",
            array("id" => $this->state["user2"]->id)
        );
        if (!$by_id_result) {
            $output = false;
        } else if (property_exists($by_id_result, "error")) {
            echo "{$by_id_result->error}\n";
            echo "Failed to get user by id.\n";
            $output = false;
        } else if ($by_id_result->user->username !== $this->state["user2"]->username) {
            echo "Failed to get the right user by id.\n";
            $output = false;
        }
        
        $by_username_result = $this->env->post_to_api(
            "get-user",
            array("username" => $this->state["user2"]->username)
        );
        if (!$by_username_result) {
            $output = false;
        } else if (property_exists($by_username_result, "error")) {
            echo "{$by_username_result->error}\n";
            echo "Failed to get user by username.\n";
            $output = false;
        } else if ($by_username_result->user->id !== $this->state["user2"]->id) {
            echo "Failed to get the right user by username.\n";
            $output = false;
        }
        
        return $output;
    }

    /**
     * Overrides abstract function in Test.
     */
    protected function teardown() {
        User::delete($this->state["user1"]->id);
        User::delete($this->state["user2"]->id);
    }
}
