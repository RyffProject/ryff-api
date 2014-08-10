<?php

/**
 * Search Posts Trending
 * =====================
 * 
 * Authentication required.
 * Gives an array of posts sorted by how popular they are at the moment.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of posts per page. Defaults to 15.
 * "tags" (optional) An array or comma-separated string of tags that the posts should match.
 * 
 * Return on success:
 * "success" The success message.
 * "posts" An array of post objects sorted by most recent and optionally tagged.
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

$tags = array();
if (isset($_POST['tags'])) {
    $tag_pattern = '/[^a-zA-Z0-9_\- ]/';
    if (is_array($_POST['tags'])) {
        $tags = preg_replace($tag_pattern, "", $_POST['tags']);
    } else {
        $tags = preg_replace($tag_pattern, "", explode(',', $_POST['tags']));
    }
}
if ($tags) {
    $safe_tags = array_map(function($tag) use ($db) {
            return "'".$db->real_escape_string($tag)."'";
        }, $tags
    );
}

$query = "SELECT DISTINCT(p.`post_id`),
              (COUNT(up.`upvote_id`) / (NOW() - p.`date_created`)) AS `score`
          FROM `posts` AS p
          ".($tags ? "JOIN `post_tags` AS t
          ON t.`post_id` = p.`post_id`" : "")."
          JOIN `upvotes` AS up
          ON up.`post_id` = p.`post_id`
          ".($tags ? "WHERE t.`tag` IN (".implode(',', $safe_tags).")" : "")."
          ORDER BY `score` DESC
          LIMIT ".(($page_num - 1) * $num_posts).", ".$num_posts;
$results = $db->query($query);

if ($results) {
    $posts = array();
    while ($row = $results->fetch_assoc()) {
        $post = Post::get_by_id((int)$row['post_id']);
        if ($post) {
            $posts[] = $post;
        }
    }
    echo json_encode(array(
        "success" => "Found some trending posts.",
        "posts" => $posts
    ));
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
