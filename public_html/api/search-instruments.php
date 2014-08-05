<?php

/**
 * Search Instruments
 * ==================
 * 
 * Authentication required.
 * 
 * POST variables:
 * "query" (required) The text that the returned instruments should match.
 * 
 * Return on success:
 * "success" The success message.
 * "instruments" An array of up to 10 of the most popular instrument names that match the query.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the MIT License.
 */

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$query_str = isset($_POST['query']) ? trim($_POST['query']) : false;
if (!$query_str) {
    echo json_encode(array("error" => "You must provide a query to search for."));
    exit;
}

$query = "SELECT `instrument` FROM `instruments`
          WHERE `instrument` LIKE '%".$db->real_escape_string($query_str)."%'
          GROUP BY `instrument`
          ORDER BY COUNT(*) DESC
          LIMIT 10";
$results = $db->query($query);
if ($results) {
    $instruments = array();
    while ($row = $results->fetch_assoc()) {
        $instruments[] = $row['instrument'];
    }
    echo json_encode(array(
        "success" => "Retrieved instruments successfully.",
        "instruments" => $instruments
    ));
    exit;
}

echo json_encode(array("error" => "Unable to retrieve instruments."));
