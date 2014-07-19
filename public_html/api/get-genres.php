<?php

/**
 * Get Genres
 * ==========
 * 
 * Authentication required if "id" is not set.
 * 
 * POST variables:
 * "id" The id of the user you want to get. Defaults to the current user.
 * 
 * Return on success:
 * "success" The success message.
 * "genres" An array of the names of genres for the current user.
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

$query = "SELECT `genre` FROM `genres` WHERE `user_id`=".$db->real_escape_string($USER_ID);
$results = $db->query($query);
if ($results) {
    $genres = array();
    while ($row = $results->fetch_assoc()) {
        $genres[] = $row['genre'];
    }
    echo json_encode(array(
        "success" => "Successfully retrieved genres for user.",
        "genres" => $genres
    ));
} else {
    echo json_encode(array("error" => "Error retrieving genres for user."));
}
