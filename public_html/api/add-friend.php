<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$to_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$to_user = User::get_by_id($to_id);
if (!$to_user) {
    echo json_encode(array("error" => "This user does not exist to add as a friend."));
    exit;
}

$exists_query = "SELECT * FROM `friends`
                 WHERE `to_id`=".$db->real_escape_string($to_id)."
                 AND `from_id`=".$db->real_escape_string($CURRENT_USER->id);
$exists_results = $db->query($exists_query);
if ($exists_results && $exists_results->num_rows) {
    echo json_encode(array("error" => "This user has already been added as a friend."));
    exit;
}

$query = "INSERT INTO `friends` (`to_id`, `from_id`)
          VALUES (".$db->real_escape_string($to_id).",".$db->real_escape_string($CURRENT_USER->id).")";
$results = $db->query($query);
if (!$results) {
    echo json_encode(array("error" => "Could not add the user as a friend."));
    exit;
} else {
    echo json_encode(array("success" => "Successfully added {$to_user->username} as a friend."));
    exit;
}
