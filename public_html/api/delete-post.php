<?php

/**
 * Delete Post
 * ===========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (required) The id of the post you want to remove.
 * 
 * Return on success:
 * "success" The success message.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the MIT License.
 */

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$POST_ID = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$post = Post::get_by_id($POST_ID);
if (!$post) {
    echo json_encode(array("error" => "No post to delete!"));
    exit;
}

$query = "DELETE FROM `posts`
          WHERE `post_id`='".$db->real_escape_string($POST_ID)."'
          AND `user_id`=".$db->real_escape_string($CURRENT_USER->id);
$results = $db->query($query);
if ($results) {
    //If there is an .m4a file attached to this post, unlink the file
    if ($post->riff && $post->riff->id) {
        $path = RIFF_ABSOLUTE_PATH."/{$post->riff->id}.m4a";
        if (file_exists($path)) {
            unlink($path);
        }
    }
    echo json_encode(array("success" => "Successfully deleted post from user."));
    exit;
}

echo json_encode(array("error" => "Error deleting post from user."));