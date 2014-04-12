<?php

require_once("connect-db.php");
require_once("functions.php");

require_once("user.class.php");

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
    } else if (!valid_login($AUTH_USERNAME, $AUTH_PASSWORD)) {
        echo json_encode(array("error" => "Invalid credentials."));
        exit;
    }
    $CURRENT_USER = get_user_from_username($AUTH_USERNAME);
}
