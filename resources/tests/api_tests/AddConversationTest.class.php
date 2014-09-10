<?php

/**
 * @class AddConversationTest
 * ==========================
 * 
 * Creates two users, logs one of them in, ands adds a conversation with the
 * other's id. Then tries to add a conversation with only itself as an id,
 * which should fail. Deletes the users and conversation on exit.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class AddConversationTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Add Conversation test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        $this->env->log_user_in($this->state["user1"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api(
            "add-conversation",
            array("ids" => $this->state["user2"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add conversation (API Level).\n";
            $output = false;
        } else {
            $this->state["conversation"] = $results->conversation;
            if (!Conversation::get_by_id($results->conversation->id, $this->state["user1"]->id)) {
                echo "Failed to add conversation (Database Level).\n";
                $output = false;
            }
        }
        $fail_results = $this->env->post_to_api("add-conversation", array("ids" => ""));
        if (!$fail_results) {
            $output = false;
        } else if (!property_exists($fail_results, "error")) {
            echo "Failed to detect conversation with not enough ids.\n";
            $output = false;
        }
        return $output;
    }

    /**
     * Overrides abstract function in Test.
     */
    protected function teardown() {
        if (isset($this->state["conversation"])) {
            Conversation::delete($this->state["conversation"]->id);
        }
        User::delete($this->state["user1"]->id);
        User::delete($this->state["user2"]->id);
    }
}
