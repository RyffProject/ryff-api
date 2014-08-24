<?php

/**
 * Get Conversations
 * =================
 * 
 * Authentication required.
 * Returns an array of user objects you have sent or received messages from,
 * and the most recent message in the conversation.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of results per page. Defaults to 15.
 * "type" (optional) either "unread" or "all". Defaults to "all".
 * 
 * On success:
 * "success" The success message.
 * "conversations" An array of objects that have "user", "message", and "is_read" members.
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

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;
$type = isset($_POST['type']) ? $_POST['type'] : "all";

$conversations = Message::get_conversations_recent($page, $limit, $type === "unread");
if (is_array($conversations)) {
    echo json_encode(array(
        "success" => "Conversations retrieved successfully.",
        "conversations" => $conversations
    ));
} else {
    echo json_encode(array("error" => "Error getting conversations."));
}
