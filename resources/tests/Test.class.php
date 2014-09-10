<?php

/**
 * @class Test [abstract]
 * ======================
 * 
 * An abstract class for implementing a unit test. The subclass would
 * implement the setup(), test(), and teardown() functions.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
abstract class Test {
    /**
     * The TestEnvironment this Test is being called from.
     * 
     * @var TestEnvironment
     */
    protected $env = null;
    
    /**
     * An internval variable used to track internal state between the
     * setup(), test(), and teardown() functions.
     * 
     * @var array
     */
    protected $state = array();
    
    /**
     * Constructs a new Test with the given TestEnvironment.
     * 
     * @param TestEnvironment $env
     */
    public function __construct(TestEnvironment $env) {
        $this->env = $env;
    }
    
    /**
     * Gets the message to print when the test runs.
     * 
     * @return string
     */
    protected abstract function get_message();
    
    /**
     * Sets up the test data.
     */
    protected abstract function setup();
    
    /**
     * Does the actual testing on the data.
     * 
     * @return boolean
     */
    protected abstract function test();
    
    /**
     * Cleans up the test data.
     */
    protected abstract function teardown();
    
    /**
     * Runs setup(), test(), and teardown(), then returns the boolean
     * results of test().
     * 
     * @return boolean
     */
    public function run() {
        echo $this->get_message()."... ";
        
        $start_time = microtime(true);
        ob_start();
        
        $this->setup();
        $passed = $this->test();
        $this->teardown();
        
        $output = ob_get_contents();
        ob_end_clean();
        $end_time = microtime(true);
        
        echo $passed ? "passed" : "failed";
        echo " (".round($end_time - $start_time, 3)." seconds).\n";
        echo $output;
        
        return $passed;
    }
}
