<?php

/**
 * Get Tags Trending
 * =================
 * 
 * Authentication required.
 * Returns an array of no more than 10 post tags that are currently trending.
 * 
 * POST variables:
 * "time" (optional) Get the most trending of the day, week, month, or all time.
 *                   Options: "day", "week", "month", "all". Defaults to "day".
 * 
 * Return on success:
 * "success" The success message.
 * "tags" An array of the trending tags.
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

$time = isset($_POST['time']) ? $_POST['time'] : "day";
if (!in_array($time, array("day", "week", "month", "all"))) {
    $time = "week";
}

$tags = Tag::get_trending($time);
if (is_array($tags)) {
    echo json_encode(array(
        "success" => "Successfully retrieved trending tags.",
        "tags" => $tags
    ));
} else {
    echo json_encode(array("error" => "Error retrieving trending tags."));
}
