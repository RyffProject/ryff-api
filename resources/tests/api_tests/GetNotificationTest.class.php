<?php

/**
 * @class GetNotificationTest
 * ==========================
 * 
 * Creates two users and has one follow the other to create a notification.
 * Attempts to get this notification and makes sure it's the right one.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class GetNotificationTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Notification test";
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
            "get-notification",
            array("id" => $this->state["notification"]->id)
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get notification.\n";
            $output = false;
        } else if ($results->notification->users[0]->id !== $this->state["user2"]->id) {
            echo "Failed to get the correct notification id.\n";
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
    }
}
