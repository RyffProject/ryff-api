<?php

/**
 * Get Tags
 * ========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (optional) The id of the user you want to get. Defaults to the current user.
 * 
 * Return on success:
 * "success" The success message.
 * "tags" An array of the names of tags for the current user.
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

if (isset($_POST['id'])) {
    $USER_ID = (int)$_POST['id'];
    if (!User::get_by_id($USER_ID)) {
        echo json_encode(array("error" => "The requested user does not exist."));
        exit;
    }
} else {
    $USER_ID = $CURRENT_USER->id;
}

$query = "SELECT `tag` FROM `user_tags` WHERE `user_id`=".$db->real_escape_string($USER_ID);
$results = $db->query($query);
if ($results) {
    $tags = array();
    while ($row = $results->fetch_assoc()) {
        $tags[] = $row['tag'];
    }
    echo json_encode(array(
        "success" => "Successfully retrieved tags for user.",
        "tags" => $tags
    ));
} else {
    echo json_encode(array("error" => "Error retrieving tags for user."));
}
