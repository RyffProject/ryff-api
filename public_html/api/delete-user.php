<?php

/**
 * Delete User
 * ===========
 * 
 * Authentication required.
 * 
 * NOTE: This script only sets the user "inactive", it does not actually delete
 * their record in the database.
 * 
 * Return on success:
 * "success" The success message.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (User::delete()) {
    setcookie('user_id', '', time() - 3600);
    setcookie('auth_token', '', time() - 3600);
    echo json_encode(array("success" => "User deleted successfully."));
} else {
    echo json_encode(array("error" => "An error occurred while deleting the user."));
}
