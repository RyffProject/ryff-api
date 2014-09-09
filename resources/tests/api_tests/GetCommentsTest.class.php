<?php

/**
 * @class GetCommentsTest
 * ======================
 * 
 * Creates a new user, post, and several comments, then gets the comments via
 * the API and verifies that they are in the correct order.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class GetCommentsTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Comments test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["post"] = $this->env->get_test_post($this->state["user"]->id);
        $this->state["num_comments"] = 3;
        $this->state["comments"] = array();
        for ($i = 0; $i < $this->state["num_comments"]; $i++) {
            $this->state["comments"][] = Comment::add(
                $this->env->get_words(10),
                $this->state["post"]->id,
                $this->state["user"]->id
            );
            if ($i !== $this->state["num_comments"] - 1) {
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
            "get-comments",
            array("id" => $this->state["post"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get comments.\n";
            $output = false;
        } else if (count($results->comments) !== $this->state["num_comments"]) {
            echo "Failed to get the correct number of comments.\n";
            $output = false;
        } else if ($results->comments[0]->id !== $this->state["comments"][0]->id ||
                $results->comments[1]->id !== $this->state["comments"][1]->id ||
                $results->comments[2]->id !== $this->state["comments"][2]->id) {
            echo "Failed to get the comments in the correct order.\n";
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
