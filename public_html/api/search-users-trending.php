<?php

/**
 * Search Users Trending
 * =====================
 * 
 * Authentication required.
 * Gets the users who have acquired the most karma in the last day, week,
 * month, or all time, optionally matching the given tags.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of users per page. Defaults to 15.
 * "tags" (optional) An array or comma-separated string of tags that the users should match.
 * "time" (optional) Get the most trending of the day, week, month, or all time.
 *                   Options: "day", "week", "month", "all". Defaults to "week".
 * 
 * Return on success:
 * "success" The success message.
 * "users" An array of trending user objects.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$tags = array();
if (isset($_POST['tags'])) {
    $tag_pattern = '/[^a-zA-Z0-9_\- ]/';
    if (is_array($_POST['tags'])) {
        $tags = preg_replace($tag_pattern, "", $_POST['tags']);
    } else {
        $tags = preg_replace($tag_pattern, "", array_filter(explode(',', $_POST['tags'])));
    }
}

$time = isset($_POST['time']) ? $_POST['time'] : "week";
if (!in_array($time, array("day", "week", "month", "all"))) {
    $time = "week";
}

$users = UserFeed::search_trending($time, $tags, $page, $limit);
if (is_array($users)) {
    echo json_encode(array(
        "success" => "Found some trending users.",
        "users" => $users
    ));
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
