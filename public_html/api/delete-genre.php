<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$genre = isset($_POST['genre']) ? trim($_POST['genre']) : "";
if (!$genre) {
    echo json_encode(array("error" => "No genre to delete!"));
    exit;
}

$query = "DELETE FROM `genres`
          WHERE `genre`='".$db->real_escape_string($genre)."'
          AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "Successfully deleted genre from user."));
} else {
    echo json_encode(array("error" => "Error deleting genre from user."));
}
