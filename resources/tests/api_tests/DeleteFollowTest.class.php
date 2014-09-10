<?php

/**
 * @class DeleteFollowTest
 * =======================
 * 
 * Creates two users and has one follow the other, then tries to delete
 * that follow via the API.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class DeleteFollowTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Delete Follow test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        Follow::add($this->state["user1"]->id, $this->state["user2"]->id);
        $this->env->log_user_in($this->state["user2"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api(
            "delete-follow",
            array("id" => $this->state["user1"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete follow (API Level).\n";
            $output = false;
        } else if (!empty(Follow::get_following(1, 1, $this->state["user2"]->id))) {
            echo "Failed to delete follow (Database Level).\n";
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
