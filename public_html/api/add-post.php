<?php

/**
 * Add Post
 * ========
 * 
 * Authentication required.
 * 
 * NOTE: Either the content of the post must be set, or the title and riff 
 * upload must both be set.
 * 
 * POST variables:
 * "title" The title of the post. Titles longer than 255 characters will be truncated.
 * "duration" (optional) Duration of the associated riff, defaults to zero.
 * "content" The body of the post. Bodies longer than 65535 bytes will be truncated.
 * "parent_ids" (optional) Array of ids of the parent posts sampled in this post's riff.
 * 
 * File uploads:
 * "image" (optional) A PNG image.
 * "riff" An .m4a audio file.
 * 
 * Return on success:
 * "success" The success message.
 * "post" The created post object.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

header("Content-Type: application/json");

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$content = isset($_POST['content']) ? trim($_POST['content']) : "";
if (!$content && 
        ((!isset($_FILES['riff']) || $_FILES['riff']['error']) ||
        (!isset($_POST['title']) || !$_POST['title']))) {
    echo json_encode(array("error" => "No post to add!"));
    exit;
}

if (isset($_POST['parent_ids'])) {
    $parent_ids = array_filter(
        array_map(intval, array_filter(explode(',', $_POST['parent_ids']))),
        function($id) { return Post::get_by_id($id) !== null; }
    );
} else {
    $parent_ids = array();
}

if (isset($_FILES['image']) && !$_FILES['image']['error'] && $_FILES['image']['type'] === "image/png") {
    $img_tmp_path = $_FILES['image']['tmp_name'];
} else {
    $img_tmp_path = "";
}

$title = isset($_POST['title']) ? trim($_POST['title']) : "";
$duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
if (isset($_FILES['riff']) && !$_FILES['riff']['error']) {
    $riff_tmp_path = $_FILES['riff']['tmp_name'];
} else {
    $riff_tmp_path = "";
}

$post = Post::add($content, $parent_ids, $img_tmp_path,
        $title, $duration, $riff_tmp_path);
if ($post) {
    //Add an upvote from the current user. Don't send an error on failure though.
    Upvote::add($post->id);
    
    echo json_encode(array(
        "success" => "Successfully added post from user.",
        "post" => Post::get_by_id($post->id)
    ));
} else {
    echo json_encode(array("error" => "Error adding post from user."));
}
