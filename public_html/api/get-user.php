<?php

if (isset($_POST['id'])) {
    $USER_ID = (int)$_POST['id'];
} else {
    define("REQUIRES_AUTHENTICATION", true);
}

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (isset($USER_ID)) {
    $user = get_user_from_id($USER_ID);
    if ($user) {
        echo json_encode(array("success" => "Retrieved user.", "user" => $user));
    } else {
        echo json_encode(array("error" => "Invalid user id."));
    }
} else {
    echo json_encode($CURRENT_USER);
}
