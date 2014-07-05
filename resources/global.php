<?php

header("Content-Type: application/json");

require_once("connect-db.php");
require_once("functions.php");

require_once("user.class.php");
require_once("point.class.php");
require_once("post.class.php");
require_once("riff.class.php");

$ERRORS = 0;

if (!$db) {
    $ERRORS++;
}

if (isset($_POST['auth_username'])) {
    $AUTH_USERNAME = $_POST['auth_username'];
}
if (isset($_POST['auth_password'])) {
    $AUTH_PASSWORD = $_POST['auth_password'];
}

if (defined("REQUIRES_AUTHENTICATION") && REQUIRES_AUTHENTICATION) {
    if (!isset($AUTH_USERNAME) || !isset($AUTH_PASSWORD)) {
        echo json_encode(array("error" => "Authentication required."));
        exit;
    } else if (!User::is_valid_login($AUTH_USERNAME, $AUTH_PASSWORD)) {
        echo json_encode(array("error" => "Invalid credentials."));
        exit;
    }
    $CURRENT_USER = User::get_by_username($AUTH_USERNAME);
}
