<?php

/**
 * @class GetConversationsTest
 * ===========================
 * 
 * Creates three users and two conversations, then logs one user in and
 * tries to get the two conversations from the API.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class GetConversationsTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Conversations test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        $this->state["user3"] = $this->env->get_test_user();
        $this->state["conversation1"] = Conversation::add(array(
            $this->state["user1"]->id,
            $this->state["user2"]->id)
        );
        $this->state["conversation2"] = Conversation::add(array(
            $this->state["user1"]->id,
            $this->state["user3"]->id)
        );
        $this->env->log_user_in($this->state["user1"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api("get-conversations");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get conversations.\n";
            $output = false;
        } else if (count($results->conversations) !== 2) {
            echo "Failed to get the right number of conversations.\n";
            $output = false;
        }
        return $output;
    }

    /**
     * Overrides abstract function in Test.
     */
    protected function teardown() {
        User::delete($this->state["user1"]->id);
        User::delete($this->state["user2"]->id);
        User::delete($this->state["user3"]->id);
        Conversation::delete($this->state["conversation1"]->id);
        Conversation::delete($this->state["conversation2"]->id);
    }
}
