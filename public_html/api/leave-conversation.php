<?php

/**
 * Leave Conversation
 * ==================
 * 
 * Authentication required.
 * Removes the current user from the given conversation.
 * 
 * POST variables:
 * "id" (required) The conversation id.
 * 
 * On success:
 * "success" The success message.
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
    echo json_encode(array("error" => "You must provide a valid conversation id."));
    exit;
}

if (Conversation::delete_member($conversation->id)) {
    echo json_encode(array( "success" => "Left conversation successfully."));
} else {
    echo json_encode(array("error" => "Error leaving conversation."));
}
