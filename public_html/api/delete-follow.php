<?php

/**
 * Delete Follow
 * =============
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The id of the user you want to unfollow.
 * 
 * Return on success:
 * "success" The success message.
 * "user" The updated user object that was unfollowed.
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

$to_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$to_user = User::get_by_id($to_id);
if (!$to_user) {
    echo json_encode(array("error" => "User does not exist to unfollow!"));
    exit;
}

if ($to_user->is_following) {
    if (Follow::delete($to_user->id)) {
        echo json_encode(array(
            "success" => "Successfully unfollowed {$to_user->username}.",
            "user" => User::get_by_id($to_user->id)
        ));
    } else {
        echo json_encode(array("error" => "Could not unfollow the user."));
    }
} else {
    echo json_encode(array("error" => "This user is not being followed."));
}
