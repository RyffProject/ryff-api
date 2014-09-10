<?php

/**
 * @class DeleteCommentTest
 * ========================
 * 
 * Creates a new user, post, and comment, then deletes the comment via the API
 * and verifies after that it has been deleted.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class DeleteCommentTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Delete Comment test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["post"] = $this->env->get_test_post($this->state["user"]->id);
        $this->state["comment"] = Comment::add(
            $this->env->get_words(10),
            $this->state["post"]->id,
            $this->state["user"]->id
        );
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
            "delete-comment",
            array("id" => $this->state["comment"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete comment (API Level).\n";
            $output = false;
        } else if (!empty(Comment::get_for_post($this->state["post"]->id))) {
            echo "Failed to delete comment (Database Level).\n";
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
