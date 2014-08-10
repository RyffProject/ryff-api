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
        array_map(intval, explode(',', $_POST['parent_ids'])),
        function($id) { return Post::get_by_id($id) !== null; }
    );
} else {
    $parent_ids = array();
}

$post_query = "INSERT INTO `posts` (`user_id`, `content`)
               VALUES (
                   ".$db->real_escape_string($CURRENT_USER->id).",
                   '".$db->real_escape_string($content)."'
               )";
$post_results = $db->query($post_query);
if ($post_results) {
    $post_id = $db->insert_id;
    
    if (isset($_FILES['image']) && !$_FILES['image']['error'] && $_FILES['image']['type'] === "image/png") {
        $path = MEDIA_ABSOLUTE_PATH."/posts/$post_id.png";
        if (file_exists($path)) {
            unlink($path);
        }
        move_uploaded_file($_FILES['image']['tmp_name'], $path);
    }
    
    //If there was a riff uploaded as well, it would have been uploaded with a title between
    //zero and 255 characters. Make sure the file was uploaded without errors and it's an m4a, 
    //then create a record in the riffs table with the title and save the .m4a in as riff_id.m4a
    if (isset($_FILES['riff']) && !$_FILES['riff']['error']) {
        $title = isset($_POST['title']) ? trim($_POST['title']) : "";
        $duration = isset($_POST['duration']) ? (int)$_POST['duration'] : 0;
        if ($title) {
            $riff_query = "INSERT INTO `riffs` (`post_id`, `title`, `duration`)
                           VALUES (".$db->real_escape_string($post_id).",
                           '".$db->real_escape_string($title)."',".
                           $db->real_escape_string($duration).")";
            $riff_results = $db->query($riff_query);
            if ($riff_results) {
                $riff_id = $db->insert_id;
            } else {
                echo json_encode(array("error" => "Error adding a new riff."));
            }
        }
        if ($riff_id) {
            $path = MEDIA_ABSOLUTE_PATH."/riffs/$riff_id.m4a";
            if (file_exists($path)) {
                unlink($path);
            }
            if (!move_uploaded_file($_FILES['riff']['tmp_name'], $path)) {
                echo json_encode(array("error" => "Unable to upload riff."));
                exit;
            }
        }
    }
    
    if ($parent_ids) {
        $post_family_query = "INSERT INTO `post_families` (`parent_id`, `child_id`) VALUES ";
        $post_family_query_pieces = array();
        foreach ($parent_ids as $parent_id) {
            $post_family_query_pieces[] = "(
                ".$db->real_escape_string($parent_id).",
                ".$db->real_escape_string($post_id)."
            )";
        }
        $post_family_query .= implode(',', $post_family_query_pieces);
        $post_family_results = $db->query($post_family_query);
        if (!$post_family_results) {
            echo json_encode(array("error" => "There was an error attaching a parent post."));
            exit;
        }
    }
    
    $tags = array();
    if (preg_match_all('/#([a-zA-Z0-9_]+)/', $content, $tags)) {
        $post_tags_query = "INSERT INTO `post_tags` (`post_id`, `tag`) VALUES ";
        $post_tags_query_pieces = array();
        foreach ($tags[1] as $tag) {
            $post_tags_query_pieces[] = "(
                ".$db->real_escape_string($post_id).",
                '".$db->real_escape_string($tag)."'
            )"; 
        }
        $post_tags_query .= implode(',', $post_tags_query_pieces);
        $db->query($post_tags_query);
    }
    
    echo json_encode(array(
        "success" => "Successfully added post from user.",
        "post" => Post::get_by_id($post_id)
    ));
} else {
    echo json_encode(array("error" => "Error adding post from user."));
}
