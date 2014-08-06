<?php

/**
 * Get News Feed
 * =============
 * 
 * Authentication required.
 * Gets the posts of the users you follow, ordered by most recent first.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of posts per page. Defaults to 15.
 * 
 * Return on success:
 * "success" The success message.
 * "posts" An array of no more than "limit" post objects in descending chronological order.
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

$page_num = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$num_posts = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$query = "SELECT a.* FROM `posts` AS a
          JOIN `follows` AS b
          ON b.`to_id` = a.`user_id`
          AND b.`from_id` = ".$db->real_escape_string($CURRENT_USER->id)."
          ORDER BY a.`date_created` DESC
          LIMIT ".(($page_num - 1) * $num_posts).", ".$num_posts;
$results = $db->query($query);

if ($results) {
    $posts = array();
    while ($row = $results->fetch_assoc()) {
        $post = Post::get_by_id($row['post_id']);
        if ($post) {
            $posts[] = $post;
        }
    }
    echo json_encode(array(
        "success" => "Retrieved posts successfully.",
        "posts" => $posts
    ));
} else {
    echo json_encode(array("error" => "There was an error getting the news feed."));
}
