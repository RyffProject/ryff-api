<?php

/**
 * @class SearchUsersNearbyTest
 * ============================
 * 
 * Description.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once(__DIR__."/../test.class.php");

class SearchUsersNearbyTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Search Users Nearby test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->state["user"]->set_location(50, 50);
        $this->state["tag"] = $this->env->get_word();
        $this->state["users"] = array();
        
        $locations = array(
            new Point(45, 45),
            new Point(55, 60),
            new Point(100, 0)
        );
        foreach ($locations as $location) {
            $user = $this->env->get_test_user();
            $user->set_location($location->x, $location->y);
            Tag::add_for_user($this->state["tag"], $user->id);
            $this->state["users"][] = $user;
        }
        
        $this->env->log_user_in($this->state["user"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api(
            "search-users-nearby",
            array("tags" => $this->state["tag"])
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to search users nearby.\n";
            $output = false;
        } else if (count($results->users) !== count($this->state["users"])) {
            echo "Failed to get the correct number of nearby users.\n";
            $output = false;
        } else if ($results->users[0]->id !== $this->state["users"][0]->id ||
                $results->users[1]->id !== $this->state["users"][1]->id ||
                $results->users[2]->id !== $this->state["users"][2]->id) {
            echo "Failed to get the nearby users in the correct order.\n";
            $output = false;
        }
        return $output;
    }

    /**
     * Overrides abstract function in Test.
     */
    protected function teardown() {
        User::delete($this->state["user"]->id);
        foreach ($this->state["users"] as $user) {
            User::delete($user->id);
        }
    }
}
