<?php
/**
 * Global
 * ======
 * 
 * The global include file. Connects the database and authenticates the current
 * user, if REQUIRES_AUTHENTICATION is defined and set to some truthy value.
 * 
 * If the database connects successfully, a global variable $dbh will be
 * created as a PDO object for use by the script that is including this file.
 * If there is an error connecting the database, the script will return an
 * error and exit.
 * 
 * If REQUIRES_AUTHENTICATION is set, both the 'user_id' and 'auth_token'
 * cookies must be set, and these will be checked against the database. If the
 * user_id and auth_token are not valid, the script will return an authentication
 * error and exit. If they are valid, a global variable $CURRENT_USER will be
 * created as the authenticated user.
 * 
 * The files in the models/ directory are also included by this file, so that
 * any of the model classes are accessible from scripts that include this file.
 * 
 * Ryff API <http://www.github.com/RyffProject/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once("config.php");
require_once("connect-db.php");

/**
 * Register autoloader for model classes.
 */
spl_autoload_register(function($class_name) {
    @include("models/{$class_name}.class.php");
});

/**
 * If in test mode, register autoloader for test classes.
 */
if (TEST_MODE) {
    spl_autoload_register(function($class_name) {
        @include("tests/{$class_name}.class.php");
    });
}

/**
 * Get cookies for authentication.
 */
if (isset($_COOKIE['user_id']) && isset($_COOKIE['auth_token'])) {
    $AUTH_USER_ID = (int)$_COOKIE['user_id'];
    $AUTH_TOKEN = preg_replace('/[^0-9a-f]/', '', $_COOKIE['auth_token']);
    
    if (Auth::is_auth_token_valid($AUTH_USER_ID, $AUTH_TOKEN)) {
        $CURRENT_USER = User::get_by_id($AUTH_USER_ID);
    }
}

/**
 * If authentication is required, attempts to authenticate the user and
 * exits with an error on failure.
 */
if (defined("REQUIRES_AUTHENTICATION") && REQUIRES_AUTHENTICATION) {
    if (!isset($CURRENT_USER)) {
        header("Content-Type: application/json");
        echo json_encode(array("error" => "Authentication required."));
        exit;
    }
}
