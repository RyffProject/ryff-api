<?php

/**
 * @class SearchTagsTest
 * =====================
 * 
 * Creates some tags and attaches them to some users. Then searches for a tag
 * and makes sure it returns the results in the correct order.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class SearchTagsTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Search Tags test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        
        $tags = array(
            "rock1", "rock2", "rock2", "rock3", "rock3", "rock3",
            "jazz4", "jazz4", "jazz4", "jazz4"
        );
        $this->state["users"] = array();
        foreach ($tags as $tag) {
            $user = $this->env->get_test_user();
            Tag::add_for_user($tag, $user->id);
            $this->state["users"][] = $user;
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
            "search-tags",
            array("query" => "rock")
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to search tags.\n";
            $output = false;
        } else if (count($results->tags) !== 3) {
            echo "Failed to get the correct number of tags.\n";
            $output = false;
        } else if ($results->tags[0]->tag !== "rock3" ||
                $results->tags[1]->tag !== "rock2" ||
                $results->tags[2]->tag !== "rock1") {
            echo "Failed to get the tags in the correct order.\n";
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
