<?php

/**
 * Send Message
 * ============
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The conversation you are sending this message to.
 * "content" (required) The text content of the message.
 * 
 * On success:
 * "success" The success message.
 * "message" The new Message object.
 * 
 * On error:
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

$conversation_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$conversation = Conversation::get_by_id($conversation_id);
if (!$conversation) {
    echo json_encode(array(
        "error" => "That conversation doesn't exist, or you are not a participant."
    ));
    exit;
}

$content = isset($_POST['content']) ? trim($_POST['content']) : "";
if (!$content) {
    echo json_encode(array("error" => "You must provide the message content."));
    exit;
}

$message = Message::send($content, $conversation_id);
if ($message) {
    echo json_encode(array(
        "success" => "Message sent successfully.",
        "message" => $message
    ));
} else {
    echo json_encode(array("error" => "Error sending message."));
}
