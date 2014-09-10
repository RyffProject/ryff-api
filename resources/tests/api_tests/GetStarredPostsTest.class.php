<?php

/**
 * @class GetStarredPostsTest
 * ==========================
 * 
 * Creates a user, then has it create a set number of posts and star them.
 * Then gets the user's starred posts via the API and verifies that it is
 * the correct number.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class GetStarredPostsTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Starred Posts test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["num_stars"] = 5;
        for ($i = 0; $i < $this->state["num_stars"]; $i++) {
            $this->state["post"] = $this->env->get_test_post($this->state["user"]->id);
            Star::add($this->state["post"]->id, $this->state["user"]->id);
        }
        $this->env->log_user_in($this->state["user"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api("get-starred-posts");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get starred posts.\n";
            $output = false;
        } else if (count($results->posts) !== $this->state["num_stars"]) {
            echo "Failed to get the right number of starred posts.\n";
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
