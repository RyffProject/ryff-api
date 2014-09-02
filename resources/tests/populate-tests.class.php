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
     * Overrides abstract method run_test() from class TestEnvironment.
     * 
     * @return boolean If the tests succeeded or not.
     */
    protected function run_tests() {
        return true;
    }
}
