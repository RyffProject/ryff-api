<?php

/**
 * Add Comment
 * ===========
 * 
 * Authentication required.
 * Adds a comment to the given post.
 * 
 * POST variables:
 * "id" (required) The post id.
 * "content" (required) The text content of the comment, no more than 255 characters.
 * 
 * On success:
 * "success" The success message.
 * "comment" The comment.
 * 
 * On error:
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

$post_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!Post::get_by_id($post_id)) {
    echo json_encode(array("error" => "You must provide a valid post id."));
    exit;
}

$content = isset($_POST['content']) ? $_POST['content'] : "";
if (!$content) {
    echo json_encode(array("error" => "You must provide text content for the comment."));
    exit;
}

$comment = Comment::add($content, $post_id);
if ($comment) {
    echo json_encode(array(
        "success" => "Comment created successfully.",
        "comment" => $comment
    ));
} else {
    echo json_encode(array("error" => "Error creating comment."));
}
