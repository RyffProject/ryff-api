<?php

/**
 * Delete Comment
 * ==============
 * 
 * Authentication required.
 * Deletes a comment, if it belongs to the current user.
 * 
 * POST variables:
 * "id" (required) The comment id.
 * 
 * On success:
 * "success" The success message.
 * 
 * On error:
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

$comment_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$comment = Comment::get_by_id($comment_id);
if (!$comment) {
    echo json_encode(array("error" => "You must provide a valid comment id."));
    exit;
} else if ($comment->user->id !== $CURRENT_USER->id) {
    echo json_encode(array("error" => "You cannot delete comments you have not added."));
    exit;
}

if (Comment::delete($comment_id)) {
    echo json_encode(array(
        "success" => "Comment deleted successfully."
    ));
} else {
    echo json_encode(array("error" => "Error creating comment."));
}
