<?php

header("Content-Type: application/json");

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
        echo json_encode(array("error" => "Authentication required."));
        exit;
    }
    
    if (!Auth::is_auth_token_valid($AUTH_USER_ID, $AUTH_TOKEN)) {
        echo json_encode(array("error" => "Invalid credentials."));
        exit;
    }
    $CURRENT_USER = User::get_by_id($AUTH_USER_ID);
}
