<?php
/**
 * Connect Database
 * ================
 * 
 * If in test mode, this script will attempt to connect to the test database,
 * otherwise it will attempt to connect to the main database. The credentials
 * for these databases are set in config.php.
 * 
 * If the database is connected successfully, a global variable $dbh will be
 * created as a PDO object for accessing the database. If there is an error,
 * the script will output an error and exit.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once("db/NestedPDO.class.php");

try {
    if (TEST_MODE) {
        $dbh = new NestedPDO(
            "mysql:host=".TEST_DB_HOST.";dbname=".TEST_DB_NAME.";charset=utf8mb4",
            TEST_DB_USER, TEST_DB_PASS
        );
    } else {
        $dbh = new NestedPDO(
            "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
            DB_USER, DB_PASS
        );
    }
} catch (Exception $ex) {
    header("Content-Type: application/json");
    echo json_encode(array("error" => "Unable to connect to the database."));
    exit;
}
