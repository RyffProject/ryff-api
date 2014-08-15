<?php

/**
 * Send Message
 * ============
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The user you are sending the message to.
 * "content" (required) The text content of the message.
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


define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$to_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$recipient = User::get_by_id($to_id);
if (!$recipient) {
    echo json_encode(array("error" => "Invalid recipient id."));
    exit;
} else if ($recipient->id === $CURRENT_USER->id) {
    echo json_encode(array("error" => "You cannot send a message to yourself."));
    exit;
}

$content = isset($_POST['content']) ? trim($_POST['content']) : "";
if (!$content) {
    echo json_encode(array("error" => "You must provide the message content."));
    exit;
}

$query = "INSERT INTO `messages` (`to_id`, `from_id`, `content`)
          VALUES (
              ".$db->real_escape_string($recipient->id).",
              ".$db->real_escape_string($CURRENT_USER->id).",
              '".$db->real_escape_string($content)."'
          )";
$results = $db->query($query);
if ($results) {
    echo json_encode(array("success" => "Message sent successfully."));
} else {
    echo json_encode(array("error" => "Error sending message."));
}
