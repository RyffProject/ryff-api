<?php
/**
 * Serve Media
 * ===========
 * 
 * Accesses to media go through this file, which makes sure that the
 * corresponding database object exists for the file before serving it.
 * In the future this can be used to restrict access further if privacy
 * settings are implemented.
 * 
 * GET variables:
 * "type" (required) The type of resource (avatar, post, or riff) to be served.
 * "id" (required) The id of the resource to be served.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../resources"
)));

require_once("global.php");

$type = isset($_GET['type']) ? $_GET['type'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$type || !$id) {
    header("HTTP/1.1 404 Not Found", true, 404);
    exit;
}

$media_dir = TEST_MODE ? TEST_MEDIA_ABSOLUTE_PATH : MEDIA_ABSOLUTE_PATH;
$riff_path = "$media_dir/riffs/$id.m4a";
$riff_hq_path = "$media_path/riffs/hq/$id.m4a";
$riff_raw_path = "$media_path/riffs/raw/$id.m4a";

switch ($type) {
    case "avatar":
        $content_type = "image/png";
        $object_exists = User::exists($id);
        $file_path = "$media_dir/avatars/$id.png";
        break;
    case "avatar_small":
        $content_type = "image/jpeg";
        $object_exists = User::exists($id);
        $file_path = "$media_dir/avatars/small/$id.jpg";
        break;
    case "post":
        $content_type = "image/png";
        $object_exists = Post::exists($id);
        $file_path = "$media_dir/posts/$id.png";
        break;
    case "post_medium":
        $content_type = "image/jpeg";
        $object_exists = Post::exists($id);
        $file_path = "$media_dir/posts/medium/$id.jpg";
        break;
    case "post_small":
        $content_type = "image/jpeg";
        $object_exists = Post::exists($id);
        $file_path = "$media_dir/posts/small/$id.jpg";
        break;
    case "riff":
        $content_type = "audio/mp4";
        $object_exists = Post::exists($id);
        if (Post::is_converted($id, false) && file_exists($riff_path)) {
            $file_path = $riff_path;
        } else if (Post::is_converted($id, true) && file_exists($riff_hq_path)) {
            $file_path = $riff_hq_path;
        } else if (file_exists($riff_raw_path)) {
            $file_path = $riff_raw_path;
        }
        break;
    case "riff_hq":
        $content_type = "audio/mp4";
        $object_exists = Post::exists($id);
        $file_path = "$media_dir/riffs/hq/$id.m4a";
        if (Post::is_converted($id, true) && file_exists($riff_hq_path)) {
            $file_path = $riff_hq_path;
        } else if (file_exists($riff_raw_path)) {
            $file_path = $riff_raw_path;
        }
        break;
    default:
        header("HTTP/1.1 404 Not Found", true, 404);
        exit;
}

if (!$object_exists || !file_exists($file_path)) {
    header("HTTP/1.1 404 Not Found", true, 404);
    exit;
}

header("Content-Type: $content_type");
header("Content-Length: ".filesize($file_path));
readfile($file_path);
