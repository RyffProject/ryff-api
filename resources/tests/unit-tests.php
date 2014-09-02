<?php
/**
 * Unit Tests
 * ==========
 * 
 * This script will tear down the current test database, create a new test
 * database, and test the functions that the models provide. If there is an
 * error at any point in the tests, it will be reported and the script will exit.
 * Before exiting, the test database will be torn down again.
 * 
 * Command-line arguments:
 * "--no-setup" Doesn't install or uninstall the database.
 * "--no-teardown" Doesn't uninstall the database.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

define("TEST_MODE", true);

set_time_limit(300);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");
require_once("unit-test-environment.class.php");

if (in_array('--no-setup', $argv)) {
    $do_setup = false;
} else {
    $do_setup = true;
}

if (!$do_setup || in_array('--no-teardown', $argv)) {
    $do_teardown = false;
} else {
    $do_teardown = true;
}

echo "Running unit tests\n";
echo "==================\n";

$environment = new UnitTestEnvironment();
$environment->run($do_setup, $do_teardown);
