<?php

/**
 * @class TestEnvironment [abstract]
 * =================================
 * 
 * An abstract class for creating a test environment. Subclasses should implement
 * the run_tests() function. Scripts that use implementations of this class
 * should create an instance and call its run() method.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

ini_set('memory_limit','1024M');

abstract class TestEnvironment {
    /**
     * An array of words so for get_word() to use.
     * 
     * @var array
     */
    private $words = array();
    
    /**
     * An array of unique words that haven't been used get. Used in
     * get_unique_word().
     * 
     * @var array
     */
    private $unique_words = array();
    
    /**
     * An array of unique words that have already been used.
     * 
     * @var array
     */
    private $used_words = array();
    
    /**
     * Constructs a new TestEnvironment object and initializes the words array
     * from words.txt.
     */
    public function __construct() {
        $raw_words = explode("\n", file_get_contents(__DIR__."/words.txt"));
        $this->words = array_values(array_unique(array_map(function($word) {
            return preg_replace('/[^a-z]/', '', strtolower($word));
        }, $raw_words)));
        $this->unique_words = $this->words;
        shuffle($this->unique_words);
    }
    /**
     * Adds and returns a random new user, or null on failure.
     * 
     * @return User|null
     */
    protected function get_test_user() {
        return User::add(
            $this->get_words(2),
            $this->get_unique_word(),
            $this->get_unique_word()."@example.com",
            $this->get_words(10),
            $this->get_word(),
            ""
        );
    }
    
    /**
     * Adds and returns a random new post for the given users, with the
     * given parent_ids optionally as parent posts. Optionally with custom tags.
     * 
     * @param int $user_id
     * @param array $parent_ids [optional]
     * @param array $tags [optional]
     * @return Post|null
     */
    protected function get_test_post($user_id, $parent_ids = array(),
            $tags = array(), $mentions = array()) {
        $content = "";
        if (is_array($mentions)) {
            foreach ($mentions as $username) {
                $content .= "@$username ";
            }
        }
        $content .= $this->get_words(10);
        if (is_array($tags)) {
            foreach ($tags as $tag) {
                $content .= " #$tag";
            }
        }
        return Post::add(
            $content,
            $parent_ids,
            "",
            "",
            0,
            "",
            $user_id
        );
    }
    
    /**
     * Returns a random word, useful for creating fake data in test environments.
     * 
     * @return string
     */
    protected function get_word() {
        if (count($this->words)) {
            return $this->words[rand(0, count($this->words) - 1)];
        } else {
            return "";
        }
    }
    
    /**
     * Returns a random word guaranteed to be unique for this instance of
     * TestEnvironment.
     * 
     * @return string
     */
    protected function get_unique_word() {
        $word = array_pop($this->unique_words);
        if ($word) {
            $this->used_words[] = $word;
            return $word;
        }
        do {
            $rand_word = substr(md5(rand()), 0, rand(4, 12));
        } while (!in_array($rand_word, $this->used_words));
        $this->used_words[] = $rand_word;
        return $rand_word;
    }
    
    /**
     * Returns a space-separated string of random words.
     * 
     * @param int $num_words The number of words.
     * @return string
     */
    protected function get_words($num_words) {
        $words = array();
        while ($num_words > 0) {
            $words[] = $this->get_word();
            $num_words--;
        }
        return implode(' ', $words);
    }
    
    /**
     * Installs the test database.
     * 
     * @global PDO $dbh
     * @return boolean If the setup was successful.
     */
    protected function setup() {
        global $dbh;
        
        if (!$this->teardown()) {
            echo "Error tearing down the old database.\n";
            return false;
        }
        $install_query = file_get_contents(__DIR__."/../db/install.sql");
        if ($dbh->exec($install_query) === false) {
            echo "Error installing the test database. Database said:\n";
            print_r($dbh->errorInfo());
            return false;
        }
        return true;
    }
    
    /**
     * Uninstalls the test database.
     * 
     * @global PDO $dbh
     * @return boolean If the teardown was successful.
     */
    protected function teardown() {
        global $dbh;
        
        $uninstall_query = file_get_contents(__DIR__."/../db/uninstall.sql");
        if ($dbh->exec($uninstall_query) === false) {
            echo "Error uninstalling the test database. Database said:\n";
            print_r($dbh->errorInfo());
            return false;
        }
        return true;
    }
    
    /**
     * Runs the tests in between setup() and teardown(). Implemented by subclass.
     * 
     * @return boolean If the tests were successful.
     */
    protected abstract function run_tests();
    
    /**
     * Tests a member function, times it, and outputs the results.
     * 
     * @param string $test The function name to be tested.
     * @param string $message_str The message text to be shown.
     * @return boolean If the test passed or failed.
     */
    protected function do_test($test, $message_str) {
        echo $message_str."... ";
        
        $start_time = microtime(true);
        ob_start();
        $passed = $this->$test();
        $output = ob_get_contents();
        ob_end_clean();
        $end_time = microtime(true);
        
        echo $passed ? "passed" : "failed";
        echo " (".round($end_time - $start_time, 3)." seconds).\n";
        echo $output;
        
        return $passed;
    }
    
    /**
     * Runs setup(), run_tests(), and teardown(). If $do_setup is
     * false, the tables are assumed to be setup already and this step will be
     * skipped. If $do_teardown is false, the data will not be deleted after
     * running the tests. This function is the only outward-facing API.
     * 
     * @param boolean $do_setup [optional]
     * @param boolean $do_teardown [optional]
     * @return void
     */
    public function run($do_setup = true, $do_teardown = true) {
        if ($do_setup) {
            if (!$this->do_test("setup", "Installing test database")) {
                return false;
            }
        }
        
        $passed = $this->run_tests();
        
        if ($do_teardown) {
            if (!$this->do_test("teardown", "Uninstalling test database")) {
                $passed = false;
            }
        }
        
        return $passed;
    }
}