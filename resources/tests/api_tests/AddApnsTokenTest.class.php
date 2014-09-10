<?php

/**
 * @class AddApnsTokenTest
 * =======================
 * 
 * Creates a user, logs them in, and calls add-apns-token. Then it verifies
 * that the token has been set in the database.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class AddApnsTokenTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Add APNs Token test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->env->log_user_in($this->state["user"]->username);
        $this->state["fields"] = array(
            "token" => str_repeat("0", 64),
            "uuid" => str_repeat("0", 36)
        );
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api("add-apns-token", $this->state["fields"]);
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to add APNs token (API level).\n";
            $output = false;
        } else if (empty(PushNotification::get_apns_tokens($this->state["user"]->id))) {
            echo "Failed to add APNs token (Database level).\n";
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
