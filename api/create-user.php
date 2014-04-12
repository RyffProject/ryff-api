<?php

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../resources"
)));

require_once("global.php");

if (!$db) {
    $ERRORS++;
}

$name = isset($_POST['name']) ? trim($_POST['name']) : "";
$username = isset($_POST['username']) ? trim($_POST['username']) : "";
$email = isset($_POST['email']) ? trim($_POST['email']) : "";
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : "";
$password = isset($_POST['password']) ? trim($_POST['password']) : "";

if (!$name || strlen($name) > 255) {
    $ERRORS++;
}
if (!$username || strlen($username) > 32) {
    $ERRORS++;
}
if (!$email || strlen($email) > 255) {
    $ERRORS++;
}
if (!$password) {
    $ERRORS++;
}

if ($ERRORS) {
    echo json_encode(false);
    exit;
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$token = sha1(rand());
$query = "INSERT INTO `users`
          (`name`, `username`, `email`, `bio`, `password`, `token`, `date_updated`)
          VALUES ('".$db->real_escape_string($name)."','".$db->real_escape_string($username)."
          ','".$db->real_escape_string($email)."','".$db->real_escape_string($bio)."
          ','".$db->real_escape_string($password_hash)."','".$db->real_escape_string($token)."',NOW())";
$results = $db->query($query);

if ($results) {
    echo json_encode(true);
} else {
    echo json_encode(false);
}