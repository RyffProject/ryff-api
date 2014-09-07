<?php

/**
 * @class GetPostFamilyTest
 * ========================
 * 
 * Creates a user and makes a tree of posts so there is a post in the middle
 * with two parents and two children. Gets the family via the API and
 * verifies that both parents and children were returned.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class GetPostFamilyTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Post Family test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        
        //Create two parents
        $parent1 = $this->env->get_test_post($this->state["user"]->id);
        $parent2 = $this->env->get_test_post($this->state["user"]->id);
        $this->state["num_parents"] = 2;
        
        $this->state["post"] = $this->env->get_test_post(
            $this->state["user"]->id,
            array($parent1->id, $parent2->id)
        );
        
        //Create two children
        $this->env->get_test_post($this->state["user"]->id, array($this->state["post"]->id));
        $this->env->get_test_post($this->state["user"]->id, array($this->state["post"]->id));
        $this->state["num_children"] = 2;
        
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
            "get-post-family",
            array("id" => $this->state["post"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get post family.\n";
            $output = false;
        } else if (count($results->parents) !== $this->state["num_parents"]) {
            echo "Failed to get both parents.\n";
            $output = false;
        } else if (count($results->children) !== $this->state["num_children"]) {
            echo "Failed to get both children.\n";
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
