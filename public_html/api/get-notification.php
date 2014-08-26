<?php

/**
 * Get Notification
 * ================
 * 
 * Authentication required.
 * Gets a single notification by id, if it belongs to the current user.
 * 
 * POST variables:
 * "id" (required) The notification id.
 * 
 * Return on success:
 * "success" The success message.
 * "notification" The Notification object.
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

$notification_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$notification = Notification::get_by_id($notification_id);
if ($notification) {
    echo json_encode(array(
        "success" => "Retrieved notification successfully.",
        "notification" => $notification
    ));
} else {
    echo json_encode(array("error" => "There was an error getting the notification."));
}
