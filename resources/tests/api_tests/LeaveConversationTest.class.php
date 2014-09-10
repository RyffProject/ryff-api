<?php

/**
 * @class LeaveConversationTest
 * ============================
 * 
 * Creates three users in a conversation, logs one in, and has them leave
 * the conversation via the API. Then makes sure they are actually gone.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class LeaveConversationTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Leave Conversation test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        $this->state["user3"] = $this->env->get_test_user();
        $this->state["conversation"] = Conversation::add(array(
            $this->state["user1"]->id,
            $this->state["user2"]->id,
            $this->state["user3"]->id
        ));
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
            "leave-conversation",
            array("id" => $this->state["conversation"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to leave conversation (API Level).\n";
            $output = false;
        } else {
            $conversation = Conversation::get_by_id(
                $this->state["conversation"]->id,
                $this->state["user1"]->id
            );
            if ($conversation) {
                echo "Failed to leave conversation (Database Level).\n";
                $output = false;
            }
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
        Conversation::delete($this->state["conversation"]->id);
    }
}
