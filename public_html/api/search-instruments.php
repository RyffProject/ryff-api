<?php

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$query_str = isset($_POST['query']) ? trim($_POST['query']) : "";

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
