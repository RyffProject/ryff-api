<?php

/**
 * Set Notification Read
 * =====================
 * 
 * Authentication required.
 * Sets the given notification as read, if it belongs to the current user.
 * 
 * POST variables:
 * "id" (required) The id of the notification you want to mark as read.
 * 
 * Return on success:
 * "success" The success message.
 * "notification" The updated notification object.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$notification_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$notification = Notification::get_by_id($notification_id);
if (!$notification) {
    echo json_encode(array(
        "error" => "This notification either does not exist, ".
                   "or you do not have permission to mark it as read."
    ));
    exit;
}

if ($notification->is_read) {
    echo json_encode(array(
        "error" => "This notification has already been marked as read."
    ));
    exit;
}

if (Notification::set_read($notification->id)) {
    echo json_encode(array(
        "success" => "Marked notification as read.",
        "notification" => Notification::get_by_id($notification->id)
    ));
} else {
    echo json_encode(array(
        "error" => "There was an error marking this notification as read."
    ));
}
