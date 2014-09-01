<?php

define("SITE_ROOT", "");
define("MEDIA_URL", ""); //Contains avatars, posts, and riffs subfolders.
define("MEDIA_ABSOLUTE_PATH", ""); //Contains avatars, posts, and riffs subfolders.

define("DB_NAME", "");
define("DB_USER", "");
define("DB_HOST", "");
define("DB_PASS", "");

define("APNS_CERTIFICATE", ""); //Path to the APNs SSL certificate
define("APNS_PASSPHRASE", ""); //Passphrase for APNs private key
define("APNS_GATEWAY", ""); //The APNs gateway to connect to
define("APNS_CONNECT_TIMEOUT", 60); //How long the script will wait to connect

define("COOKIE_LIFESPAN", 604800); //The amount of time it takes for auth tokens to expire.
define("NOTIFICATION_TIMEOUT", 7200); //The time it takes before notifications will not longer stack
define("PUSH_NOTIFICATION_LIMIT", 1000); //How many notifications the cron script will push per run
define("PUSH_MESSAGE_LIMIT", 1000); //How many messages the cron script will push per run

date_default_timezone_set("America/Los_Angeles");
