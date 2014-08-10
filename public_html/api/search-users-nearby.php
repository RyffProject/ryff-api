<?php

/**
 * Get Users Nearby
 * ================
 * 
 * Authentication required.
 * 
 * NOTE: The current user must have latitude and longitude information in the database.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of users per page. Defaults to 15.
 * "tags" (optional) An array or comma-separated string of tags that the users should match.
 * 
 * Return on success:
 * "success" The success message.
 * "users" An array of user objects found nearby.
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

$user_location = $CURRENT_USER->get_location();
if (!$user_location) {
    echo json_encode(array("error" => "No location found for user."));
    exit;
}

$page_num = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$num_users = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$tags = array();
if (isset($_POST['tags'])) {
    $tag_pattern = '/[^a-zA-Z0-9_\- ]/';
    if (is_array($_POST['tags'])) {
        $tags = preg_replace($tag_pattern, "", $_POST['tags']);
    } else {
        $tags = preg_replace($tag_pattern, "", explode(',', $_POST['tags']));
    }
}
if ($tags) {
    $safe_tags = array_map(function($tag) use($db) {
            return "'".$db->real_escape_string($tag)."'";
        }, $tags
    );
}

$query = "SELECT DISTINCT(u.`user_id`), u.`name`, u.`username`, u.`email`, u.`bio`, u.`date_created`,
          SQRT(POW(X(l.`location`)-".$db->real_escape_string($user_location->x).",2)+
          POW(Y(l.`location`)-".$db->real_escape_string($user_location->y).",2)) AS `distance`
          FROM `users` AS u
          ".($tags ? "JOIN `user_tags` AS t
          ON t.`user_id` = u.`user_id`" : "")."
          JOIN `locations` AS l
          ON l.`user_id` = u.`user_id`
          WHERE l.`date_created`=(
              SELECT MAX(l2.`date_created`) 
              FROM `locations` AS l2 
              WHERE l2.`user_id`= l.`user_id`
          )
          ".($tags ? "AND t.`tag` IN (".implode(',', $safe_tags).")" : "")."
          AND l.`user_id`!=".$db->real_escape_string($CURRENT_USER->id)."
          ORDER BY `distance` ASC
          LIMIT ".(($page_num - 1) * $num_users).", ".$num_users;
$results = $db->query($query);

if ($results) {
    $users = array();
    while ($row = $results->fetch_assoc()) {
        $user = User::create($row);
        if ($user) {
            $users[] = $user;
        }
    }
    echo json_encode(array(
        "success" => "Found some users nearby.",
        "users" => $users
    ));
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
