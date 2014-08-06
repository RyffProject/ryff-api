<?php

/**
 * Create User
 * ===========
 * 
 * NOTE: On success, this script sets two cookies, one for the user_id and the
 * other for the auth_token. These are used to authenticate after logging in.
 * 
 * POST variables:
 * "username" (required) The username for the new user. No more than 32 characters.
 * "password" (required) The password for the new user.
 * "name" (optional) The name for the new user. No more than 255 characters.
 * "email" (optional) The email address for the new user. No more than 255 characters.
 * "bio" (optional) The bio[graphy] for the new user. No more than 65535 bytes.
 * "latitude" (optional) The user's current latitude GPS coordinate.
 * "longitude" (optional) The user's current longitude GPS coordinate.
 * 
 * File uploads:
 * "avatar" (optional) An image for the new user in PNG format.
 * 
 * Return on success:
 * "success" The success message.
 * "user" The newly created user object.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

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

if (strlen($name) > 255) {
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
if (strlen($email) > 255) {
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
if ($email) {
    $email_results = $db->query("SELECT * FROM `users` WHERE `email`='".$db->real_escape_string($email)."'");
    if ($email_results && $email_results->num_rows) {
        echo json_encode(array("error" => "Email already in use."));
        exit;
    }
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$query = "INSERT INTO `users`
          (`name`, `username`, `email`, `bio`, `password`, `date_updated`)
          VALUES ('".$db->real_escape_string($name)."','".$db->real_escape_string($username)."'
          ,'".$db->real_escape_string($email)."','".$db->real_escape_string($bio)."'
          ,'".$db->real_escape_string($password_hash)."',NOW())";
$results = $db->query($query);

if ($results) {
    $user_id = $db->insert_id;
    if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
        $latitude = (double)$_POST['latitude'];
        $longitude = (double)$_POST['longitude'];
        
        if ($latitude && $longitude) {
            $location_query = "INSERT INTO `locations` (`user_id`, `location`)
                               VALUES (".$db->real_escape_string((int)$user_id).",
                               POINT(".$db->real_escape_string($latitude).",".
                               $db->real_escape_string($longitude)."))";
            $results = $db->query($location_query);
        }
    }
    if (isset($_FILES['avatar']) && !$_FILES['avatar']['error'] && $_FILES['avatar']['type'] === "image/png") {
        $path = AVATAR_ABSOLUTE_PATH."/$user_id.png";
        if (file_exists($path)) {
            unlink($path);
        }
        move_uploaded_file($_FILES['avatar']['tmp_name'], $path);
    }
    
    $new_user = User::get_by_id($user_id);
    if ($new_user->set_logged_in()) {
        echo json_encode(array(
            "success" => "You have successfully registered, $username.",
            "user" => $new_user
        ));
    } else {
        echo json_encode(array(
            "error" => "You have been registered, but there was an error logging you in."
        ));
    }
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
