<?php

/**
 * Get Conversation
 * ================
 * 
 * Authentication required.
 * Returns the existing conversation between the current user and one other
 * user, or creates one if it doesn't already exist.
 * 
 * POST variables:
 * "id" (required) The other user's id.
 * 
 * On success:
 * "success" The success message.
 * "conversation" The conversation.
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

$user_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($user_id === $CURRENT_USER->id) {
    echo json_encode(array("error" => "You cannot have a conversation with yourself."));
    exit;
}

$user = User::get_by_id($user_id);
if (!$user) {
    echo json_encode(array("error" => "The user does not exist."));
    exit;
}

$conversation = Conversation::get_for_user($user_id);
if ($conversation) {
    echo json_encode(array(
        "success" => "Conversation retrieved successfully.",
        "conversation" => $conversation
    ));
} else {
    echo json_encode(array("error" => "Error getting conversation."));
}
