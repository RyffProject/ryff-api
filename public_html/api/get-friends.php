<?php

/**
 * Get Friends
 * ===========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (optional) The id of the user whose friends you want. Defaults to the current user.
 * "exclude" (optional) A comma-separated list of the user ids you have already received.
 * "limit" (optional) The maximum amount of users that will be returned. Defaults to 5.
 * 
 * Return on success:
 * "success" The success message.
 * "users" An array of user objects that are friends of the requested user.
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

$query = "SELECT u.`user_id`, u.`name`, u.`username`, u.`email`, u.`bio`, u.`date_created`
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
        $user = User::create($row);
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
