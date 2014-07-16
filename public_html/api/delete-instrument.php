<?php

/**
 * Delete Instrument
 * =================
 * 
 * POST variables:
 * "instrument" (required) The name of the instrument you want to remove.
 * "auth_username" (required) The current user's username, used for authentication.
 * "auth_password" (required) The current user's password, used for authentication.
 * 
 * Return on success:
 * "success" The success message.
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

$instrument = isset($_POST['instrument']) ? trim($_POST['instrument']) : "";
if (!$instrument) {
    echo json_encode(array("error" => "No instrument to delete!"));
    exit;
}

$query = "DELETE FROM `instruments`
          WHERE `instrument`='".$db->real_escape_string($instrument)."'
          AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "Successfully deleted instrument from user."));
} else {
    echo json_encode(array("error" => "Error deleting instrument from user."));
}
