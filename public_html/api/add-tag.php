<?php

/**
 * Add Tag
 * =======
 * 
 * Authentication required.
 * 
 * POST variables:
 * "tag" (required) The tag you want to add.
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

$tag = isset($_POST['tag']) ? trim($_POST['tag']) : "";
if (!$tag) {
    echo json_encode(array("error" => "No tag to add!"));
    exit;
}

$unique_query = "SELECT `tag_id` FROM `user_tags`
                 WHERE `tag`='".$db->real_escape_string($tag)."'
                 AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$unique_results = $db->query($unique_query);
if ($unique_results && $unique_results->num_rows) {
    echo json_encode(array("error" => "This tag already exists for this user!"));
    exit;
}

$query = "INSERT INTO `user_tags` (`user_id`, `tag`)
          VALUES (".$db->real_escape_string($CURRENT_USER->id).",'".$db->real_escape_string($tag)."')";
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "Successfully added tag to user."));
} else {
    echo json_encode(array("error" => "Error adding tag to user."));
}
