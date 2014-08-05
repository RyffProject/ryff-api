<?php

/**
 * Follow
 * ======
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The id of the user you want to follow.
 * 
 * Return on success:
 * "success" The success message.
 * "id" The id of the user that was followed.
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

$to_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$to_user = User::get_by_id($to_id);
if (!$to_user) {
    echo json_encode(array("error" => "This user does not exist to follow."));
    exit;
}

$exists_query = "SELECT * FROM `follows`
                 WHERE `to_id`=".$db->real_escape_string($to_id)."
                 AND `from_id`=".$db->real_escape_string($CURRENT_USER->id);
$exists_results = $db->query($exists_query);
if ($exists_results && $exists_results->num_rows) {
    echo json_encode(array("error" => "This user is already being followed."));
    exit;
}

$query = "INSERT INTO `follows` (`to_id`, `from_id`)
          VALUES (".$db->real_escape_string($to_id).",".$db->real_escape_string($CURRENT_USER->id).")";
$results = $db->query($query);
if (!$results) {
    echo json_encode(array("error" => "Could not follow the user."));
    exit;
} else {
    echo json_encode(array(
        "success" => "Successfully followed {$to_user->username}.",
        "id" => $to_user->id
    ));
    exit;
}
