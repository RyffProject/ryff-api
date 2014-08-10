<?php

/**
 * Get Users Trending
 * ==================
 * 
 * Authentication required.
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of users per page. Defaults to 15.
 * "tags" (optional) An array or comma-separated string of tags that the users should match.
 * "time" (optional) Get the most trending of the day, week, month, or all time.
 *                   Options: "day", "week", "month", "all". Defaults to "week".
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
    $safe_tags = array_map(function($tag) use ($db) {
            return "'".$db->real_escape_string($tag)."'";
        }, $tags
    );
}

$time = isset($_POST['time']) ? $_POST['time'] : "week";
if (!in_array($time, array("day", "week", "month", "all"))) {
    $time = "week";
}
switch ($time) {
    case "day":
        $from_time = time() - (60 * 60 * 24);
        break;
    case "week":
        $from_time = time() - (60 * 60 * 24 * 7);
        break;
    case "month":
        $from_time = time() - (60 * 60 * 24 * 30);
        break;
    case "all":
        $from_time = 0;
        break;
}
$from_date = date("Y-m-d H:i:s", $from_time);

$query = "SELECT DISTINCT(u.`user_id`), u.`name`, u.`username`, u.`email`, u.`bio`, u.`date_created`,
              COUNT(up.`upvote_id`) AS `num_upvotes`
          FROM `users` AS u
          JOIN `user_tags` AS t
          ON t.`user_id` = u.`user_id`
          JOIN `posts` AS p
          ON p.`user_id` = u.`user_id`
          JOIN `upvotes` AS up
          ON up.`post_id` = p.`post_id`
          WHERE up.`date_created` >= '".$db->real_escape_string($from_date)."'
          ".($tags ? "AND t.`tag` IN (".implode(',', $safe_tags).")" : "")."
          AND u.`user_id`!=".$db->real_escape_string($CURRENT_USER->id)."
          ORDER BY `num_upvotes` DESC
          LIMIT ".(($page_num - 1) * $num_users).", ".$num_users;
$results = $db->query($query);

if ($results && $results->num_rows) {
    $users = array();
    while ($row = $results->fetch_assoc()) {
        $user = User::create($row);
        if ($user) {
            $users[] = $user;
        }
    }
    if (count($users)) {
        echo json_encode(array(
            "success" => "Found some users nearby.",
            "users" => $users
        ));
    } else {
        echo json_encode(array("error" => "Could not find any users."));
    }
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
