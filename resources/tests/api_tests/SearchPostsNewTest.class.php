<?php

/**
 * @class SearchPostsNewTest
 * =========================
 * 
 * Creates a user and three new posts, each with the same tag. Then searches
 * for new posts with that tag, and verifies that they show up in the correct
 * order.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class SearchPostsNewTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Search Posts New test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["num_posts"] = 3;
        $this->state["posts"] = array();
        $this->state["tag"] = $this->env->get_word();
        for ($i = 0; $i < $this->state["num_posts"]; $i++) {
            $this->state["posts"][] = $this->env->get_test_post(
                $this->state["user"]->id,
                array(), array($this->state["tag"])
            );
            if ($i !== $this->state["num_posts"] - 1) {
                sleep(1);
            }
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
        $results = $this->env->post_to_api(
            "search-posts-new",
            array("tags" => $this->state["tag"])
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to search new posts.\n";
            $output = false;
        } else if (count($results->posts) !== $this->state["num_posts"]) {
            echo "Failed to get the correct number of posts.\n";
            $output = false;
        } else if ($results->posts[0]->id !== $this->state["posts"][2]->id ||
                $results->posts[1]->id !== $this->state["posts"][1]->id ||
                $results->posts[2]->id !== $this->state["posts"][0]->id) {
            echo "Failed to get the posts in the correct order.\n";
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
