<?php

/**
 * Add Star
 * ========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The id of the post you want to star.
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
    echo json_encode(array("error" => "No post to star!"));
    exit;
}

if (!$post->is_starred) {
    if (Star::add($post->id)) {
        $new_post = Post::get_by_id($post->id);
        if ($new_post) {
            echo json_encode(array(
                "success" => "Successfully added star.",
                "post" => $new_post
            ));
        } else {
            echo json_encode(array("error" => "Added star but was unable to get the new post."));
        }
    } else {
        echo json_encode(array("error" => "Unable to add star."));
    }
} else {
    echo json_encode(array("error" => "You have already starred this post."));
}
