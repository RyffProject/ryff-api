<?php

/**
 * @class GetTagsTrendingTest
 * ==========================
 * 
 * Creates a user and three tags, with each tag having successively more
 * posts. Then checks that the trending tags are returned in the right order.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class GetTagsTrendingTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Tags Trending test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["tag1"] = $this->env->get_unique_word();
        for ($i = 0; $i < 5; $i++) {
            $this->env->get_test_post(
                $this->state["user"]->id,
                array(), array($this->state["tag1"])
            );
        }
        $this->state["tag2"] = $this->env->get_unique_word();
        for ($i = 0; $i < 4; $i++) {
            $this->env->get_test_post(
                $this->state["user"]->id,
                array(), array($this->state["tag2"])
            );
        }
        $this->state["tag3"] = $this->env->get_unique_word();
        for ($i = 0; $i < 3; $i++) {
            $this->env->get_test_post(
                $this->state["user"]->id,
                array(), array($this->state["tag3"])
            );
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
        $results = $this->env->post_to_api("get-tags-trending");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get trending tags.\n";
            $output = false;
        } else if ($results->tags[0]->tag !== $this->state["tag1"] ||
                $results->tags[1]->tag !== $this->state["tag2"] ||
                $results->tags[2]->tag !== $this->state["tag3"]) {
            echo "Failed to get the trending tags in the right order.\n";
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
