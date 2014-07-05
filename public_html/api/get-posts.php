<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (isset($_POST['id'])) {
    $USER_ID = (int)$_POST['id'];
} else {
    $USER_ID = $CURRENT_USER->id;
}

if (isset($_POST['exclude'])) {
    $exclude_ids = explode(",", $_POST['exclude']);
    foreach ($exclude_ids as &$id) {
        $id = $db->real_escape_string((int)$id);
    }
} else {
    $exclude_ids = array(0);
}

$num_posts = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;

$query = "SELECT * FROM `posts`
          WHERE `user_id`=".$db->real_escape_string($USER_ID)."
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
    echo json_encode(array("error" => "There was an error getting the user's posts."));
}
