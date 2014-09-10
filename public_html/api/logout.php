<?php

/**
 * Logout
 * ========
 * 
 * Authentication required.
 * Logs the user out.
 * 
 * Return on success:
 * "success" The success message.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (Auth::set_logged_out()) {
    echo json_encode(array("success" => "Successfully logged out."));
} else {
    echo json_encode(array("error" => "There was an error logging out."));
}
