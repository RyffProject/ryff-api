<?php

set_include_path(implode(PATH_SEPARATOR, array(
    get_include_path(),
    __DIR__."/../../resources"
)));

require_once("global.php");

$name = isset($_POST['name']) ? trim($_POST['name']) : "";
$username = isset($_POST['username']) ? trim($_POST['username']) : "";
$email = isset($_POST['email']) ? trim($_POST['email']) : "";
$bio = isset($_POST['bio']) ? trim($_POST['bio']) : "";
$password = isset($_POST['password']) ? trim($_POST['password']) : "";

if (!$username) {
    echo json_encode(array("error" => "Missing username."));
    exit;
} else if (strlen($username) > 32) {
    echo json_encode(array("error" => "Username cannot be more than 32 characters."));
    exit;
}
if (!$password) {
    echo json_encode(array("error" => "Missing password."));
    exit;
}
$username_results = $db->query("SELECT * FROM `users` WHERE `username`='".$db->real_escape_string($username)."' AND `active`=1");
if ($username_results && $username_results->num_rows) {
    echo json_encode(array("error" => "Username already in use."));
    exit;
}
if ($email) {
    $email_results = $db->query("SELECT * FROM `users` WHERE `email`='".$db->real_escape_string($email)."' AND `active`=1");
    if ($email_results && $email_results->num_rows) {
        echo json_encode(array("error" => "Email already in use."));
        exit;
    }
}

$password_hash = password_hash($password, PASSWORD_DEFAULT);
$query = "INSERT INTO `users`
          (`name`, `username`, `email`, `bio`, `password`, `date_updated`)
          VALUES ('".$db->real_escape_string($name)."','".$db->real_escape_string($username)."'
          ,'".$db->real_escape_string($email)."','".$db->real_escape_string($bio)."'
          ,'".$db->real_escape_string($password_hash)."',NOW())";
$results = $db->query($query);

if ($results) {
    $user_id = $db->insert_id;
    if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
        $latitude = (double)$_POST['latitude'];
        $longitude = (double)$_POST['longitude'];
        
        if ($latitude && $longitude) {
            $location_query = "INSERT INTO `locations` (`user_id`, `location`)
                               VALUES (".$db->real_escape_string((int)$user_id).",
                               POINT(".$db->real_escape_string($latitude).",".
                               $db->real_escape_string($longitude)."))";
            $results = $db->query($location_query);
        }
    }
    if (isset($_FILES['avatar']) && !$_FILES['avatar']['error'] && $_FILES['avatar']['type'] === "image/png") {
        $path = AVATAR_ABSOLUTE_PATH."/$user_id.png";
        if (file_exists($path)) {
            unlink($path);
        }
        move_uploaded_file($_FILES['avatar']['tmp_name'], $path);
    }
    echo json_encode(array(
        "success" => "You have successfully registered, $username.",
        "user" => get_user_from_username($username)
    ));
} else {
    echo json_encode(array("error" => "There was an error processing your request."));
}
