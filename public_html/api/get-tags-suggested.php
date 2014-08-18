<?php

/**
 * Get Tags Suggested
 * ==================
 * 
 * Authentication required.
 * Returns an array of no more than 10 tags to suggest the user search for.
 * 
 * Return on success:
 * "success" The success message.
 * "tags" An array of the suggested tags.
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

$tags = Tag::get_suggested();
if (is_array($tags)) {
    echo json_encode(array(
        "success" => "Successfully retrieved suggested tags.",
        "tags" => $tags
    ));
} else {
    echo json_encode(array("error" => "Error retrieving suggested tags."));
}
