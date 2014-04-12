<?php

require_once("connect-db.php");
require_once("functions.php");

require_once("user.class.php");

$ERRORS = 0;

if (!$db) {
    $ERRORS++;
}

if (isset($_POST['username'])) {
    $USERNAME = $_POST['username'];
}
if (isset($_POST['password'])) {
    $PASSWORD = $_POST['password'];
}

if (defined("REQUIRES_AUTHENTICATION") && REQUIRES_AUTHENTICATION) {
    if (!isset($USERNAME) || !isset($PASSWORD)) {
        echo json_encode(array("error" => "Authentication required."));
        exit;
    } else if (!valid_login($USERNAME, $PASSWORD)) {
        echo json_encode(array("error" => "Invalid credentials."));
        exit;
    }
    $CURRENT_USER = get_user_from_username($USERNAME);
}
