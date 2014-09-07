<?php

/**
 * @class GetNotificationsTest
 * ===========================
 * 
 * Creates two users and has the second follow, upvote, mention, and remix
 * the first. Then logs in as the first and makes sure there are four
 * notifications from the API.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class GetNotificationsTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Notifications test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        
        //Follow notification
        Follow::add($this->state["user1"]->id, $this->state["user2"]->id);
        
        //Mention notification
        $this->env->get_test_post(
            $this->state["user2"]->id, array(), array(),
            array($this->state["user1"]->username)
        );
        
        //Remix notification
        $this->state["post"] = $this->env->get_test_post($this->state["user1"]->id);
        $this->env->get_test_post($this->state["user2"]->id, array($this->state["post"]->id));
        
        //Upvote notification
        Upvote::add($this->state["post"]->id, $this->state["user2"]->id);
        
        $this->state["num_notifications"] = 4;
        $this->env->log_user_in($this->state["user1"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api("get-notifications");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get notifications.\n";
            $output = false;
        } else if (count($results->notifications) !== $this->state["num_notifications"]) {
            echo "Failed to get all four types of notification.\n";
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
