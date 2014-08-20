<?php

/**
 * Login
 * =====
 * 
 * NOTE: On success, this script sets two cookies, one for the user_id and the
 * other for the auth_token. These are used to authenticate after logging in.
 * 
 * POST variables:
 * "auth_username" (required) The user's username.
 * "auth_password" (required) The user's password.
 * 
 * Return on success:
 * "success" The success message.
 * "user" The user object for the current user.
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

if ($ERRORS) {
    echo json_encode(array("error" => "Unable to connect to database"));
    exit;
}

$username = isset($_POST['auth_username']) ? trim($_POST['auth_username']) : "";
$password = isset($_POST['auth_password']) ? $_POST['auth_password'] : "";

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

if (Auth::is_login_valid($username, $password)) {
    $CURRENT_USER = User::get_by_username($username);
    if (Auth::set_logged_in()) {
        echo json_encode(array(
            "success" => "You have logged in successfully.",
            "user" => $CURRENT_USER
        ));
        exit;
    } else {
        echo json_encode(array("error" => "There was an error logging you in."));
        exit;
    }
}

echo json_encode(array("error" => "Invalid username or password"));
exit;
