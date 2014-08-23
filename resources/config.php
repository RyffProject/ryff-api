<?php

define("SITE_ROOT", "");
define("MEDIA_URL", ""); //Contains avatars, posts, and riffs subfolders.
define("MEDIA_ABSOLUTE_PATH", ""); //Contains avatars, posts, and riffs subfolders.

define("DB_NAME", "");
define("DB_USER", "");
define("DB_HOST", "");
define("DB_PASS", "");

define("COOKIE_LIFESPAN", 604800); //The amount of time it takes for auth tokens to expire.
define("NOTIFICATION_TIMEOUT", 7200); //The time it takes before notifications will not longer stack

date_default_timezone_set("America/Los_Angeles");
