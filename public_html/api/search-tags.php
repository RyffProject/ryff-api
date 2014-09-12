<?php

/**
 * Search Tags
 * ===========
 * 
 * Returns user tags similar to the query.
 * 
 * POST variables:
 * "query" (required) The text that the returned tags should match.
 * 
 * Return on success:
 * "success" The success message.
 * "tags" An array of up to 10 of the most popular user tags that match the query.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$query_str = isset($_POST['query']) ? trim($_POST['query']) : "";
if (!$query_str) {
    echo json_encode(array("error" => "You must provide a query to search for."));
    exit;
}

$tags = Tag::search_users($query_str);
if (is_array($tags)) {
    echo json_encode(array(
        "success" => "Retrieved tags successfully.",
        "tags" => $tags
    ));
} else {
    echo json_encode(array("error" => "Unable to retrieve tags."));
}
