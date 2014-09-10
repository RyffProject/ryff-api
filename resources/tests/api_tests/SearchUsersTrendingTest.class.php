<?php

/**
 * @class SearchUsersTrendingTest
 * ==============================
 * 
 * Creates three users each with the same tag, then adds three posts to the
 * first one, two to the second, and one to the third. Since each post has one
 * upvote by default this makes the first user more trending, and the third
 * least trending. Then it gets the users via the API and verifies the order.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

class SearchUsersTrendingTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Search Users Trending test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        $this->env->log_user_in($this->state["user"]->username);
        
        $this->state["users"] = array();
        $this->state["num_users"] = 3;
        $this->state["tag"] = $this->env->get_word();
        
        //Create some users with the same tag
        for ($i = 0; $i < $this->state["num_users"]; $i++) {
            $user = $this->env->get_test_user();
            Tag::add_for_user($this->state["tag"], $user->id);
            //Create more posts (each with 1 upvote by default, so more upvotes)
            //to users with lower $i, so index 0 is first, 1 is second, 2 is third.
            for ($j = 0; $j < $this->state["num_users"] - $i; $j++) {
                $this->env->get_test_post($user->id);
            }
            $this->state["users"][] = $user;
        }
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api(
            "search-users-trending",
            array("tags" => $this->state["tag"])
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to search trending users.\n";
            $output = false;
        } else if (count($results->users) !== $this->state["num_users"]) {
            echo "Failed to get the correct number of trending users.\n";
            $output = false;
        } else if ($results->users[0]->id !== $this->state["users"][0]->id ||
                $results->users[1]->id !== $this->state["users"][1]->id ||
                $results->users[2]->id !== $this->state["users"][2]->id) {
            echo "Failed to get the trending users in the right order.\n";
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
