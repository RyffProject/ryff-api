<?php

/**
 * Delete User
 * ===========
 * 
 * NOTE: This script only sets the user "inactive", it does not actually delete
 * their record in the database.
 * 
 * POST variables:
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

$query = "UPDATE `users` SET `active`=0, `date_deactivated`=NOW()
          WHERE `user_id`=".$db->real_escape_string((int)$CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "User deleted successfully."));
} else {
    echo json_encode(array("error" => "An error occurred while deleting the user."));
}
