<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (isset($_POST['exclude'])) {
    $exclude_ids = explode(",", $_POST['exclude']);
    foreach ($exclude_ids as &$id) {
        $id = $db->real_escape_string((int)$id);
    }
} else {
    $exclude_ids = array(0);
}

$num_posts = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;

$friend_ids = array();
$friend_query = "SELECT `to_id` FROM `friends`
                 WHERE `from_id`=".$db->real_escape_string($CURRENT_USER->id);
$friend_results = $db->query($friend_query);
if ($friend_results) {
    while ($row = $friend_results->fetch_assoc()) {
        $friend_ids[] = $db->real_escape_string((int)$row['to_id']);
    }
}

$query = "SELECT * FROM `posts`
          WHERE `user_id` IN (".implode(",", $friend_ids).")
          AND `post_id` NOT IN (".implode(",", $exclude_ids).")
          ORDER BY `date_created` DESC
          LIMIT ".$db->real_escape_string($num_posts);
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
    echo json_encode(array("error" => "There was an error getting the user's friends' posts."));
}
