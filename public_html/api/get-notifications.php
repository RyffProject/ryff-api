<?php

/**
 * Get Notifications
 * =================
 * 
 * Authentication required.
 * Gets notifications for the current user.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of notifications per page. Defaults to 15.
 * 
 * Return on success:
 * "success" The success message.
 * "notifications" An array of notification objects for the requested user.
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

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$notifications = Notification::get_latest($page, $limit);
if (is_array($notifications)) {
    echo json_encode(array(
        "success" => "Retrieved notifications successfully.",
        "notifications" => $notifications
    ));
} else {
    echo json_encode(array("error" => "There was an error getting notifications."));
}
