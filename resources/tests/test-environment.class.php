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
abstract class TestEnvironment {
    /**
     * An array of words so for get_word() to use.
     * 
     * @var array
     */
    private $words;
    
    /**
     * Constructs a new TestEnvironment object and initializes the words array
     * from words.txt.
     */
    public function __construct() {
        $this->words = explode("\n", file_get_contents(__DIR__."/words.txt"));
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
        $end_time = microtime(true);
        
        echo $passed ? "passed" : "failed";
        echo " (".round($end_time - $start_time, 3)." seconds).\n";
        echo $output;
        
        return $passed;
    }
    
    /**
     * Runs setup(), lock_tables(), run_tests(), unlock_tables(). If $do_setup is
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