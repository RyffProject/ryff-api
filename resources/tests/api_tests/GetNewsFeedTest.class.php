<?php

/**
 * @class GetNewsFeedTest
 * ======================
 * 
 * Creates a user that follows two other users with one post each. Makes
 * sure that getting the news feed returns both these posts.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class GetNewsFeedTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get News Feed test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        $this->state["user3"] = $this->env->get_test_user();
        $this->env->get_test_post($this->state["user2"]->id);
        $this->env->get_test_post($this->state["user3"]->id);
        $this->state["num_posts"] = 2;
        Follow::add($this->state["user2"]->id, $this->state["user1"]->id);
        Follow::add($this->state["user3"]->id, $this->state["user1"]->id);
        $this->env->log_user_in($this->state["user1"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api("get-news-feed");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get news feed.\n";
            $output = false;
        } else if (count($results->posts) !== $this->state["num_posts"]) {
            echo "Failed to get the right number of news feed posts.\n";
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
        User::delete($this->state["user3"]->id);
    }
}
