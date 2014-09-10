<?php

/**
 * @class AddFollowTest
 * ====================
 * 
 * Creates two users and has one try to follow the other.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class AddFollowTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Add Follow test";
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
        $results = $this->env->post_to_api(
            "add-follow",
            array("id" => $this->state["user2"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add follow (API Level).\n";
            $output = false;
        } else if (Follow::get_followers(1, 1, $this->state["user2"]->id) !=
                array(User::get_by_id($this->state["user1"]->id))) {
            echo "Failed to add follow (Database Level).\n";
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
