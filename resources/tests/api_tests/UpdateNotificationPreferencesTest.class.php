<?php

/**
 * @class UpdateNotificationPreferencesTest
 * ========================================
 * 
 * Creates a user, logs them in, updates their preferences with the API, then
 * checks the database to see if they have actually been changed.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class UpdateNotificationPreferencesTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Update Notification Preferences test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->env->log_user_in($this->state["user"]->username);
        $this->state["fields"] = array(
            "type" => "follow",
            "value" => false
        );
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api(
            "update-notification-preferences",
            $this->state["fields"]
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to update notification preferences (API Level).\n";
            $output = false;
        } else {
            $prefs = Preferences::get_notification_preferences($this->state["user"]->id);
            if (!$prefs || $prefs['follow'] || !$prefs['upvote']) {
                echo "Failed to update notification preferences (Database Level).\n";
                $output = false;
            }
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
