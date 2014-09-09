<?php

/**
 * Get Comments
 * ============
 * 
 * Authentication required.
 * Gets the comments for a specified post.
 * 
 * POST variables:
 * "id" (required) The post id.
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of comments per page. Defaults to 15.
 * 
 * Return on success:
 * "success" The success message.
 * "comments" An array of Comment objects from the given post.
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

$post_id = isset($_POST["id"]) ? (int)$_POST["id"] : 0;
$post = Post::get_by_id($post_id);
if (!$post) {
    echo json_encode(array("error" => "You must provide a valid post id."));
}

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$comments = Comment::get_for_post($post_id, $page, $limit);
if (is_array($comments)) {
    echo json_encode(array(
        "success" => "Retrieved comments successfully.",
        "comments" => $comments
    ));
} else {
    echo json_encode(array("error" => "There was an error getting comments."));
}
