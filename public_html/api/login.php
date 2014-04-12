<?php

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
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

$query = "SELECT `token`, `password` FROM `users`
         WHERE `username`='".$db->real_escape_string($username)."'";
$results = $db->query($query);
if ($results) {
    if ($row = $results->fetch_assoc()) {
        $password_hash = $row['password'];
        if (password_verify($password, $password_hash)) {
            echo json_encode(array(
                "success" => "You have successfully logged in.", 
                "token" => $row['token']
                ));
            exit;
        }
    }
}

echo json_encode(array("error" => "Invalid username or password"));
exit;
