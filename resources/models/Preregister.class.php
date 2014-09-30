<?php

/**
 * @class Preregister
 * ===========
 * 
 * Provides static functions for adding preregisters, sending out activation
 * emails, and other preregister-related tasks.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */
class Preregister {
    /**
     * Adds an email address and activation code to the preregisters table and
     * sends out an email confirming that the preregistration was received.
     * 
     * @global NestedPDO $dbh
     * @param string $email
     * @return boolean
     */
    public static function add($email) {
        global $dbh;
        
        if (!static::is_email_valid($email)) {
            return false;
        }
        
        $dbh->beginTransaction();
        $activation_code = static::get_activation_code();
        $query = "
            INSERT INTO `preregisters` (`email`, `activation_code`)
            VALUES (:email, :activation_code)";
        $sth = $dbh->prepare($query);
        $sth->bindValue('email', $email);
        $sth->bindValue('activation_code', $activation_code);
        if (!$sth->execute() ||
                !static::send_email($email, FROM_EMAIL,
                        PREREGISTRATION_RECEIVED_EMAIL_SUBJECT,
                        PREREGISTRATION_RECEIVED_EMAIL_BODY)) {
            $dbh->rollBack();
            return false;
        }
        
        $dbh->commit();
        return true;
    }
    
    /**
     * Returns true if the given $email has already been preregistered, or
     * false otherwise.
     * 
     * @global NestedPDO $dbh
     * @param string $email
     * @return boolean
     */
    public static function exists($email) {
        global $dbh;
        $query = "SELECT 1 FROM `preregisters` WHERE `email` = :email";
        $sth = $dbh->prepare($query);
        $sth->bindValue('email', $email);
        if (!$sth->execute() || !$sth->rowCount()) {
            return false;
        }
        return true;
    }
    
    /**
     * Returns true if the email is a valid email address, or false otherwise.
     * This test is not conclusive, it just checks whether the format looks
     * somewhat similar to an email address.
     * 
     * @param string $email
     * @return boolean
     */
    public static function is_email_valid($email) {
        return (bool)preg_match('/^[^@]+@[^\.@]+\.[^@]+$/', $email);
    }
    
    /**
     * Returns true if the given activation code is found in the system and
     * has not been used, or false otherwise.
     * 
     * @global NestedPDO $dbh
     * @param string $activation_code
     * @return boolean
     */
    public static function is_activation_valid($activation_code) {
        global $dbh;
        $query = "
            SELECT `preregister_id` FROM `preregisters`
            WHERE `activation_code` = :activation_code
            AND `used` = 0";
        $sth = $dbh->prepare($query);
        $sth->bindValue('activation_code', $activation_code);
        if (!$sth->execute() || !$sth->rowCount()) {
            return false;
        }
        return true;
    }
    
    /**
     * Sets the given activation code as used for the given user_id, if it
     * exists and has not already been used. Returns true on success, false on
     * failure.
     * 
     * @global NestedPDO $dbh
     * @param string $activation_code
     * @param int $user_id
     * @return boolean
     */
    public static function set_used($activation_code, $user_id) {
        global $dbh;
        $query = "
            UPDATE `preregisters`
            SET `used` = 1, `date_used` = NOW(), `user_id` = :user_id
            WHERE `used` = 0
            AND `activation_code` = :activation_code";
        $sth = $dbh->prepare($query);
        $sth->bindValue('activation_code', $activation_code);
        $sth->bindValue('user_id', $user_id);
        if (!$sth->execute() || !$sth->rowCount()) {
            return false;
        }
        return true;
    }
    
    /**
     * Returns a random 10-character activation code that consists of the
     * characters a-z and 0-9, which can be repeated.
     * 
     * @return string
     */
    protected static function get_activation_code() {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $code = "";
        for ($i = 0; $i < 10; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars))];
        }
        return $code;
    }
    
    /**
     * Sends an email with the appropriate headers from $to to $from, with
     * the given $subject and $body. The content type of $body is assumed
     * to be HTML.
     * 
     * @param string $to
     * @param string $from
     * @param string $subject
     * @param string $body
     * @return boolean
     */
    protected static function send_email($to, $from, $subject, $body) {
        $headers = array(
            'To: '.$to,
            'From: '.$from,
            'Reply-To: '.$from,
            'Return-Path: '.$from,
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf8',
            'X-Mailer: PHP/'.phpversion()
        );
        if (!mail($to, $subject, $body, implode("\r\n", $headers))) {
            return false;
        }
        return true;
    }
}
