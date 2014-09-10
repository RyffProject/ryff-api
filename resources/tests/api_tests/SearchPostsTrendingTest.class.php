<?php

/**
 * @class SearchPostsTrendingTest
 * ==============================
 * 
 * Creates a user and three other users to upvote the posts. The first user
 * makes a post, and all three other users upvote it. Then the script waits
 * three seconds, and makes a post that only one other user upvotes. Then it
 * gets the posts via the API to make sure that they came in the correct
 * order; the second post should be first.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class SearchPostsTrendingTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Search Posts Trending test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->env->log_user_in($this->state["user"]->username);
        $this->state["users"] = array();
        $this->state["posts"] = array();
        $this->state["num_posts"] = 3;
        $this->state["tag"] = $this->env->get_word();
        
        //Create a few other users to upvote the posts
        for ($i = 0; $i < $this->state["num_posts"]; $i++) {
            $this->state["users"][] = $this->env->get_test_user();
        }
        
        //Create the posts and upvote them
        for ($i = $this->state["num_posts"] - 1; $i >= 0; $i--) {
            $post = $this->env->get_test_post(
                $this->state["user"]->id,
                array(), array($this->state["tag"])
            );
            for ($j = 0; $j < $this->state["num_posts"] - $i; $j++) {
                Upvote::add($post->id, $this->state["users"][$j]->id);
            }
            $this->state["posts"][$i] = $post;
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
            "search-posts-trending",
            array("tags" => $this->state["tag"])
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to search trending posts.\n";
            $output = false;
        } else if (count($results->posts) !== $this->state["num_posts"]) {
            echo "Failed to get the correct number of trending posts.\n";
            $output = false;
        }
        return $output;
    }

    /**
     * Overrides abstract function in Test.
     */
    protected function teardown() {
        User::delete($this->state["user"]->id);
        foreach ($this->state["users"] as $user) {
            User::delete($user->id);
        }
    }
}
