<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$to_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$to_user = get_user_from_id($to_id);
if (!$to_user) {
    echo json_encode(array("error" => "User does not exist to unfriend!"));
    exit;
}

$query = "DELETE FROM `friends`
          WHERE `to_id`=".$db->real_escape_string($to_id)."
          AND `from_id`=".$db->real_escape_string($CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "Successfully unfriended {$to_user->username}"));
    exit;
} else {
    echo json_encode(array("error" => "Unable to unfriend {$to_user->username}."));
    exit;
}
