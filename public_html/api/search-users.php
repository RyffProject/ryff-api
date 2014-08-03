<?php

/**
 * Search Users
 * ============
 * 
 * POST variables:
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of users per page. Defaults to 15.
 * "query" (required) The text that the returned users should match.
 * 
 * Return on success:
 * "success" The success message.
 * "users" An array of user objects that match the query.
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

$user_location = $CURRENT_USER->get_location();
if (!$user_location) {
    echo json_encode(array("error" => "No location found for user."));
    exit;
}

$page_num = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$num_users = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$query_str = isset($_POST['query']) ? trim($_POST['query']) : false;
if (!$query_str) {
    echo json_encode(array("error" => "You must provide a query to search for."));
    exit;
}
$safe_query_str = $db->real_escape_string($query_str);

$query = "SELECT DISTINCT(u.`user_id`), u.`name`, u.`username`, 
          u.`email`, u.`bio`, u.`date_created`,
          SQRT(POW(X(l.`location`)-".$db->real_escape_string($user_location->x).",2)+
          POW(Y(l.`location`)-".$db->real_escape_string($user_location->y).",2)) AS `distance`
          FROM `users` AS u
          LEFT JOIN `posts` AS p
          ON p.`user_id`=u.`user_id`
          LEFT JOIN `genres` AS g
          ON g.`user_id`=u.`user_id`
          LEFT JOIN `instruments` AS i
          ON i.`user_id`=u.`user_id`
          LEFT JOIN `riffs` AS r
          ON r.`post_id`=p.`post_id`
          LEFT JOIN `locations` AS l
          ON l.`user_id`=u.`user_id`
          WHERE u.`active`=1
          AND u.`user_id`!=".$db->real_escape_string($CURRENT_USER->id)."
          AND l.`date_created`=(
              SELECT MAX(l2.`date_created`) 
              FROM `locations` AS l2 
              WHERE l2.`user_id`= l.`user_id`
          )
          AND 
          (
              u.`name` LIKE '%$safe_query_str%'
              OR u.`username` LIKE '%$safe_query_str%'
              OR u.`bio` LIKE '%$safe_query_str%'
              OR g.`genre` LIKE '%$safe_query_str%'
              OR i.`instrument` LIKE '%$safe_query_str%'
              OR p.`content` LIKE '%$safe_query_str%'
              OR r.`title` LIKE '%$safe_query_str%'
          )
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
        "success" => "Retrieved users successfully.",
        "users" => $users
        ));
    exit;
}

echo json_encode(array("error" => "Unable to retrieve users."));
