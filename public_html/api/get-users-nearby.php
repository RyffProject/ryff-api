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

//Select the closest users not in the $_POST['ids'] array, which are excluded
//because they have already been sent to the client
$query = "SELECT u.`user_id`, u.`name`, u.`username`, u.`email`, u.`bio`, u.`date_created`,
          SQRT(POW(X(l.`location`)-".$db->real_escape_string($user_location->x).",2)+
          POW(Y(l.`location`)-".$db->real_escape_string($user_location->y).",2)) AS `distance`
          FROM `users` AS u
          LEFT JOIN `locations` AS l
          ON l.`user_id` = u.`user_id` AND u.`active`=1
          WHERE l.`user_id` NOT IN (".implode(",", $exclude_ids).")
          AND l.`date_created`=(
              SELECT MAX(l2.`date_created`) 
              FROM `locations` AS l2 
              WHERE l2.`user_id`= l.`user_id`
          )
          AND l.`user_id`!=".$db->real_escape_string($CURRENT_USER->id)."
          ORDER BY `distance` ASC
          LIMIT ".$db->real_escape_string($num_users);
$results = $db->query($query);

if ($results && $results->num_rows) {
    $users = array();
    while ($row = $results->fetch_assoc()) {
        $user = new User($row['user_id'], $row['name'], $row['username'], 
                $row['email'], $row['bio'], $row['date_created']);
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
