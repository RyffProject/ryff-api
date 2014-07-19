<?php

/**
 * Get Instruments
 * ===============
 * 
 * Authentication required if "id" is not set.
 * 
 * POST variables:
 * "id" The id of the user you want to get. Defaults to the current user.
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

if (isset($_POST['id'])) {
    $USER_ID = (int)$_POST['id'];
    if (!User::get_by_id($USER_ID)) {
        echo json_encode(array("error" => "The requested user does not exist."));
        exit;
    }
} else {
    define("REQUIRES_AUTHENTICATION", true);
}

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (!isset($USER_ID)) {
    $USER_ID = $CURRENT_USER->id;
}

$query = "SELECT `instrument` FROM `instruments` WHERE `user_id`=".$db->real_escape_string($USER_ID);
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
