<?php

define("REQUIRES_AUTHENTICATION", true);

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

if (isset($_POST['id'])) {
    $USER_ID = (int)$_POST['id'];
} else {
    $USER_ID = $CURRENT_USER->id;
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

$query = "SELECT u.`user_id`, u.`name`, u.`username`, u.`email`, u.`bio`
          FROM `users` AS u
          JOIN `friends` AS f
          ON f.`to_id`=u.`user_id`
          AND f.`from_id`=".$db->real_escape_string($USER_ID)."
          WHERE u.`user_id` NOT IN (".implode(",", $exclude_ids).")
          ORDER BY f.`date_created` ASC
          LIMIT ".$db->real_escape_string($num_users);
$results = $db->query($query);

if ($results) {
    $friends = array();
    while ($row = $results->fetch_assoc()) {
        $user = new User($row['user_id'], $row['name'], $row['username'], $row['email'], $row['bio']);
        if ($user) {
            $friends[] = $user;
        }
    }
    echo json_encode(array(
        "success" => "Retrieved friends successfully.",
        "users" => $friends
        ));
} else {
    echo json_encode(array("error" => "There was an error getting the user's friends."));
}
