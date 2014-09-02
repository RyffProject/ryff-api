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
     * Overrides abstract method run_test() from class TestEnvironment.
     * 
     * @return boolean If the tests succeeded or not.
     */
    protected function run_tests() {
        for ($i = 0; $i < $this->num_cycles; $i++) {
            //Do something for each populate cycle
        }
        return true;
    }
}
