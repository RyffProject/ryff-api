<?php

/**
 * Add Conversation
 * ================
 * 
 * Authentication required.
 * Creates a conversation between the current user and two or more other users.
 * 
 * POST variables:
 * "ids" (required) An array or comma-separated string of at least two user ids.
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

header("Content-Type: application/json");

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$raw_user_ids = isset($_POST['ids']) ? $_POST['ids'] : array();
if (is_array($raw_user_ids)) {
    $raw_user_ids_array = $raw_user_ids;
} else {
    $raw_user_ids_array = array_filter(explode(',', $raw_user_ids));
}
$raw_user_ids_array[] = $CURRENT_USER->id;

$user_ids = array_filter(
    array_unique(array_map(intval, $raw_user_ids_array)),
    function($id) {
        return User::get_by_id($id) !== null;
    }
);

if (count($user_ids) < 2) {
    echo json_encode(array("error" => "There must be at least two valid participant ids."));
    exit;
}

$conversation = Conversation::add($user_ids);
if ($conversation) {
    echo json_encode(array(
        "success" => "Conversation created successfully.",
        "conversation" => $conversation
    ));
} else {
    echo json_encode(array("error" => "Error creating conversation."));
}
