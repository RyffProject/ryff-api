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

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$starred_query = "
    SELECT `post_id` FROM `stars`
    WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id)."
    ORDER BY `date_created` DESC";
$starred_results = $db->query($starred_query);
if ($starred_results) {
    $posts = array();
    while ($row = $starred_results->fetch_assoc()) {
        $posts[] = Post::get_by_id((int)$row['post_id']);
    }
    echo json_encode(array(
        "success" => "Retrieved starred posts successfully.",
        "posts" => $posts
    ));
} else {
    echo json_encode(array("error" => "There was an error getting your starred posts."));
}
