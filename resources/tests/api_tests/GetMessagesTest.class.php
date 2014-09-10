<?php

/**
 * @class GetMessagesTest
 * ======================
 * 
 * Creates a conversation between two users, and has one user send a number
 * of messages. Then logs the other user in and makes sure the right amount
 * of messages come back from the API.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class GetMessagesTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Messages test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        $this->state["conversation"] = Conversation::add(array(
            $this->state["user1"]->id,
            $this->state["user2"]->id)
        );
        $this->state["num_messages"] = 5;
        for ($i = 0; $i < $this->state["num_messages"]; $i++) {
            Message::send(
                $this->env->get_words(10),
                $this->state["conversation"]->id,
                $this->state["user2"]->id
            );
        }
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
            "get-messages",
            array("id" => $this->state["conversation"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get messages.\n";
            $output = false;
        } else if (count($results->messages) !== $this->state["num_messages"]) {
            echo "Failed to get the right number of messages.\n";
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
        Conversation::delete($this->state["conversation"]->id);
    }
}
