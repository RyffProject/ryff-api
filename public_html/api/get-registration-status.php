<?php
/**
 * Get Registration Status
 * =======================
 * 
 * Returns the registration status from the config, so that clients can
 * optionally show an extra field for the activation code at user creation.
 * If registration is "closed", the create-user script will require an
 * activation code.
 * 
 * Return on success:
 * "success" The success message.
 * "status" Either "open" or "closed".
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

echo json_encode(array(
    "success" => "Got registration status successfully.",
    "status" => (REGISTRATION_OPEN ? "open" : "closed")
));
