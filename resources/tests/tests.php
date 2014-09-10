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
 * "--type=TYPE" The type of tests to run, one of api, unit, or populate.
 * "--cycles=CYCLES" The number of populate cycles to run.
 * "--help" Brings up the help text.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

if (in_array('--help', $argv)) {
    echo "--no-setup: Doesn't attempt to install or uninstall the database.\n";
    echo "--no-teardown: Doesn't attempt to uninstall the database.\n";
    echo "--type=TYPE: The type of test. One of api, unit, or populate.\n";
    echo "--cycles=CYCLES: The number of populate cycles to run.\n";
    echo "--help: Brings up this text.\n";
    exit;
}

define("TEST_MODE", true);

set_time_limit(300);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$lock_file = __DIR__."/tests.lock";
if (file_exists($lock_file)) {
    echo "Found existing tests lock file. Exiting now.\n";
    exit;
}

file_put_contents($lock_file, "Locked at ".date('Y-m-d H:i:s')."\n");

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

foreach ($argv as $arg) {
    if (strpos($arg, '--type=') === 0) {
        $type = strtolower(substr($arg, 7));
    }
}
if (!isset($type)) {
    echo "You must specify a type.\n";
    echo "Use --help for more information.\n";
    exit;
} else if ($type === 'api') {
    $environment = new ApiTests();
} else if ($type === 'unit') {
    $environment = new UnitTests();
} else if ($type === 'populate') {
    $environment = new PopulateTests();
    foreach ($argv as $arg) {
        if (strpos($arg, '--cycles=') === 0) {
            $num_cycles = (int)substr($arg, 9);
            $environment->set_num_cycles($num_cycles);
        }
    }
} else {
    echo "The type must be either unit or populate.\n";
    echo "Use --help for more information.\n";
    exit;
}

echo "Running $type tests\n";
echo "========".str_repeat("=", strlen($type))."======\n";

$environment->run($do_setup, $do_teardown);

if (file_exists($lock_file)) {
    unlink($lock_file);
}
