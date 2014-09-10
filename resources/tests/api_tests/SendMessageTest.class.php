<?php

/**
 * @class SendMessageTest
 * ======================
 * 
 * Creates two users and a conversation between them, then has one of the
 * users send a message via the API.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class SendMessageTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Send Message test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        $this->state["conversation"] = Conversation::add(array(
            $this->state["user1"]->id,
            $this->state["user2"]->id
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
            "send-message",
            array(
                "id" => $this->state["conversation"]->id,
                "content" => $this->env->get_words(10)
            )
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to send message (API Level).\n";
            $output = false;
        } else {
            $messages = Message::get_for_conversation(
                $this->state["conversation"]->id,
                1, 1, false, $this->state["user2"]->id
            );
            if (empty($messages)) {
                echo "Failed to send message (Database Level).\n";
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
        Conversation::delete($this->state["conversation"]->id);
    }
}
