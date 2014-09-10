<?php

/**
 * @class DeletePostTest
 * =====================
 * 
 * Creates a user and a post, then tries to delete the post via the API.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class DeletePostTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Delete Post test";
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
            "delete-post",
            array("id" => $this->state["post"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to delete post (API Level).\n";
            $output = false;
        } else if (Post::get_by_id($this->state["post"]->id)) {
            echo "Failed to delete post (Database Level).\n";
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
