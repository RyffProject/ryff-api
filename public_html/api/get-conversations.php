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
 * 
 * On success:
 * "success" The success message.
 * "conversations" An array of objects that have both "user" and "message" members.
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

$page_num = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$num_conversations = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$query = "
    SELECT m.*
    FROM (
        SELECT m1.*,
            IF(m1.`from_id`={$CURRENT_USER->id},m1.`to_id`,m1.`from_id`) AS `user_id`
        FROM `messages` AS m1
        WHERE m1.`from_id` = {$CURRENT_USER->id}
        OR m1.`to_id` = {$CURRENT_USER->id}
    ) AS m
    WHERE m.`date_created` = (
        SELECT m2.`date_created` FROM `messages` AS m2
        WHERE (
            m2.`from_id` = {$CURRENT_USER->id}
            AND m2.`to_id` = m.`user_id`
        ) OR (
            m2.`from_id` = m.`user_id`
            AND m2.`to_id` = {$CURRENT_USER->id}
        )
        ORDER BY m2.`date_created` DESC
        LIMIT 1
    )
    ORDER BY m.`date_created`
    LIMIT ".(($page_num - 1) * $num_conversations).", ".$num_conversations;
$results = $db->query($query);
if ($results) {
    $conversations = array();
    while ($row = $results->fetch_assoc()) {
        $conversations[] = array(
            "user" => User::get_by_id($row['user_id']),
            "message" => Message::create($row)
        );
    }
    echo json_encode(array(
        "success" => "Conversations retrieved successfully.",
        "conversations" => $conversations
    ));
} else {
    echo $db->error;
    echo json_encode(array("error" => "Error getting conversations."));
}
