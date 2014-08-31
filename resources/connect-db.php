<?php

require_once(__DIR__."/config.php");

try {
    $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
} catch (Exception $ex) {
    header("Content-Type: application/json");
    echo json_encode(array("error" => "Unable to connect to the database."));
    exit;
}
