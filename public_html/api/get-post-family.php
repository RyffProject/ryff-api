<?php

/**
 * Get Post Family
 * ===============
 * 
 * Returns the parents and children of a given post.
 * 
 * POST variables:
 * "id" (required) The id of the post whose family you would like to get.
 * 
 * Return on success:
 * "success" The success message.
 * "parents" An array of the parent posts.
 * "children" An array of the child posts.
 * 
 * Return on error:
 * "error" The error message.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the MIT License.
 */

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$post_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$post = Post::get_by_id($post_id);
if (!$post) {
    echo json_encode(array("error" => "You must provide the id of an existing post."));
    exit;
}

$parents = $post->get_parents();
$children = $post->get_children();

echo json_encode(array(
    "success" => "Successfully got parent and child posts.",
    "parents" => $parents,
    "children" => $children
));
exit;
