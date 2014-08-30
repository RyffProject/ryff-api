<?php

/**
 * Add APNS Token
 * ==============
 * 
 * Authentication required.
 * Stores the given token for the given device id so that the user can
 * receive notifications through the Apple Push Notification service.
 * 
 * POST variables:
 * "token" (required) The 64-hex-digit device token used to send via APNs.
 * "uuid" (required) The 36-character UUID for the device.
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

$token = isset($_POST['token']) ? $_POST['token'] : "";
$uuid = isset($_POST['uuid']) ? $_POST['uuid'] : "";

if (strlen($token) !== 64) {
    echo json_encode(array("error" => "Your device token must be 64 characters."));
    exit;
} else if (strlen($uuid) !== 36) {
    echo json_encode(array("error" => "Your device UUID must be 36 characters."));
    exit;
}

if (PushNotification::add_apns_token($token, $uuid)) {
    echo json_encode(array("success" => "Successfully added device token."));
} else {
    echo json_encode(array("error" => "Failed to add device token."));
}
