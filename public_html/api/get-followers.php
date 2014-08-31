<?php

/**
 * Get Followers
 * =============
 * 
 * Authentication required.
 * Gets the users that follow a given user.
 * 
 * POST variables:
 * "id" (optional) Defaults to the current user.
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of users per page. Defaults to 15.
 * 
 * Return on success:
 * "success" The success message.
 * "users" An array of user objects that follow the requested user.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
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
} else {
    $user_id = $CURRENT_USER->id;
}

$user = User::get_by_id($user_id);
if (!$user) {
    echo json_encode(array("error" => "You must provide a valid user id."));
    exit;
}

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$followers = Follow::get_followers($page, $limit, $user->id);
if (is_array($followers)) {
    echo json_encode(array(
        "success" => "Retrieved followers for {$user->username} successfully.",
        "users" => $followers
    ));
} else {
    echo json_encode(array("error" => "There was an error getting followers for {$user->username}."));
}
