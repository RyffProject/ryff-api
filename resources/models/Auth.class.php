<?php

/**
 * @class Auth
 * ===========
 * 
 * Provides static functions for logging in, logging out, and verifying
 * login credentials.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */
class Auth {
    /**
     * Creates a new auth token for the user and sets both the user_id and
     * auth token cookies.
     * 
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function set_logged_in($user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = $CURRENT_USER->id;
        }
        
        $expiration = time() + COOKIE_LIFESPAN;
        $expiration_date = date('Y-m-d H:i:s', $expiration);
        $auth_token = bin2hex(openssl_random_pseudo_bytes(32));
        
        $query = "
            INSERT INTO `auth_tokens` (`user_id`, `token`, `date_expires`)
            VALUES (:user_id, :auth_token, :expiration_date)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->bindValue('auth_token', $auth_token);
        $sth->bindValue('expiration_date', $expiration_date);
        if (!$sth->execute()) {
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
     * @global NestedPDO $dbh
     * @global User $CURRENT_USER
     * @param int $user_id [optional] Defaults to the current user.
     * @return boolean
     */
    public static function set_logged_out($user_id = null) {
        global $dbh, $CURRENT_USER;
        
        if ($user_id === null && $CURRENT_USER) {
            $user_id = (int)$CURRENT_USER->id;
        }
        
        $expiration = time() - 3600;
        $expiration_date = date('Y-m-d H:i:s', $expiration);
        
        $query = "
            UPDATE `auth_tokens`
            SET `date_expires` = :expiration_date
            WHERE `user_id` = :user_id";
        $sth = $dbh->prepare($query);
        $sth->bindValue('user_id', $user_id);
        $sth->bindValue('expiration_date', $expiration_date);
        if (!$sth->execute()) {
            return false;
        }
        
        setcookie('user_id', '', $expiration);
        setcookie('auth_token', '', $expiration);
        
        return true;
    }
    
    /**
     * Verifies that the username/password combination is valid.
     * 
     * @global NestedPDO $dbh
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public static function is_login_valid($username, $password) {
        global $dbh;

        $query = "SELECT `password` FROM `users`
                  WHERE `username` = :username";
        $sth = $dbh->prepare($query);
        $sth->bindValue('username', $username);
        $sth->execute();
        
        $password_hash = $sth->fetchColumn();
        if (password_verify($password, $password_hash)) {
            return true;
        }
        return false;
    }
    
    /**
     * Verifies that this user_id and auth_token are valid, not expired, and match.
     * 
     * @global NestedPDO $dbh
     * @param int $user_id
     * @param string $auth_token
     * @return boolean
     */
    public static function is_auth_token_valid($user_id, $auth_token) {
        global $dbh;
        
        $query = "
            SELECT `date_expires` FROM `auth_tokens`
            WHERE `user_id` = :user_id
            AND `token` = :auth_token
            AND `token_id`=(
                SELECT `token_id` FROM `auth_tokens`
                WHERE `user_id` = :user_id
                ORDER BY `date_created` DESC
                LIMIT 1
            )";
        $sth = $dbh->prepare($query);
        
        $sth->bindValue('user_id', $user_id);
        $sth->bindValue('auth_token', $auth_token);
        $sth->execute();
        
        $date_expires = $sth->fetchColumn();
        if (!$date_expires || time() >= strtotime($date_expires)) {
            return false;
        }
        return true;
    }
}
