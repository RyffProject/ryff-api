<?php

/**
 * Add Genre
 * =========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "genre" (required) The name of the genre you want to add.
 * 
 * On success:
 * "success" The success message.
 * 
 * On error:
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
    echo json_encode(array("error" => "No genre to add!"));
    exit;
}

$unique_query = "SELECT `genre_id` FROM `genres`
                 WHERE `genre`='".$db->real_escape_string($genre)."'
                 AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$unique_results = $db->query($unique_query);
if ($unique_results && $unique_results->num_rows) {
    echo json_encode(array("error" => "This genre already exists for this user!"));
    exit;
}

$query = "INSERT INTO `genres` (`user_id`, `genre`)
          VALUES (".$db->real_escape_string($CURRENT_USER->id).",'".$db->real_escape_string($genre)."')";
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "Successfully added genre to user."));
} else {
    echo json_encode(array("error" => "Error adding genre to user."));
}
