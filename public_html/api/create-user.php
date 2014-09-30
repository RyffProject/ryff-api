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
 * "activation_code" (optional) The activation code, required if registration is not open.
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
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$name = isset($_POST['name']) ? trim($_POST['name']) : "";
$username = isset($_POST['username']) ? trim($_POST['username']) : "";
$activation_code = isset($_POST['activation_code']) ? trim($_POST['activation_code']) : "";
$email = isset($_POST['email']) ? trim($_POST['email']) : "";
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : "";
$password = isset($_POST['password']) ? trim($_POST['password']) : "";

//Check activation code
if (!REGISTRATION_OPEN) {
    if (!$activation_code) {
        echo json_encode(array("error" => "Registration is closed, you must provide an activation code."));
        exit;
    } else if (!Preregister::is_activation_valid($activation_code)) {
        echo json_encode(array("error" => "Your activation code is invalid."));
        exit;
    }
}

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
$username_user = User::get_by_username($username);
if ($username_user) {
    echo json_encode(array("error" => "Username already in use."));
    exit;
}
if ($email) {
    $email_user = User::get_by_email($email);
    if ($email_user) {
        echo json_encode(array("error" => "Email already in use."));
        exit;
    }
}

if (isset($_FILES['avatar']) && !$_FILES['avatar']['error'] && @getimagesize($_FILES['avatar']['tmp_name'])) {
    $avatar_tmp_path = $_FILES['avatar']['tmp_name'];
} else {
    $avatar_tmp_path = "";
}

$user = User::add($name, $username, $email, $bio, $password, $avatar_tmp_path);
if ($user) {
    if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
        $latitude = (double)$_POST['latitude'];
        $longitude = (double)$_POST['longitude'];
        
        $user->set_location($latitude, $longitude);
    }
    
    if (Auth::set_logged_in($user->id)) {
        echo json_encode(array(
            "success" => "You have successfully registered, {$user->username}.",
            "user" => $user
        ));
    } else {
        echo json_encode(array(
            "error" => "You have been registered, but there was an error logging you in."
        ));
    }
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
