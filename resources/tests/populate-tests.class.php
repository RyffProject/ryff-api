<?php

/**
 * @class PopulateTests
 * ====================
 * 
 * This script populates a test environment with a lot of test data. Meant
 * to be run without teardown, usually.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once("test-environment.class.php");

class PopulateTests extends TestEnvironment {
    /**
     * An array of Tag objects used for the current cycle.
     * 
     * @var array
     */
    private $tags = array();
    
    /**
     * The array of User objects created during the current run (all cycles).
     * 
     * @var array
     */
    private $users = array();
    
    /**
     * The number of populate cycles this environment should go through.
     * 
     * @var int
     */
    private $num_cycles = 1;
    
    /**
     * Sets the number of populate cycles this environment should go through.
     * 
     * @param int $num_cycles
     */
    public function set_num_cycles($num_cycles) {
        $this->num_cycles = (int)$num_cycles > 0 ? (int)$num_cycles : 1;
    }
    
    /**
     * Gets the trending tags, and adds to that until it has a set number
     * of tags that it can use as a pool for this cycle.
     * 
     * @return boolean
     */
    protected function tags_test() {
        $this->tags = Tag::get_trending("all");
        while (count($this->tags) < 15) {
            $tag = $this->get_word();
            $this->tags[] = Tag::get_by_tag($tag);
        }
        return true;
    }
    
    /**
     * Create some users, and give each of them some tags from the pool.
     * 
     * @return boolean
     */
    protected function users_test() {
        for ($i = 0; $i < 20; $i++) {
            $user = $this->get_test_user();
            $tags = array_rand($this->tags, mt_rand(2, 4));
            foreach ($tags as $t) {
                Tag::add_for_user($this->tags[$t]->tag, $user->id);
            }
            $this->users[] = $user;
        }
        return true;
    }
    
    /**
     * Make each user follow some other users, and then unfollow a smaller
     * number of users.
     * 
     * @return boolean
     */
    protected function follows_test() {
        if (count($this->users) < 5) {
            echo "Not enough users to follow.\n";
            return false;
        }
        
        foreach ($this->users as $user) {
            $n = mt_rand(2, 5);
            for ($i = 0; $i < $n; $i++) {
                do {
                    $other_user = $this->users[array_rand($this->users)];
                } while ($other_user == $user);
                if (!Follow::add($other_user->id, $user->id)) {
                    echo "Failed to add follow.\n";
                    return false;
                }
            }
            
            $m = mt_rand(0, 2);
            for ($i = 0; $i < $m; $i++) {
                do {
                    $other_user = $this->users[array_rand($this->users)];
                } while ($other_user == $user);
                if (!Follow::delete($other_user->id, $user->id)) {
                    echo "Failed to delete follow.\n";
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Overrides abstract method run_test() from class TestEnvironment.
     * 
     * @return boolean If the tests succeeded or not.
     */
    protected function run_tests() {
        $tests = array(
            "tags_test" => "Get tags test",
            "users_test" => "Add users test",
            "follows_test" => "Follow users test"
        );
        echo "\n";
        for ($i = 0; $i < $this->num_cycles; $i++) {
            echo "Beginning populate cycle ".($i + 1)." of ".$this->num_cycles.".\n";
            foreach ($tests as $test => $message) {
                if (!$this->do_test($test, $message)) {
                    return false;
                }
            }
            echo "Finished populate cycle ".($i + 1)." of ".$this->num_cycles.".\n\n";
        }
        return true;
    }
}
