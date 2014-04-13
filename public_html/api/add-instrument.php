<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$instrument = isset($_POST['instrument']) ? trim($_POST['instrument']) : "";
if (!$instrument) {
    echo json_encode(array("error" => "No instrument to add!"));
    exit;
}

$unique_query = "SELECT `instrument_id` FROM `instruments`
                 WHERE `instrument`='".$db->real_escape_string($instrument)."'
                 AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$unique_results = $db->query($unique_query);
if ($unique_results && $unique_results->num_rows) {
    echo json_encode(array("error" => "This instrument already exists for this user!"));
    exit;
}

$query = "INSERT INTO `instruments` (`user_id`, `instrument`)
          VALUES (".$db->real_escape_string($CURRENT_USER->id).",'".$db->real_escape_string($instrument)."')";
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "Successfully added instrument to user."));
} else {
    echo json_encode(array("error" => "Error adding instrument to user."));
}
