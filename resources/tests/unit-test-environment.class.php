<?php

/**
 * @class UnitTestEnvironment
 * ==========================
 * 
 * Implements unit tests for basic actions that models can take. Will test
 * user creation, following, post creation, upvoting, tagging, messaging, etc.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once("test-environment.class.php");

class UnitTestEnvironment extends TestEnvironment {
    /**
     * Overrides abstract method run_test() from class TestEnvironment.
     * 
     * @return boolean If the tests succeeded or not.
     */
    protected function run_tests() {
        return true;
    }
}
