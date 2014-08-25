<?php

/**
 * @class Auth
 * ===========
 * 
 * Provides static functions for logging in, logging out, and verifying
 * login credentials.
 * 
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */
class Auth {
    /**
     * Creates a new auth token for the user and sets both the user_id and
     * auth token cookies.
     * 
     * @global mysqli $db
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function set_logged_in($user_id = null) {
        global $db, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $expiration = time() + COOKIE_LIFESPAN;
        $expiration_date = date('Y-m-d H:i:s', $expiration);
        $auth_token = bin2hex(openssl_random_pseudo_bytes(32));
        
        $query = "
            INSERT INTO `auth_tokens` (`user_id`, `token`, `date_expires`)
            VALUES
            (
                ".$db->real_escape_string((int)$user_id).",
                '".$db->real_escape_string($auth_token)."',
                '".$db->real_escape_string($expiration_date)."'
            )";
        
        if (!$db->query($query)) {
            return false;
        }
        
        setcookie('user_id', $user_id, $expiration);
        setcookie('auth_token', $auth_token, $expiration);
        
        return true;
    }
    
    /**
     * Expires the user's auth token in the database and sets
     * login cookies to expire.
     * 
     * @global mysqli $db
     * @global string $AUTH_TOKEN
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function set_logged_out($user_id = null) {
        global $db, $AUTH_TOKEN, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = (int)$CURRENT_USER->id;
        }
        
        $expiration = time() - 3600;
        $expiration_date = date('Y-m-d H:i:s', $expiration);
        
        $query = "
            UPDATE `auth_tokens`
            SET `date_expires`='".$db->real_escape_string($expiration_date)."'
            WHERE `user_id`=".$db->real_escape_string((int)$user_id)."
            AND `token`='".$db->real_escape_string($AUTH_TOKEN)."'";
        
        if (!$db->query($query)) {
            return false;
        }
        
        setcookie('user_id', '', $expiration);
        setcookie('auth_token', '', $expiration);
        
        return true;
    }
    
    /**
     * Verifies that the username/password combination is valid.
     * 
     * @global mysqli $db
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public static function is_login_valid($username, $password) {
        global $db;

        $query = "SELECT `password` FROM `users`
                  WHERE `username`='".$db->real_escape_string($username)."'";
        $results = $db->query($query);
        if ($results && $results->num_rows > 0) {
            $row = $results->fetch_assoc();
            $password_hash = $row['password'];
            if (password_verify($password, $password_hash)) {
                return true;
            }
        }
        
        return false;
    }
}
