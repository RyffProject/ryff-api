<?php

/**
 * Get Following
 * =============
 * 
 * Gets the users that the given user follows.
 * 
 * POST variables:
 * "id" (optional) Defaults to the current user.
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of users per page. Defaults to 15.
 * 
 * Return on success:
 * "success" The success message.
 * "users" An array of user objects that are followed by the requested user.
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

if (isset($_POST['id'])) {
    $user_id = (int)$_POST['id'];
} else {
    define("REQUIRES_AUTHENTICATION", true);
}

require_once("global.php");

$user = isset($user_id) ? User::get_by_id($user_id) : $CURRENT_USER;
if (!$user) {
    echo json_encode(array("error" => "You must provide a valid user id."));
    exit;
}

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$following = Follow::get_following($page, $limit, $user->id);
if (is_array($following)) {
    echo json_encode(array(
        "success" => "Retrieved users that {$user->username} is following successfully.",
        "users" => $following
    ));
} else {
    echo json_encode(array("error" => "There was an error getting the users that {$user->username} follows."));
}
