<?php

/**
 * @class SetNotificationReadTest
 * ==============================
 * 
 * Creates two users and has one follow the other to create a notification.
 * Attempts to set this notification as read through the API, and then
 * verifies that it has been marked as read.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class SetNotificationReadTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Set Notification Read test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        Follow::add($this->state["user1"]->id, $this->state["user2"]->id);
        
        $notifications = Notification::get_latest(1, 1, $this->state["user1"]->id);
        $this->state["notification"] = $notifications[0];
        
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
            "set-notification-read",
            array("id" => $this->state["notification"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to set notification as read (API Level).\n";
            $output = false;
        } else {
            $new_notification = Notification::get_by_id(
                $this->state["notification"]->id,
                $this->state["user1"]->id
            );
            if (!$new_notification->is_read) {
                echo "Failed to set notification as read (Database Level).\n";
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
    }
}
