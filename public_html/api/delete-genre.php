<?php

/**
 * Delete Genre
 * ============
 * 
 * POST variables:
 * "genre" (required) The name of the genre you want to remove.
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
