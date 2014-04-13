<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$query = "SELECT `instrument` FROM `instruments` WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    $instruments = array();
    while ($row = $results->fetch_assoc()) {
        $instruments[] = $row['instrument'];
    }
    echo json_encode(array(
        "success" => "Successfully retrieved instruments for user.",
        "instruments" => $instruments
        ));
} else {
    echo json_encode(array("error" => "Error retrieving instruments for user."));
}
