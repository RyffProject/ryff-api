<?php
/**
 * Update Notification Preferences
 * ===============================
 * 
 * Authentication required.
 * Updates the push notification preferences for the current user.
 * 
 * POST variables:
 * "type" (required) One of 'follow', 'upvote', 'remix', 'mention', 'message', or 'post'.
 * "value" (required) Ether 1 for on, or 0 for off.
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

$type = isset($_POST['type']) ? $_POST['type'] : "";
$value = isset($_POST['value']) ? (bool)$_POST['value'] : null;
if (!$type || $value === null) {
    echo json_encode(array("error" => "Both type and value must be set."));
    exit;
}

if (!Preferences::update_notification_preference($type, $value)) {
    echo json_encode(array("error" => "Failed to update notification for type '$type'."));
    exit;
}

echo json_encode(array("success" => "Successfully updated notification for type '$type'."));
