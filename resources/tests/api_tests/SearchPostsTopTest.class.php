<?php

/**
 * @class SearchPostsTopTest
 * =========================
 * 
 * Creates a user with three posts, then creates three other users to give
 * those posts 1, 2, and 3 upvotes. Then gets the posts from the API and makes
 * sure they come back in the correct order.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class SearchPostsTopTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Search Posts Top test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["users"] = array();
        $this->state["posts"] = array();
        $this->state["num_posts"] = 3;
        $this->state["tag"] = $this->env->get_word();
        
        //Create a few other users to upvote the posts
        for ($i = 0; $i < $this->state["num_posts"]; $i++) {
            $this->state["users"][] = $this->env->get_test_user();
        }
        
        //Create the posts and upvote them
        for ($i = 0; $i < $this->state["num_posts"]; $i++) {
            $post = $this->env->get_test_post(
                $this->state["user"]->id,
                array(), array($this->state["tag"])
            );
            for ($j = 0; $j < $this->state["num_posts"] - $i; $j++) {
                Upvote::add($post->id, $this->state["users"][$j]->id);
            }
            $this->state["posts"][] = $post;
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
            "search-posts-top",
            array("tags" => $this->state["tag"])
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to search top posts.\n";
            $output = false;
        } else if (count($results->posts) !== $this->state["num_posts"]) {
            echo "Failed to get the correct number of top posts.\n";
            $output = false;
        } else if ($results->posts[0]->id !== $this->state["posts"][0]->id ||
                $results->posts[1]->id !== $this->state["posts"][1]->id ||
                $results->posts[2]->id !== $this->state["posts"][2]->id) {
            echo "Failed to get the top posts in the correct order.\n";
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
