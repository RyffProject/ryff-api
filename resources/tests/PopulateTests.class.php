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
     * The array of Post objects created during the current run (all cycles).
     * 
     * @var array
     */
    private $posts = array();
    
    /**
     * The array of Conversation objects created during the current run (all cycles).
     * 
     * @var array
     */
    private $conversations = array();
    
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
            $user = $this->get_test_user(static::chance(0.7));
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
     * Loops through each user. Users have a 30% chance of doing nothing,
     * and a 70% chance of posting. If they post, there is a 20% chance they will
     * post a significant amount (3-10 posts), and an 80% chance they will post
     * only a little (1-3 posts). There is a chance that each post comes with
     * parents, tags, and mentions.
     * 
     * @return boolean
     */
    protected function posts_test() {
        foreach ($this->users as $user) {
            if (static::chance(0.3)) {
                continue;
            }
            
            $following = Follow::get_following(1, 20, $user->id);
            if (static::chance(0.2)) {
                $num_posts = mt_rand(3, 10);
            } else {
                $num_posts = mt_rand(1, 3);
            }
            
            for ($i = 0; $i < $num_posts; $i++) {
                $parent_ids = array();
                if (static::chance(0.5) && count($this->posts) > 5) {
                    $num_parents = mt_rand(1, 3);
                    for ($j = 0; $j < $num_parents; $j++) {
                        $parent_ids[] = $this->posts[array_rand($this->posts)]->id;
                    }
                }
                $tags = array();
                if (static::chance(0.7)) {
                    $num_tags = mt_rand(1, 5);
                    for ($j = 0; $j < $num_tags; $j++) {
                        $tags[] = $this->tags[array_rand($this->tags)]->tag;
                    }
                }
                $mentions = array();
                if (static::chance(0.3)) {
                    $mentions[] = $following[array_rand($following)]->username;
                }
                $post = $this->get_test_post(
                    $user->id, array_unique($parent_ids),
                    array_unique($tags), array_unique($mentions),
                    static::chance(0.3)
                );
                if (!$post) {
                    echo "Failed to add post.\n";
                    return false;
                }
                $this->posts[] = $post;
            }
        }
        return true;
    }
    
    /**
     * For 80% of users, make 1 to 2 comments on a 1 to 3 posts. Returns false
     * with an error message if it fails to add a comment.
     * 
     * @return boolean
     */
    protected function comments_test() {
        foreach ($this->users as $user) {
            //20% chance this user doesn't comment
            if ($this->chance(0.2)) {
                continue;
            }
            
            //Comment on 1-3 posts
            $num_posts = mt_rand(1, 3);
            for ($i = 0; $i < $num_posts; $i++) {
                $post = $this->posts[array_rand($this->posts)];
                //Make 1-2 comments
                $num_comments = mt_rand(1, 2);
                for ($j = 0; $j < $num_comments; $j++) {
                    //Comments are 2-10 words in length
                    if (!Comment::add($this->get_words(mt_rand(2, 10)),
                            $post->id, $user->id)) {
                        echo "Failed to add comment.\n";
                        return false;
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * Ten posts are chosen at random to be more likely to be upvoted than
     * others. Each user gives out between 3-10 upvotes, with a 25% chance that
     * the post will be chosen from the pool of "best" posts rather than the
     * general pool.
     * 
     * @return boolean
     */
    protected function upvotes_test() {
        $best_posts = array();
        for ($i = 0; $i < 10; $i++) {
            $best_posts[] = $this->posts[array_rand($this->posts)];
        }
        
        foreach ($this->users as $user) {
            $num_upvotes = mt_rand(3, 10);
            for ($i = 0; $i < $num_upvotes; $i++) {
                if (static::chance(0.25)) {
                    $post = $best_posts[array_rand($best_posts)];
                } else {
                    $post = $this->posts[array_rand($this->posts)];
                }
                if (!Upvote::add($post->id, $user->id)) {
                    echo "Failed to add upvote.\n";
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * For each user, removes a random number of starred posts and adds back
     * between 0 and 5 starred posts.
     * 
     * @return boolean
     */
    protected function stars_test() {
        foreach ($this->users as $user) {
            $starred_posts = Star::get_starred_posts($user->id);
            
            $posts_to_remove = mt_rand(0, count($starred_posts));
            for ($i = 0; $i < $posts_to_remove; $i++) {
                if (!Star::delete($starred_posts[array_rand($starred_posts)]->id, $user->id)) {
                    echo "Failed to remove starred post.\n";
                    return false;
                }
            }
            
            $posts_to_add = mt_rand(0, 5);
            for ($i = 0; $i < $posts_to_add; $i++) {
                if (!Star::add($this->posts[array_rand($this->posts)]->id, $user->id)) {
                    echo "Failed to add starred post.\n";
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * For each user, give a 25% chance that they will create a conversation with
     * one or more of the people they follow.
     * 
     * @return boolean
     */
    protected function conversations_test() {
        foreach ($this->users as $user) {
            if (static::chance(0.75)) {
                continue;
            }
            
            $participants = array($user->id);
            $following = Follow::get_following(1, 30, $user->id);
            if (empty($following)) {
                continue;
            }
            do {
                $participants[] = $following[array_rand($following)]->id;
            } while (static::chance(0.5));
            $conversation = Conversation::add($participants);
            if (!$conversation) {
                echo "Failed to add conversation.\n";
                return false;
            }
            $this->conversations[] = $conversation;
        }
        return true;
    }
    
    /**
     * Adds some messages to some of the conversations.
     */
    protected function messages_test() {
        foreach ($this->conversations as $conversation) {
            while (static::chance(0.80)) {
                $from = $conversation->users[array_rand($conversation->users)];
                $message = $this->get_words(mt_rand(1, 10));
                if (!Message::send($message, $conversation->id, $from->id)) {
                    echo "Failed to send message.\n";
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
            "follows_test" => "Follow users test",
            "posts_test" => "Posts test",
            "comments_test" => "Comments test",
            "upvotes_test" => "Upvotes test",
            "stars_test" => "Stars test",
            "conversations_test" => "Conversations test",
            "messages_test" => "Messages test"
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
