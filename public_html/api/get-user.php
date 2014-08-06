<?php

/**
 * Get User
 * ========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (optional) The id of the user you want to get. Defaults to the current user.
 * 
 * Return on success:
 * "success" The success message.
 * "user" The user object.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

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
} else {
    echo json_encode(array("success" => "Retrieved user.", "user" => $CURRENT_USER));
}

