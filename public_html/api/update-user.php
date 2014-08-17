<?php

/**
 * Update User
 * ===========
 * 
 * Authentication required.
 * 
 * POST variables:
 * "name" (optional) The new name for the current user. No more than 255 characters.
 * "username" (optional) The new username for the current user. No more than 32 characters.
 * "email" (optional) The new email address for the current user. No more than 255 characters.
 * "bio" (optional) The new bio[graphy] for the current user. No more than 65535 bytes.
 * "password" (optional) The new password for the current user.
 * "latitude" (optional) The new latitude coordinate for the current user's location.
 * "longitude" (optional) The new longitude coordinate for the current user's location.
 * "tags" (optional) An array or comma-separated string of tags to set for the user.
 * 
 * File uploads:
 * "avatar" (optional) An image for the current user in PNG format.
 * 
 * Return on success:
 * "success" The success message.
 * "user" The updated user object.
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

if (isset($_POST['name'])) {
    $name = $_POST['name'];
    if (strlen($name) > 255) {
        echo json_encode(array("error" => "Name cannot be more than 255 characters."));
        exit;
    }
    $query = "UPDATE `users` SET `name`='".$db->real_escape_string($name)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update name."));
        exit;
    }
}

if (isset($_POST['username']) && $_POST['username']) {
    $username = $_POST['username'];
    if (User::get_by_username($username)) {
        echo json_encode(array("error" => "This username is already in use."));
        exit;
    }
    if (strlen($username) > 32) {
        echo json_encode(array("error" => "Username cannot be more than 32 characters."));
        exit;
    }
    $query = "UPDATE `users` SET `username`='".$db->real_escape_string($username)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update username."));
        exit;
    }
}

if (isset($_POST['email'])) {
    $email = $_POST['email'];
    if (User::get_by_email($email)) {
        echo json_encode(array("error" => "This email is already in use."));
        exit;
    }
    if (strlen($email) > 255) {
        echo json_encode(array("error" => "Email cannot be more than 255 characters."));
        exit;
    }
    $query = "UPDATE `users` SET `email`='".$db->real_escape_string($email)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update email."));
        exit;
    }
}

if (isset($_POST['bio'])) {
    $bio = $_POST['bio'];
    $query = "UPDATE `users` SET `bio`='".$db->real_escape_string($bio)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update bio."));
        exit;
    }
}

if (isset($_POST['password']) && $_POST['password']) {
    $password = $_POST['password'];
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $query = "UPDATE `users` SET `password`='".$db->real_escape_string($password_hash)."'
              WHERE `user_id`=".$db->real_escape_string($CURRENT_USER->id);
    $results = $db->query($query);
    if (!$results) {
        echo json_encode(array("error" => "Could not update password."));
        exit;
    }
}

if (isset($_FILES['avatar'])) {
    if ($_FILES['avatar']['error']) {
        echo json_encode(array("error" => "There was an error with your avatar upload."));
        exit;
    } else if ($_FILES['avatar']['type'] !== "image/png") {
        echo json_encode(array("error" => "Your avatar must be in PNG format."));
        exit;
    }
    $path = MEDIA_ABSOLUTE_PATH."/avatars/{$CURRENT_USER->id}.png";
    if (file_exists($path)) {
        unlink($path);
    }
    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $path)) {
        echo json_encode(array("error" => "Unable to upload avatar."));
        exit;
    }
}

if (isset($_POST['latitude']) && isset($_POST['longitude'])) {
    $latitude = (double)$_POST['latitude'];
    $longitude = (double)$_POST['longitude'];
    if ($latitude && $longitude) {
        $location_query = "INSERT INTO `locations` (`user_id`, `location`)
                           VALUES (".$db->real_escape_string($CURRENT_USER->id).",
                           POINT(".$db->real_escape_string($latitude).",".
                           $db->real_escape_string($longitude)."))";
        $results = $db->query($location_query);
        if (!$results) {
            echo json_encode(array("error" => "Could not update location."));
            exit;
        }
    }
}

if (isset($_POST['tags'])) {
    $new_tags = is_array($_POST['tags']) ? $_POST['tags'] : explode(',', $_POST['tags']);
    $current_tags = $CURRENT_USER->tags;
    
    $tags_to_add = array_diff($new_tags, $current_tags);
    foreach ($tags_to_add as $tag) {
        $tag_add_query = "
            INSERT INTO `user_tags` (`user_id`, `tag`)
            VALUES (
                ".$db->real_escape_string($CURRENT_USER->id).",
                '".$db->real_escape_string($tag)."'
            )";
        $tag_add_result = $db->query($tag_add_query);
        if (!$tag_add_result) {
            echo json_encode(array("error" => "Could not add tag."));
            exit;
        }
    }
    
    $tags_to_delete = array_diff($current_tags, $new_tags);
    foreach ($tags_to_delete as $tag) {
        $tag_delete_query = "
            DELETE FROM `user_tags`
            WHERE `user_id` = ".$db->real_escape_string($CURRENT_USER->id)."
            AND `tag` = '".$db->real_escape_string($tag)."'";
        $tag_delete_result = $db->query($tag_delete_query);
        if (!$tag_delete_result) {
            echo json_encode(array("error" => "Could not delete tag."));
            exit;
        }
    }
}

$user = User::get_by_id($CURRENT_USER->id);
if ($user) {
    echo json_encode(array("success" => "Successfully updated.", "user" => $user));
} else {
    echo json_encode(array("error" => "An error occurred processing your request."));
}
