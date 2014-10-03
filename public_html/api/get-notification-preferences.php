<?php
/**
 * Get Notification Preferences
 * ============================
 * 
 * Authentication required.
 * Returns an array of notification preferences for the current user.
 * 
 * Return on success:
 * "success" The success message.
 * "preferences" An associative $type => $value array of notification preferences.
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

$preferences = Preferences::get_notification_preferences();
if (!$preferences) {
    echo json_encode(array("error" => "Failed to get notification preferences."));
    exit;
}

echo json_encode(array(
    "success" => "Successfully got notification preferences.",
    "preferences" => $preferences
));
