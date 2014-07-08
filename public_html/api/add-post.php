<?php

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

$parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : false;
//If there is a parent_id set, make sure it refers to an actual post
if (!Post::get_by_id($parent_id)) {
    $parent_id = false;
}

if ($parent_id) {
    $post_query = "INSERT INTO `posts` (`user_id`, `parent_id`, `content`)
                   VALUES (
                       ".$db->real_escape_string($CURRENT_USER->id).",
                       ".$db->real_escape_string($parent_id).",
                       '".$db->real_escape_string($content)."'
                   )";
} else {
    $post_query = "INSERT INTO `posts` (`user_id`, `content`)
                   VALUES (
                       ".$db->real_escape_string($CURRENT_USER->id).",
                       '".$db->real_escape_string($content)."'
                   )";
}
$post_results = $db->query($post_query);
if ($post_results) {
    $post_id = $db->insert_id;
    
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
            $path = RIFF_ABSOLUTE_PATH."/$riff_id.m4a";
            if (file_exists($path)) {
                unlink($path);
            }
            if (!move_uploaded_file($_FILES['riff']['tmp_name'], $path)) {
                echo json_encode(array("error" => "Unable to upload riff."));
                exit;
            }
        }
    }
    echo json_encode(array(
        "success" => "Successfully added post from user.",
        "post" => Post::get_by_id($post_id)
        ));
} else {
    echo json_encode(array("error" => "Error adding post from user."));
}
