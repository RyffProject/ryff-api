<?php

/**
 * Get Friends
 * ===========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "id" (optional) The id of the user whose friends you want. Defaults to the current user.
 * "page" (optional) The page number of the results, 1-based.
 * "limit" (optional) The maximum number of users per page. Defaults to 15.
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

$page_num = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$num_users = isset($_POST['limit']) ? (int)$_POST['limit'] : 15;

$query = "SELECT u.`user_id`, u.`name`, u.`username`, u.`email`, u.`bio`, u.`date_created`
          FROM `users` AS u
          JOIN `friends` AS f
          ON f.`to_id`=u.`user_id`
          AND f.`from_id`=".$db->real_escape_string($USER_ID)."
          ORDER BY f.`date_created` ASC
          LIMIT ".(($page_num - 1) * $num_users).", ".$num_users;
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
