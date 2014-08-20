<?php

/**
 * Search Posts New
 * ================
 * 
 * Authentication required.
 * Gives an array of posts sorted by most recent.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of posts per page. Defaults to 15.
 * "tags" (optional) An array or comma-separated string of tags that the posts should match.
 * 
 * Return on success:
 * "success" The success message.
 * "posts" An array of post objects sorted by most recent and optionally tagged.
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

$tags = array();
if (isset($_POST['tags'])) {
    $tag_pattern = '/[^a-zA-Z0-9_\- ]/';
    if (is_array($_POST['tags'])) {
        $tags = preg_replace($tag_pattern, "", $_POST['tags']);
    } else {
        $tags = preg_replace($tag_pattern, "", explode(',', $_POST['tags']));
    }
}

$posts = PostFeed::search_latest($tags, $page, $limit);
if (is_array($posts)) {
    echo json_encode(array(
        "success" => "Found some recent posts.",
        "posts" => $posts
    ));
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
