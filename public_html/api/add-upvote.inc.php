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

if ($post->is_upvoted) {
    $upvote_query = "DELETE FROM `upvotes`
                     WHERE `post_id`=".$db->real_escape_string($post->id)."
                     AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $upvote_results = $db->query($upvote_query);
    if ($upvote_results) {
        $new_post = get_post_from_id($post->id);
        if ($new_post) {
            echo json_encode(array(
                "success" => "Successfully removed upvote.",
                "post" => $new_post
            ));
        } else {
            echo json_encode(array("error" => "Removed upvote but was unable to get the new post."));
        }
    } else {
        echo json_encode(array("error" => "Unable to remove upvote."));
    }
} else {
    $upvote_query = "INSERT INTO `upvotes` (`post_id`, `user_id`)
                     VALUES (
                       ".$db->real_escape_string($post->id)."
                       ".$db->real_escape_string($CURRENT_USER->id)."
                     )";
    $upvote_results = $db->query($upvote_query);
    if ($upvote_results) {
        $new_post = get_post_from_id($post->id);
        if ($new_post) {
            echo json_encode(array(
                "success" => "Successfully added upvote.",
                "post" => $new_post
            ));
        } else {
            echo json_encode(array("error" => "Added upvote but was unable to get the new post."));
        }
    } else {
        echo json_encode(array("error" => "Unable to add upvote."));
    }
}
