<?php

/**
 * @class UpdateUserTest
 * =====================
 * 
 * Creates a user and updates their name, username, bio, avatar, tags, etc
 * via the update-user API script. Then it verifies that the changes were
 * in fact made.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class UpdateUserTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Update User test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        $this->state["user"] = $this->env->get_test_user();
        MediaFiles::delete_user_image($this->state["user"]->id);
        $this->state["fields"] = array(
            "name" => ucwords($this->env->get_words(2)),
            "username" => $this->env->get_unique_word(),
            "email" => $this->env->get_unique_word()."@example.com",
            "bio" => $this->env->get_words(10),
            "password" => "pickles",
            "latitude" => 100,
            "longitude" => 100,
            "tags" => str_replace(" ", ",", $this->env->get_words(3))
        );
        $this->state["files"] = array();
        if (!empty($this->env->sample_avatars)) {
            $this->state["files"]["avatar"] = array(
                "path" => $this->env->sample_avatars[0],
                "type" => "image/png"
            );
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
            "update-user",
            $this->state["fields"],
            $this->state["files"]
        );
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to update user.\n";
            $output = false;
        } else {
            $updated_user = User::get_by_id($this->state["user"]->id);
            
            if ($updated_user->name !== $this->state["fields"]["name"]) {
                echo "Failed to update user's name.\n";
                $output = false;
            }
            if ($updated_user->username !== $this->state["fields"]["username"]) {
                echo "Failed to update user's username.\n";
                $output = false;
            }
            if ($updated_user->email !== $this->state["fields"]["email"]) {
                echo "Failed to update user's email.\n";
                $output = false;
            }
            if ($this->state["files"] && !$updated_user->avatar) {
                echo "Failed to update user's avatar.\n";
                $output = false;
            }
            
            $returned_tags = array_map(function($t) {
                return $t->tag;
            }, $updated_user->tags);
            $original_tags = explode(",", $this->state["fields"]["tags"]);
            if (!empty(array_diff($returned_tags, $original_tags)) ||
                    !empty(array_diff($original_tags, $returned_tags))) {
                echo "Failed to update user's tags.\n";
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
