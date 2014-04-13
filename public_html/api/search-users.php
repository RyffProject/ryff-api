<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$user_location = get_location_from_user_id($CURRENT_USER->id);
if (!$user_location) {
    echo json_encode(array("error" => "No location found for user."));
    exit;
}

if (isset($_POST['exclude'])) {
    $exclude_ids = explode(",", $_POST['exclude']);
    foreach ($exclude_ids as &$id) {
        $id = $db->real_escape_string((int)$id);
    }
} else {
    $exclude_ids = array(0);
}

$num_users = isset($_POST['limit']) ? (int)$_POST['limit'] : 5;

$query_str = isset($_POST['query']) ? trim($_POST['query']) : "";
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
          AND u.`user_id` NOT IN (".implode(",", $exclude_ids).")
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
          LIMIT ".$db->real_escape_string($num_users);
$results = $db->query($query);
if ($results) {
    $users = array();
    while ($row = $results->fetch_assoc()) {
        $user = new User($row['user_id'], $row['name'], $row['username'],
                $row['email'], $row['bio'], $row['date_created']);
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
