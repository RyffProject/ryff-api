<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$post_id = isset($_POST['id']) ? (int)$_POST['id'] : false;
$post = get_post_from_id($post_id);
if (!$post) {
    echo json_encode(array("error" => "No post to favorite!"));
    exit;
}

$user_ids = get_upvote_user_ids($post->id);
$users = array();
foreach ($user_ids as $user_id) {
    $users[] = get_user_from_id($user_id);
}

echo json_encode(array(
    "success" => "Successfully got users who upvoted the post.",
    "users" => $users
));
