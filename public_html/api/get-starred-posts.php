<?php

/**
 * Get Starred Posts
 * =================
 * 
 * Authentication required.
 * 
 * Return on success:
 * "success" The success message.
 * "posts" The array of starred post objects.
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

$posts = Star::get_starred_posts();
if (is_array($posts)) {
    echo json_encode(array(
        "success" => "Retrieved starred posts successfully.",
        "posts" => $posts
    ));
} else {
    echo json_encode(array("error" => "There was an error getting your starred posts."));
}
