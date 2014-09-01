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
 * Ryff API <http://www.github.com/rfotino/ryff-api>
 * Released under the Apache License 2.0.
 */

require_once("config.php");
require_once("connect-db.php");

require_once("models/auth.class.php");
require_once("models/conversation.class.php");
require_once("models/follow.class.php");
require_once("models/media-files.class.php");
require_once("models/message.class.php");
require_once("models/notification.class.php");
require_once("models/point.class.php");
require_once("models/post.class.php");
require_once("models/post-feed.class.php");
require_once("models/push-notification.class.php");
require_once("models/riff.class.php");
require_once("models/star.class.php");
require_once("models/tag.class.php");
require_once("models/upvote.class.php");
require_once("models/user.class.php");
require_once("models/user-feed.class.php");

if (isset($_COOKIE['user_id'])) {
    $AUTH_USER_ID = (int)$_COOKIE['user_id'];
}
if (isset($_COOKIE['auth_token'])) {
    $AUTH_TOKEN = preg_replace('/[^0-9a-f]/', '', $_COOKIE['auth_token']);
}

if (defined("REQUIRES_AUTHENTICATION") && REQUIRES_AUTHENTICATION) {
    if (!isset($AUTH_USER_ID) || !isset($AUTH_TOKEN)) {
        header("Content-Type: application/json");
        echo json_encode(array("error" => "Authentication required."));
        exit;
    }
    
    if (!Auth::is_auth_token_valid($AUTH_USER_ID, $AUTH_TOKEN)) {
        header("Content-Type: application/json");
        echo json_encode(array("error" => "Invalid credentials."));
        exit;
    }
    $CURRENT_USER = User::get_by_id($AUTH_USER_ID);
}
