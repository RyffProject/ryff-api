<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (isset($_POST['name']) && $_POST['name']) {
    $name = $_POST['name'];
    if (strlen($name) > 255) {
        echo json_encode(array("error" => "Name cannot be more than 255 characters."));
        exit;
    }
    $query = "UPDATE `users` SET `name`='".$db->real_escape_string($name)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update name."));
        exit;
    }
}

if (isset($_POST['username']) && $_POST['username']) {
    $username = $_POST['username'];
    if (get_user_from_username($username)) {
        echo json_encode(array("error" => "This username is already in use."));
        exit;
    }
    if (strlen($username) > 32) {
        echo json_encode(array("error" => "Username cannot be more than 32 characters."));
        exit;
    }
    $query = "UPDATE `users` SET `username`='".$db->real_escape_string($username)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update username."));
        exit;
    }
}

if (isset($_POST['bio'])) {
    $bio = $_POST['bio'];
    $query = "UPDATE `users` SET `bio`='".$db->real_escape_string($bio)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update bio."));
        exit;
    }
}

if (isset($_POST['password']) && $_POST['password']) {
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $query = "UPDATE `users` SET `password`='".$db->real_escape_string($password_hash)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update password."));
        exit;
    }
}

$user = get_user_from_id($CURRENT_USER->id);
if ($user) {
    echo json_encode($user);
} else {
    echo json_encode(array("error" => "An error occurred processing your request."));
}
