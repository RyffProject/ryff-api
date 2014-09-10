<?php

/**
 * @class GetTagsSuggestedTest
 * ===========================
 * 
 * Creates a user. This user is following two other users, who each have
 * one tag. These two other users have also each made one post, with one
 * tag. A third other user who the user does not follow has made two posts
 * each with one tag, and the user has upvoted these. All six of these tags
 * should be returned as suggestions for the user.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

class GetTagsSuggestedTest extends Test {
    /**
     * Overrides abstract function in Test.
     * 
     * @return string
     */
    public function get_message() {
        return "Get Tags Suggested test";
    }
    
    /**
     * Overrides abstract function in Test.
     */
    protected function setup() {
        //A user and six tags
        $this->state["user"] = $this->env->get_test_user();
        $this->state["tags"] = explode(" ", $this->env->get_words(6));
        
        //Two more users that the main user follows
        $this->state["user1"] = $this->env->get_test_user();
        $this->state["user2"] = $this->env->get_test_user();
        Follow::add($this->state["user1"]->id, $this->state["user"]->id);
        Follow::add($this->state["user2"]->id, $this->state["user"]->id);
        
        //Give them tags for the API to return
        Tag::add_for_user($this->state["tags"][0], $this->state["user1"]->id);
        Tag::add_for_user($this->state["tags"][1], $this->state["user2"]->id);
        
        //Add posts with tags for the API to return
        $this->env->get_test_post(
            $this->state["user1"]->id,
            array(), array($this->state["tags"][2])
        );
        $this->env->get_test_post(
            $this->state["user2"]->id,
            array(), array($this->state["tags"][3])
        );
        
        //Add a third user not followed, but upvote his posts. They have
        //tags for the API to return
        $this->state["user3"] = $this->env->get_test_user();
        $this->state["post1"] = $this->env->get_test_post(
            $this->state["user3"]->id,
            array(), array($this->state["tags"][4])
        );
        $this->state["post2"] = $this->env->get_test_post(
            $this->state["user3"]->id,
            array(), array($this->state["tags"][5])
        );
        Upvote::add($this->state["post1"]->id, $this->state["user"]->id);
        Upvote::add($this->state["post2"]->id, $this->state["user"]->id);
        
        //Log the main user in
        $this->env->log_user_in($this->state["user"]->username);
    }

    /**
     * Overrides abstract function in Test.
     * 
     * @return boolean
     */
    protected function test() {
        $output = true;
        $results = $this->env->post_to_api("get-tags-suggested");
        if (!$results) {
            $output = false;
        } else if (property_exists($results, "error")) {
            echo "{$results->error}\n";
            echo "Failed to get suggested tags.\n";
            $output = false;
        } else {
            $returned_tags = array_map(function($t) {
                return $t->tag;
            }, $results->tags);
            if (!empty(array_diff($returned_tags, $this->state["tags"])) ||
                    !empty(array_diff($this->state["tags"], $returned_tags))) {
                echo "Failed to get the correct tags.\n";
                echo "Returned: ".implode(", ", $returned_tags)."\n";
                echo "Expected: ".implode(", ", $this->state["tags"])."\n";
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
        User::delete($this->state["user1"]->id);
        User::delete($this->state["user2"]->id);
        User::delete($this->state["user3"]->id);
    }
}
