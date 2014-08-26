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
require_once("models/riff.class.php");
require_once("models/star.class.php");
require_once("models/tag.class.php");
require_once("models/upvote.class.php");
require_once("models/user.class.php");
require_once("models/user-feed.class.php");

$ERRORS = 0;

if (!$db) {
    $ERRORS++;
}

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
    
    $auth_token_query = "
        SELECT * FROM `auth_tokens`
        WHERE `user_id`=".$db->real_escape_string($AUTH_USER_ID)."
        AND `token`='".$db->real_escape_string($AUTH_TOKEN)."'
        AND `token_id`=(
            SELECT `token_id` FROM `auth_tokens`
            WHERE `user_id`=".$db->real_escape_string($AUTH_USER_ID)."
            ORDER BY `date_created` DESC
            LIMIT 1
        )";
    $auth_token_results = $db->query($auth_token_query);
    if ($auth_token_results && $auth_token_results->num_rows) {
        $auth_token_row = $auth_token_results->fetch_assoc();
        $auth_token_expiration = $auth_token_row['date_expires'];
        if (time() >= strtotime($auth_token_expiration)) {
            echo json_encode(array("error" => "Your auth token has expired."));
            exit;
        }
    } else {
        echo json_encode(array("error" => "Invalid credentials."));
        exit;
    }
    $CURRENT_USER = User::get_by_id($AUTH_USER_ID);
}
