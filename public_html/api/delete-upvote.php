<?php

/**
 * Delete Upvote
 * =============
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The id of the post you want to un-upvote.
 * 
 * Return on success:
 * "success" The success message.
 * "post" The updated post object.
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

$post_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$post = Post::get_by_id($post_id);
if (!$post) {
    echo json_encode(array("error" => "No post to remove upvote!"));
    exit;
}

if ($post->is_upvoted) {
    if (Upvote::delete($post->id)) {
        $new_post = Post::get_by_id($post->id);
        if ($new_post) {
            echo json_encode(array(
                "success" => "Successfully removed upvote.",
                "post" => $new_post
            ));
        } else {
            echo json_encode(array("error" => "Removed upvote but was unable to get the new post."));
        }
    } else {
        echo json_encode(array("error" => "Unable to remove upvote."));
    }
} else {
    echo json_encode(array("error" => "You have not upvoted this post."));
}
