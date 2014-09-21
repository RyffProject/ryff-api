<?php

/**
 * @class CreateUserTest
 * =====================
 * 
 * Creates some fake credentials and creates a new user, with an avatar
 * image if one is available. Then checks to make sure the user is logged
 * in after creation.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class CreateUserTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Create User test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["fields"] = array(
            "username" => $this->env->get_unique_word(),
            "password" => "password",
            "name" => $this->env->get_words(2),
            "email" => $this->env->get_unique_word()."@example.com",
            "bio" => $this->env->get_words(10),
            "latitude" => 50,
            "longitude" => 50
        );
        $this->state["files"] = array();
        if (!empty($this->env->sample_avatars)) {
            $this->state["files"]["avatar"] = array(
                "path" => $this->env->sample_avatars[0]
            );
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
            "create-user",
            $this->state["fields"],
            $this->state["files"]
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to create user.\n";
            $output = false;
        } else {
            $this->state["user"] = $results->user;
            $get_results = $this->env->post_to_api(
                "get-user",
                array("id" => $this->state["user"]->id)
            );
            if (!$get_results) {
                $output = false;
            } else if (property_exists($get_results, "error")) {
                echo "{$results->error}\n";
                echo "Failed to get user after creation.\n";
                $output = false;
            }
        }
        return $output;
    }

    /**
     * Overrides abstract function in Test.
     */
    protected function teardown() {
        if (isset($this->state["user"])) {
            User::delete($this->state["user"]->id);
        }
    }
}
