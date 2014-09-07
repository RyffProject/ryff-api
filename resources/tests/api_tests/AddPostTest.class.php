<?php

/**
 * @class AddPostTest
 * ==================
 * 
 * Creates a user. Adds as many fields as possible, depending on the
 * availability of sample post images and riff audio. Then adds a post via
 * the API.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class AddPostTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Add Post test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["parent"] = $this->env->get_test_post($this->state["user"]->id);
        $this->env->log_user_in($this->state["user"]->username);
        $this->state["fields"] = array(
            "content" => $this->env->get_words(10),
            "parent_ids" => $this->state["parent"]->id
        );
        $this->state["files"] = array();
        if (!empty($this->env->sample_post_images)) {
            $this->state["files"]["image"] = array(
                "path" => $this->env->sample_post_images[0],
                "type" => "image/png"
            );
        }
        if (!empty($this->env->sample_riffs)) {
            $this->state["files"]["riff"] = array(
                "path" => $this->env->sample_riffs[0],
                "type" => "audio/mp4"
            );
            $this->state["fields"]["title"] = $this->env->get_words(2);
            $this->state["fields"]["duration"] = 150;
        }
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api("add-post", $this->state["fields"]);
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add post (API Level).\n";
            $output = false;
        } else if (!Post::get_by_id($results->post->id)) {
            echo "Failed to add post (Database Level).\n";
            $output = false;
        } else if (!$results->post->is_upvoted) {
            echo "The new post is not upvoted by the logged in user.\n";
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
