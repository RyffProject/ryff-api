<?php

/**
 * @class AddCommentTest
 * =====================
 * 
 * Creates a new user and a post, and then a comment via the API, and makes
 * sure the comment exists after.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class AddCommentTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Add Comment test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["post"] = $this->env->get_test_post($this->state["user"]->id);
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
            "add-comment",
            array(
                "id" => $this->state["post"]->id,
                "content" => $this->env->get_words(10)
            )
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add comment (API Level).\n";
            $output = false;
        } else if (count(Comment::get_for_post($this->state["post"]->id)) !== 1) {
            echo "Failed to add comment (Database Level).\n";
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
