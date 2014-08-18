<?php

/**
 * Get Messages
 * ============
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The user you are getting the conversation from.
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of messages per page. Defaults to 15.
 * 
 * On success:
 * "success" The success message.
 * "users" An array of users involved in the conversation.
 * "messages" A chronological array of messages, constrained by "page" and "limit".
 * 
 * On error:
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

$to_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$recipient = User::get_by_id($to_id);
if (!$recipient) {
    echo json_encode(array("error" => "Invalid user id."));
    exit;
} else if ($recipient->id === $CURRENT_USER->id) {
    echo json_encode(array("error" => "You cannot get messages from yourself."));
    exit;
}

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$messages = Message::get_conversation($recipient->id, $page, $limit);
if (is_array($messages)) {
    echo json_encode(array(
        "success" => "Messages retrieved successfully.",
        "users" => array($CURRENT_USER, $recipient),
        "messages" => $messages
    ));
} else {
    echo json_encode(array("error" => "Error getting messages."));
}
