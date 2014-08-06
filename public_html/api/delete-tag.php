<?php

/**
 * Delete Tag
 * ==========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "tag" (required) The name of the tag you want to remove.
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

$tag = isset($_POST['tag']) ? trim($_POST['tag']) : "";
if (!$tag) {
    echo json_encode(array("error" => "No tag to delete!"));
    exit;
}

$query = "DELETE FROM `user_tags`
          WHERE `tag`='".$db->real_escape_string($tag)."'
          AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "Successfully deleted tag from user."));
} else {
    echo json_encode(array("error" => "Error deleting tag from user."));
}
