<?php

/**
 * Get Instruments
 * ===============
 * 
 * POST variables:
 * "auth_username" (required) The current user's username, used for authentication.
 * "auth_password" (required) The current user's password, used for authentication.
 * 
 * Return on success:
 * "success" The success message.
 * "instruments" An array of the names of instruments for the current user.
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
