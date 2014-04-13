<?php

function get_user_from_username($username) {
    global $db;
    
    $query = "SELECT * FROM `users`
              WHERE `username`='".$db->real_escape_string($username)."'
              AND `active`=1";
    $results = $db->query($query);
    if ($results) {
        if ($row = $results->fetch_assoc()) {
            $user = new User($row['user_id'], $row['name'], $row['username'], 
                    $row['email'], $row['bio'], $row['date_created']);
            return $user;
        }
    }
}

function get_user_from_id($user_id) {
    global $db;
    
    $query = "SELECT * FROM `users`
              WHERE `user_id`=".$db->real_escape_string((int)$user_id)."
              AND `active`=1";
    $results = $db->query($query);
    if ($results) {
        if ($row = $results->fetch_assoc()) {
            $user = new User($row['user_id'], $row['name'], $row['username'], 
                    $row['email'], $row['bio'], $row['date_created']);
            return $user;
        }
    }
}

function get_location_from_user_id($user_id) {
    global $db;
    
    $query = "SELECT X(`location`) AS `x`, Y(`location`) AS `y`
              FROM `locations` WHERE `user_id`=".$db->real_escape_string($user_id)."
              ORDER BY `date_created` DESC LIMIT 1";
    $results = $db->query($query);
    if ($results && $results->num_rows) {
        if ($row = $results->fetch_assoc()) {
            return new Point($row['x'], $row['y']);
        }
    }
    return null;
}

function get_avatar_url($user_id) {
    $path = AVATAR_ABSOLUTE_PATH."/$user_id.png";
    if (file_exists($path)) {
        return SITE_ROOT."/avatars/$user_id.png";
    } else {
        return "";
    }
}

function get_post_from_id($post_id) {
    global $db;
    
    $post_query = "SELECT * FROM `posts` WHERE `post_id`=".$db->real_escape_string($post_id);
    $post_results = $db->query($post_query);
    if ($post_results && $post_results->num_rows && $post_row = $post_results->fetch_assoc()) {
        $user = get_user_from_id($post_row['user_id']);
        if (!$user) {
            return;
        }
        $riff_query = "SELECT * FROM `riffs` WHERE `post_id`=".$db->real_escape_string($post_id);
        $riff_results = $db->query($riff_query);
        if ($riff_results && $riff_results->num_rows && $riff_row = $riff_results->fetch_assoc()) {
            $riff_id = $riff_row['riff_id'];
            $path = RIFF_ABSOLUTE_PATH."/$riff_id.m4a";
            if (file_exists($path)) {
                $riff = new Riff($riff_row['riff_id'], 
                        $riff_row['title'], SITE_ROOT."/riffs/$riff_id.m4a");
            }
        }
        $post = new Post($post_id, $user, isset($riff) ? $riff : 0, 
                $post_row['content'], $post_row['date_created']);
        return $post;
    }
}

function valid_login($username, $password) {
    global $db;
    
    $query = "SELECT `password` FROM `users`
              WHERE `username`='".$db->real_escape_string($username)."'
              AND `active`=1";
    $results = $db->query($query);
    if ($results) {
        if ($row = $results->fetch_assoc()) {
            $password_hash = $row['password'];
            if (password_verify($password, $password_hash)) {
                return true;
            }
        }
    }
    return false;
}
