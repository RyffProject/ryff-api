<?php

/**
 * @class GetPostsTest
 * ===================
 * 
 * Creates two users, then has the second user create a set number of posts.
 * Logs in as the first user, and gets the second's posts via the API and
 * verifies that it is the correct number.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class GetPostsTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Posts test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        $this->state["num_posts"] = 5;
        for ($i = 0; $i < $this->state["num_posts"]; $i++) {
            $this->env->get_test_post($this->state["user2"]->id);
        }
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api(
            "get-posts",
            array("id" => $this->state["user2"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get posts.\n";
            $output = false;
        } else if (count($results->posts) !== $this->state["num_posts"]) {
            echo "Failed to get the right number of posts.\n";
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
