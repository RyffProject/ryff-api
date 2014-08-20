<?php

/**
 * Get Users Nearby
 * ================
 * 
 * Authentication required.
 * 
 * NOTE: The current user must have latitude and longitude information in the database.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of users per page. Defaults to 15.
 * "tags" (optional) An array or comma-separated string of tags that the users should match.
 * 
 * Return on success:
 * "success" The success message.
 * "users" An array of user objects found nearby.
 * 
 * Return on error:
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

$user_location = $CURRENT_USER->get_location();
if (!$user_location) {
    echo json_encode(array("error" => "No location found for user."));
    exit;
}

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$tags = array();
if (isset($_POST['tags'])) {
    $tag_pattern = '/[^a-zA-Z0-9_\- ]/';
    if (is_array($_POST['tags'])) {
        $tags = preg_replace($tag_pattern, "", $_POST['tags']);
    } else {
        $tags = preg_replace($tag_pattern, "", explode(',', $_POST['tags']));
    }
}

$users = UserFeed::search_nearby($user_location, $tags, $page, $limit);
if (is_array($users)) {
    echo json_encode(array(
        "success" => "Found some users nearby.",
        "users" => $users
    ));
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
