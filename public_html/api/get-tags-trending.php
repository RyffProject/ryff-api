<?php

/**
 * Get Tags Trending
 * =================
 * 
 * Authentication required.
 * Returns an array of post tags that are currently trending.
 * 
 * POST variables:
 * "limit" (optional) The maximum number of tags to return. Defaults to 15.
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
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$num_tags = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$time = isset($_POST['time']) ? $_POST['time'] : "day";
if (!in_array($time, array("day", "week", "month", "all"))) {
    $time = "week";
}
switch ($time) {
    case "day":
        $from_time = time() - (60 * 60 * 24);
        break;
    case "week":
        $from_time = time() - (60 * 60 * 24 * 7);
        break;
    case "month":
        $from_time = time() - (60 * 60 * 24 * 30);
        break;
    case "all":
        $from_time = 0;
        break;
}
$from_date = date("Y-m-d H:i:s", $from_time);

$query = "SELECT t.`tag`, COUNT(t.`tag`) AS `score`
          FROM `post_tags` AS t
          WHERE t.`date_created` >= '".$db->real_escape_string($from_date)."'
          GROUP BY t.`tag`
          ORDER BY `score` DESC
          LIMIT $num_tags";
$results = $db->query($query);
if ($results) {
    $tags = array();
    while ($row = $results->fetch_assoc()) {
        $tags[] = $row['tag'];
    }
    echo json_encode(array(
        "success" => "Successfully retrieved trending tags.",
        "tags" => $tags
    ));
} else {
    echo json_encode(array("error" => "Error retrieving trending tags."));
}
