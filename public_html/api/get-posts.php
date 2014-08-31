<?php

/**
 * Get Posts
 * =========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (optional) The id of the user whose posts you want to get. Defaults 
 *                 to the current user.
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of posts per page. Defaults to 15.
 * 
 * Return on success:
 * "success" The success message.
 * "posts" An array of post objects from the requested user.
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

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$posts = PostFeed::get_user_latest($page, $limit, $user_id);
if (is_array($posts)) {
    echo json_encode(array(
        "success" => "Retrieved posts successfully.",
        "posts" => $posts
    ));
} else {
    echo json_encode(array("error" => "There was an error getting the user's posts."));
}
