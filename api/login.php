<?php

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../resources"
)));

require_once("global.php");

if (!$db) {
    $ERRORS++;
}

if ($ERRORS) {
    echo json_encode(array("error" => "Unable to connect to database"));
    exit;
}

$username = isset($_POST['username']) ? trim($_POST['username']) : "";
$password = isset($_POST['password']) ? $_POST['password'] : "";

if (!$username) {
    $ERRORS++;
}
if (!$password) {
    $ERRORS++;
}

if ($ERRORS) {
    echo json_encode(array("error" => "Missing username or password"));
    exit;
}

$password_hash = password_verify($password, PASSWORD_DEFAULT);
$query = "SELECT `token` FROM `users`
         WHERE `username`='".$db->real_escape_string($username)."'
         AND `password`=".$db->real_escape_string($password_hash);
$results = $db->query($query);

if ($results) {
    if ($row = $results->fetch_assoc()) {
        echo json_encode(array("token" => $row['token']));
        exit;
    }
} else {
    echo json_encode(array("error" => "Invalid username or password"));
    exit;
}