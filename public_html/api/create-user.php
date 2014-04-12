<?php

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$name = isset($_POST['name']) ? trim($_POST['name']) : "";
$username = isset($_POST['username']) ? trim($_POST['username']) : "";
$email = isset($_POST['email']) ? trim($_POST['email']) : "";
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : "";
$password = isset($_POST['password']) ? trim($_POST['password']) : "";

if (!$name) {
    echo json_encode(array("error" => "Missing name."));
    exit;
} else if (strlen($name) > 255) {
    echo json_encode(array("error" => "Name cannot be more than 255 characters."));
    exit;
}
if (!$username) {
    echo json_encode(array("error" => "Missing username."));
    exit;
} else if (strlen($username) > 32) {
    echo json_encode(array("error" => "Username cannot be more than 32 characters."));
    exit;
}
if (!$email) {
    echo json_encode(array("error" => "Missing email."));
    exit;
} else if (strlen($email) > 255) {
    echo json_encode(array("error" => "Email cannot be more than 255 characters."));
    exit;
}
if (!$password) {
    echo json_encode(array("error" => "Missing password."));
    exit;
}
$username_results = $db->query("SELECT * FROM `users` WHERE `username`='".$db->real_escape_string($username)."'");
if ($username_results && $username_results->num_rows) {
    echo json_encode(array("error" => "Username already in use."));
    exit;
}
$email_results = $db->query("SELECT * FROM `users` WHERE `email`='".$db->real_escape_string($email)."'");
if ($email_results && $email_results->num_rows) {
    echo json_encode(array("error" => "Email already in use."));
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
    echo json_encode(array("success" => "You have successfully registered, $username."));
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}