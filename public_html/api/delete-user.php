<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$query = "UPDATE `users` SET `active`=0 WHERE `user_id`=".$db->real_escape_string((int)$CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "User deleted successfully."));
} else {
    echo json_encode(array("error" => "An error occurred while deleting the user."));
}