<?php

/**
 * Get User
 * ========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (optional) The id of the user you want to get.
 * "username" (optional) The username of the user to get.
 * 
 * Return on success:
 * "success" The success message.
 * "user" The user object. Defaults to the current user if no id or username provided.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (isset($_POST['id'])) {
    $user_id = (int)$_POST['id'];
    
    $user = User::get_by_id($user_id);
    if ($user) {
        echo json_encode(array("success" => "Retrieved user.", "user" => $user));
    } else {
        echo json_encode(array("error" => "Invalid user id."));
    }
} else if (isset($_POST['username'])) {
    $username = $_POST['username'];

    $user = User::get_by_username($username);
    if ($user) {
        echo json_encode(array("success" => "Retrieved user.", "user" => $user));
    } else {
        echo json_encode(array("error" => "Invalid username."));
    }
} else {
    echo json_encode(array("success" => "Retrieved user.", "user" => $CURRENT_USER));
}
