<?php

require_once(__DIR__."/config.php");

try {
    $db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
} catch (Exception $ex) {
    echo json_encode(array("error" => "Unable to connect to the database."));
    exit;
}
