<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$query = "SELECT `genre` FROM `genres` WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    $genres = array();
    while ($row = $results->fetch_assoc()) {
        $genres[] = $row['genre'];
    }
    echo json_encode(array(
        "success" => "Successfully retrieved genres for user.",
        "genres" => $genres
        ));
} else {
    echo json_encode(array("error" => "Error retrieving genres for user."));
}
