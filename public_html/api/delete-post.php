<?php

/**
 * Delete Post
 * ===========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The id of the post you want to remove.
 * 
 * Return on success:
 * "success" The success message.
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

$post_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$post = Post::get_by_id($post_id);
if (!$post) {
    echo json_encode(array("error" => "No post to delete!"));
    exit;
}

if (Post::delete($post->id)) {
    echo json_encode(array("success" => "Successfully deleted post from user."));
} else {
    echo json_encode(array("error" => "Error deleting post from user."));
}
