<?php

/**
 * Get Messages
 * ============
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The conversation you are getting the messages from.
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of messages per page. Defaults to 15.
 * "type" (optional) either "unread" or "all". Defaults to "all".
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

$conversation_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$conversation = Conversation::get_by_id($conversation_id);
if (!$conversation) {
    echo json_encode(array(
        "error" => "The conversation does not exist or you are not a participant."
    ));
    exit;
}

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;
$unread = isset($_POST['type']) ? ($_POST['type'] === "unread") : false;

$messages = Message::get_for_conversation($conversation->id, $page, $limit, $unread);
if (is_array($messages)) {
    echo json_encode(array(
        "success" => "Messages retrieved successfully.",
        "users" => $conversation->users,
        "messages" => $messages
    ));
} else {
    echo json_encode(array("error" => "Error getting messages."));
}
