<?php

/**
 * Search Tags
 * ===========
 * 
 * Authentication required.
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
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

define("REQUIRES_AUTHENTICATION", true);

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

$query = "SELECT `tag` FROM `user_tags`
          WHERE `tag` LIKE '%".$db->real_escape_string($query_str)."%'
          GROUP BY `tag`
          ORDER BY COUNT(*) DESC
          LIMIT 10";
$results = $db->query($query);
if ($results) {
    $tags = array();
    while ($row = $results->fetch_assoc()) {
        $tags[] = $row['tag'];
    }
    echo json_encode(array(
        "success" => "Retrieved tags successfully.",
        "tags" => $tags
    ));
    exit;
}

echo json_encode(array("error" => "Unable to retrieve tags."));
