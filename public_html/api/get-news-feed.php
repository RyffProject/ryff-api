<?php

/**
 * Get News Feed
 * =============
 * 
 * Authentication required.
 * Gets the posts of the users you follow, ordered by most recent first.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of posts per page. Defaults to 15.
 * 
 * Return on success:
 * "success" The success message.
 * "posts" An array of no more than "limit" post objects in descending chronological order.
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

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$posts = PostFeed::get_friends_latest($page, $limit);
if (is_array($posts)) {
    echo json_encode(array(
        "success" => "Retrieved posts successfully.",
        "posts" => $posts
    ));
} else {
    echo json_encode(array("error" => "There was an error getting the news feed."));
}
