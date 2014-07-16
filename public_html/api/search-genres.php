<?php

/**
 * Search Genres
 * =============
 * 
 * POST variables:
 * "query" (optional) The text that the returned genres should match.
 * 
 * Return on success:
 * "success" The success message.
 * "genres" An array of up to 10 of the most popular genre names that match the query.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the MIT License.
 */

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$query_str = isset($_POST['query']) ? trim($_POST['query']) : "";

$query = "SELECT `genre` FROM `genres`
          WHERE `genre` LIKE '%".$db->real_escape_string($query_str)."%'
          GROUP BY `genre`
          ORDER BY COUNT(*) DESC
          LIMIT 10";
$results = $db->query($query);
if ($results) {
    $genres = array();
    while ($row = $results->fetch_assoc()) {
        $genres[] = $row['genre'];
    }
    echo json_encode(array(
        "success" => "Retrieved genres successfully.",
        "genres" => $genres
    ));
    exit;
}

echo json_encode(array("error" => "Unable to retrieve genres."));
