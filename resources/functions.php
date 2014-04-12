<?php

function get_user_from_username($username) {
    global $db;
    
    $query = "SELECT * FROM `users`
              WHERE `username`='".$db->real_escape_string($username)."'
              AND `active`=1";
    $results = $db->query($query);
    if ($results) {
        if ($row = $results->fetch_assoc()) {
            $user = new User($row['user_id'], $row['name'], $row['username'], $row['email'], $row['bio']);
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
            $user = new User($row['user_id'], $row['name'], $row['username'], $row['email'], $row['bio']);
            return $user;
        }
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